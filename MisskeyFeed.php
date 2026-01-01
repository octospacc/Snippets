<?php
/**
 *  MisskeyFeed.php
 *  Copyright (C) 2025, OctoSpacc
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Requires php-gd, php-curl, and https://github.com/fastvolt/markdown ❗

// ============================= Configuration ============================= //
const INSTANCE = 'https://shark.octt.eu.org'; // Cambia con la tua istanza
const NOTES_LIMIT_DEFAULT = 5;
const NOTES_LIMIT_MAX = 25;
const WITH_SELF_REPLIES = true;
const UNICODE_HACKS = true;
const ONLY_PUBLIC = true;
const CENSOR_SENSITIVE_IMAGES = true;
const FONT_PATH = './Res/COMIC.TTF';
// ========================================================================= //

const SOURCE_CODE = 'https://gitlab.com/octospacc/Snippets/-/blob/main/MisskeyFeed.php';

require 'Res/FastVoltMarkdown.php';
use FastVolt\Helper\Markdown;

if ($userId = $_GET['userId'] ?? null) {
    generateAtomFeed($userId);
} elseif ($media = $_GET['media'] ?? null) {
    proxyAndConvertMedia($media);
} elseif ($media = $_GET['sensitive'] ?? null) {
    proxyAndConvertMedia($media, true);
} else {
    http_response_code(400);
    echo "<p>Error: specify at least 'userId' o 'media' query parameters.</p>
        <p><small><a href='" . SOURCE_CODE . "' target='_blank'>MisskeyFeed.php</a></small></p>";
}
exit;

function selfPrefix(): string {
    return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
}

function linkNote(string $noteId) {
    return INSTANCE . "/notes/$noteId";
}

function apiPost($endpoint, $data) {
    $url = INSTANCE . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function generateAtomFeed($userId) {
    $censorSensitiveImages = filter_var($_GET['censorSensitiveImages'] ?? CENSOR_SENSITIVE_IMAGES, FILTER_VALIDATE_BOOLEAN);
    $lengthFilter = abs(filter_var($_GET['lengthFilter'] ?? null, FILTER_VALIDATE_INT) ?? 0); // only supports filtering for notes LESS than length for now
    $forceTags = explode(',', strtolower($_GET['forceTags'] ?? ''));

    $notes = apiPost('/api/users/notes', [
        'userId' => $userId,
        'withRenotes' => false,
        'withReplies' => false,
        // 'withRepliesToSelf' => true, // $_GET['withRepliesToSelf'] ?? WITH_REPLIES_TO_SELF,
        'withChannelNotes' => false,
        'withFiles' => false,
        'allowPartial' => true,
        'limit' => max(min(abs(intval($_GET['limit'] ?? 0)), NOTES_LIMIT_MAX), NOTES_LIMIT_DEFAULT),
    ]);

    if (!is_array($notes)) {
        http_response_code(500);
        echo 'Errore nel recupero delle note.';
        exit;
    }

    function formatText(array $note, bool $html): string {
        $text = $note['text'] ?? '';
        if ($cw = $note['cw'] ?? null) {
            $text = "$cw\n\n$text";
        }
        if ($html) {
            if ($renote = $note['renoteId'] ?? null) {
                $link = linkNote($renote);
                $text = "[[RN]($link)]\n\n$text";
            }
            return mfmToHtml($text);
        } else {
            return parseMFM($text);
        }
    }
    
    function mfmToHtml(string $input): string {
        $input = parseMFM($input);
        if (filter_var($_GET['plaintext'] ?? '', FILTER_VALIDATE_BOOLEAN)) return $input;
        $inblock = false;
        $output = '';
        foreach (explode("\n", $input) as $line) {
            $patchedquote = false;
            $ltram = ltrim($line);
            if (str_starts_with($line, '```')) {
                $inblock = !$inblock;
            } else if (!$inblock && str_starts_with($line, '#')) {
                // prevent hashtags from being interpreted as headings
                $firstword = explode(' ', str_replace("\t", ' ', $line))[0];
                if ($firstword !== '#') {
                    $line = "&#x20;$firstword" . substr($line, strlen($firstword));
                }
            } else if (!$inblock && str_starts_with($ltram, '>') && $line !== $ltram) {
                // prevent quoteblocks from forming where they were explicitly avoided
                $line = substr($line, 0, strlen($line) - strlen($ltram)) . "\\$ltram  ";
                $patchedquote = true;
            }
            if (!$patchedquote && !$inblock) {
                // the parser won't support Markdown natural-linebreak mode, so add the famous two spaces
                $line .= "  ";
            }
            if (UNICODE_HACKS && !$inblock) {
                $line = unicodeHacks($line);
            }
            $output .= "$line\n";
        }
        if (filter_var($_GET['markdown'] ?? '', FILTER_VALIDATE_BOOLEAN)) return $output;
        return Markdown::new()->setContent($output)->toHtml();
    }

    function unicodeHacks(string $text) {
        $words = explode(' ', $text);
        for ($i=0; $i<sizeof($words); $i++) {
            $low = strtolower($words[$i]);
            $islink = str_starts_with($low, 'http://') || str_starts_with($low, 'https://');
            $maybelink = str_contains($low, ')') && str_contains($low, '](');
            if (!$islink && !$maybelink) {
                foreach([
                    '...' => '…',
                    'ffl' => 'ﬄ',
                    'ffi' => 'ﬃ',
                    'fl' => 'ﬂ',
                    'fi' => 'ﬁ',
                    'ff' => 'ﬀ',
                    'ij' => 'ĳ',
                ] as $search => $replace) {
                    $words[$i] = str_replace($search, $replace, $words[$i]);
                }
            }
        }
        return implode(' ', $words);
    }

    function parseMFM(string $input, int &$pos = 0, bool $inblock = false): string {
        $output = '';
        $length = strlen($input);
        while ($pos < $length) {
            // Detect start of MFM block
            if ($input[$pos] === '$' && $pos + 1 < $length && $input[$pos + 1] === '[') {
                $pos += 2; // Skip "$["
                // Skip feature name
                while ($pos < $length && (preg_match('/\w/', $input[$pos]) || in_array($input[$pos], ['.', ',', '=']))) {
                    $pos++;
                }
                // Skip whitespace
                while ($pos < $length && ctype_space($input[$pos])) {
                    $pos++;
                }
                // Recursively parse inner content
                $output .= parseMFM($input, $pos, true);
            } elseif ($inblock && $input[$pos] === ']') {
                $pos++; // Skip closing bracket
                return $output;
            } else {
                $output .= $input[$pos];
                $pos++;
            }
        }
        return $output;
    }

    function checkLengthLimit(string $text, int $limit): bool {
        return !($limit && strlen($text) > $limit);
    }

    function checkTags(string $text, array $tags): bool {
        foreach (["\t", "\n", '/', '(', ')'] as $search) {
            $text = str_replace($search, ' ', $text);
        }
        foreach (explode(' ', strtolower($text)) as $word) {
            foreach ($tags as $tag) {
                if ($word === "#$tag") {
                    return true;
                }
            }
        }
        return false;
    }
    
    header('Content-Type: application/atom+xml; charset=utf-8');

    echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    ?>
    <feed xmlns="http://www.w3.org/2005/Atom" xmlns:mk="https://misskey-hub.net/en/docs/for-developers/api/">
      <title>Note utente Misskey <?= $userId ?></title>
      <link href="<?= htmlspecialchars(selfPrefix() . $_SERVER['REQUEST_URI']) ?>" rel="self" />
      <link href="<?= htmlspecialchars(INSTANCE . '/users/' . $userId) ?>" rel="alternate" type="text/html" />
      <updated><?= date(DATE_ATOM) ?></updated>
      <id>tag:<?= parse_url(INSTANCE, PHP_URL_HOST) ?>,<?= date('Y-m-d') ?>:/feed/<?= htmlspecialchars($userId) ?></id>
      <generator uri="<?= SOURCE_CODE ?>">MisskeyFeed.php</generator>
    
    <?php foreach ($notes as $note): ?>
      <?php
        $plaintext = formatText($note, false);
        $force = checkTags($plaintext, $forceTags);
        if (!$note['id'] ||
            (ONLY_PUBLIC && $note['visibility'] !== 'public') ||
            (filter_var($_GET['withSelfReplies'] ?? WITH_SELF_REPLIES, FILTER_VALIDATE_BOOLEAN) === false && $note['replyId'] && !$force) ||
            (!checkLengthLimit($plaintext, $lengthFilter) && !$force)
        ) continue; ?>
      <entry>
        <title><?= htmlspecialchars(substr(strip_tags($note['text'] ?? 'Nota senza testo'), 0, 50)) ?>...</title>
        <link href="<?= linkNote($note['id']) ?>" />
        <id>tag:<?= parse_url(INSTANCE, PHP_URL_HOST) ?>,<?= substr($note['createdAt'], 0, 10) ?>:/notes/<?= $note['id'] ?></id>
        <updated><?= date(DATE_ATOM, strtotime($note['updatedAt'] ?? $note['createdAt'])) ?></updated>
        <published><?= date(DATE_ATOM, strtotime($note['createdAt'])) ?></published>
        <content type="html">
          <![CDATA[
            <?= formatText($note, true) ?>
            <?php if (!empty($note['files'])): ?>
              <div class="attachments">
                <?php foreach ($note['files'] as $file): ?>
                  <?php
                    $type = explode('/', $file['type'])[0];
                    $url = $file['url'];
                    if ($type === 'image') {
                        $url = selfPrefix() . $_SERVER['DOCUMENT_URI']
                            . ($censorSensitiveImages && $file['isSensitive'] ? '?sensitive=' : '?media=')
                            . end(explode('/', $url));
                    }
                  ?>
                  <?php if ($type === 'image'): ?>
                    <p><a href="<?= htmlspecialchars($file['url']) ?>" target="_blank"><img src="<?= htmlspecialchars($url) ?>" alt="<?= htmlspecialchars($file['comment']) ?>" style="max-width:100%" /></a></p>
                  <?php elseif ($type === 'video'): ?>
                    <p><a href="<?= htmlspecialchars($file['url']) ?>" target="_blank"><video src="<?= htmlspecialchars($url) ?>" style="max-width:100%"></video></a></p>
                  <?php elseif ($type === 'audio'): ?>
                    <p><a href="<?= htmlspecialchars($file['url']) ?>" target="_blank"><audio src="<?= htmlspecialchars($url) ?>" style="max-width:100%"></audio></a></p>
                  <?php else: ?>
                    <p><a href="<?= htmlspecialchars($file['url']) ?>" target="_blank"><?= htmlspecialchars($file['name']) ?></a></p>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          ]]>
        </content>
        <author>
          <name><?= htmlspecialchars($note['user']['name'] ?? $note['user']['username']) ?></name>
          <uri><?= htmlspecialchars(INSTANCE . '/users/' . $note['user']['id']) ?></uri>
        </author>
        <?php foreach ($note['tags'] ?? [] as $tag): ?>
          <category term="<?= $tag ?>" />
        <?php endforeach; ?>
        <mk:visibility><?= $note['visibility'] ?></mk:visibility>
        <mk:replyId><?= $note['replyId'] ?></mk:replyId>
        <mk:renoteId><?= $note['renoteId'] ?></mk:renoteId>
        <mk:renoteCount><?= $note['renoteCount'] ?></mk:renoteCount>
        <mk:repliesCount><?= $note['repliesCount'] ?></mk:repliesCount>
        <mk:reactionCount><?= $note['reactionCount'] ?></mk:reactionCount>
      </entry>
    <?php endforeach; ?>
    </feed>
<?php }

function proxyAndConvertMedia(string $fileId, bool $sensitive = false) {
    $imageData = file_get_contents(INSTANCE . '/files/' . $fileId);
    if (!$imageData) {
        http_response_code(500);
        echo 'Errore nel download del file.';
        return;
    }

    $image = imagecreatefromstring($imageData);
    if (!$image) {
        http_response_code(500);
        echo 'Errore nella decodifica dell\'immagine.';
        return;
    }

    if (!$sensitive) {
        header('Content-Type: image/jpeg');
        imagejpeg($image, null, 90);
        imagedestroy($image);    
    } else {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Create blurred background
        $background = imagecreatetruecolor($width, $height);
        imagecopy($background, $image, 0, 0, 0, 0, $width, $height);
        
        $smallW = $width / 100;
        $smallH = $height / 100;
        $small = imagecreatetruecolor($smallW, $smallH);
        imagecopyresampled($small, $background, 0, 0, 0, 0, $smallW, $smallH, $width, $height);
        
        // Blur the small image
        // for ($i = 0; $i < 5; $i++) {
        //     imagefilter($small, IMG_FILTER_GAUSSIAN_BLUR);
        // }
        
        // Upscale back
        $blurred = imagecreatetruecolor($width, $height);
        // imageantialias($blurred, true);
        imagecopyresampled($blurred, $small, 0, 0, 0, 0, $width, $height, $smallW, $smallH);

        // imagefilter($blurred, IMG_FILTER_GAUSSIAN_BLUR);
        // imagefilter($blurred, IMG_FILTER_GAUSSIAN_BLUR);
        // imagefilter($blurred, IMG_FILTER_GAUSSIAN_BLUR);

        // Apply blur multiple times for stronger effect
        // for ($i = 0; $i < 5; $i++) {
        //     imagefilter($background, IMG_FILTER_SELECTIVE_BLUR);
        // }
        // imageconvolution($background, [[1.0, 2.0, 1.0], [2.0, 4.0, 2.0], [1.0, 2.0, 1.0]], 16, 0);

        // Create new canvas
        $canvas = imagecreatetruecolor($width, $height);
        imagecopy($canvas, $blurred, 0, 0, 0, 0, $width, $height);
        
        // Resize original image to small thumbnail
        $thumbSize = min($width, $height) / 2;
        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
        // imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbSize, $thumbSize, $width, $height);
        
        // Center the thumbnail
        $thumbX = ($width - $thumbSize) / 2;
        $thumbY = ($height - $thumbSize) / 2;
        // imagecopy($canvas, $thumb, $thumbX, $thumbY, 0, 0, $thumbSize, $thumbSize);
        
        // Text settings
        $topText = "Sensitive\nContent";
        $bottomText = "Open source link\nto view original image.";
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 0, 0, 0);
        
        // Function to draw text with outline
        function drawTextWithOutline($img, $size, $outlineSize, $angle, $x, $y, $text, $font, $textColor, $outlineColor) {
            for ($ox = -$outlineSize; $ox <= $outlineSize; $ox++) {
                for ($oy = -$outlineSize; $oy <= $outlineSize; $oy++) {
                    imagettftext($img, $size, $angle, $x + $ox, $y + $oy, $outlineColor, $font, $text);
                }
            }
            imagettftext($img, $size, $angle, $x, $y, $textColor, $font, $text);
        }
        
        // Draw top text (larger)
        $topFontSize = 160;
        $topBox = imagettfbbox($topFontSize, 0, FONT_PATH, $topText);
        $topX = ($width - ($topBox[2] - $topBox[0])) / 2;
        $topY = $thumbY - 20;
        drawTextWithOutline($canvas, $topFontSize, 8, 0, $topX, $topY, $topText, FONT_PATH, $white, $black);
        
        // Draw bottom text (smaller)
        $bottomFontSize = 64;
        $bottomBox = imagettfbbox($bottomFontSize, 0, FONT_PATH, $bottomText);
        $bottomX = ($width - ($bottomBox[2] - $bottomBox[0])) / 2;
        $bottomY = $thumbY + $thumbSize + 40;
        drawTextWithOutline($canvas, $bottomFontSize, 4, 0, $bottomX, $bottomY, $bottomText, FONT_PATH, $white, $black);
        
        // Output the final image
        header('Content-Type: image/jpeg');
        imagejpeg($canvas);
        imagedestroy($image);
        imagedestroy($background);
        imagedestroy($thumb);
        imagedestroy($canvas);
    }
}