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

// ========================= Configuration ========================= //
const INSTANCE = 'https://shark.octt.eu.org'; // Cambia con la tua istanza
const NOTES_LIMIT = 5;
// ================================================================= //

require 'Res/FastVoltMarkdown.php';
use FastVolt\Helper\Markdown;

// Se è presente il parametro "media", esegui il proxy con conversione
if (isset($_GET['media'])) {
    proxyAndConvertMedia($_GET['media']);
    exit;
}

// Se è presente "userId", genera il feed Atom
if (isset($_GET['userId'])) {
    generateAtomFeed($_GET['userId']);
    exit;
}

// Altrimenti, errore
http_response_code(400);
echo 'Errore: specificare almeno "userId" o "media".';
exit;

function selfPrefix(): string {
    return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
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
    $notes = apiPost('/api/users/notes', [
        'userId' => $userId,
        'withRenotes' => false,
        'withReplies' => false,
        'withChannelNotes' => false,
        'withFiles' => false,
        'allowPartial' => true,
        'limit' => NOTES_LIMIT,
    ]);

    if (!is_array($notes)) {
        http_response_code(500);
        echo 'Errore nel recupero delle note.';
        exit;
    }
    
    function mfmToHtml(string $input): string {
        $input = parseMFM($input);
        if (filter_var($_GET['markdown'] ?? '', FILTER_VALIDATE_BOOLEAN)) return $input;

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
            $output .= "$line\n";
            if (!$patchedquote && !$inblock) {
                // the parser won't support Markdown natural-linebreak mode, so add \n's
                $output .= "\n";
            }
        }
        return Markdown::new()->setContent($output)->toHtml();
    }

    function parseMFM(string $input, int &$pos = 0, bool $inblock = false): string {
        $output = '';
        $length = strlen($input);
        // $inblock = false;
        while ($pos < $length) {
            // Detect start of MFM block
            if ($input[$pos] === '$' && $pos + 1 < $length && $input[$pos + 1] === '[') {
                // $inblock = true;
                $pos += 2; // Skip "$["
                // Skip feature name
                while ($pos < $length && preg_match('/\w/', $input[$pos])) {
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
                // $inblock = false;
                return $output;
            } else {
                $output .= $input[$pos];
                $pos++;
            }
        }
        return $output;
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
    
    <?php foreach ($notes as $note): ?>
      <entry>
        <title><?= htmlspecialchars(substr(strip_tags($note['text'] ?? 'Nota senza testo'), 0, 50)) ?>...</title>
        <link href="<?= INSTANCE ?>/notes/<?= $note['id'] ?>" />
        <id>tag:<?= parse_url(INSTANCE, PHP_URL_HOST) ?>,<?= substr($note['createdAt'], 0, 10) ?>:/notes/<?= $note['id'] ?></id>
        <updated><?= date(DATE_ATOM, strtotime($note['updatedAt'] ?? $note['createdAt'])) ?></updated>
        <published><?= date(DATE_ATOM, strtotime($note['createdAt'])) ?></published>
        <content type="html">
          <![CDATA[
            <?= mfmToHtml($note['text'] ?? '') ?>
            <?php if (!empty($note['files'])): ?>
              <div class="attachments">
                <?php foreach ($note['files'] as $file): ?>
                  <?php
                    $url = $file['url'];
                    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
                    if ($ext === 'jpg' || $ext === 'jpeg' || $ext === 'webp' || $ext === 'png') {
                        $url = selfPrefix() . $_SERVER['DOCUMENT_URI'] . '?media=' . end(explode('/', $url));
                    }
                  ?>
                  <div><img src="<?= htmlspecialchars($url) ?>" alt="<?= htmlspecialchars($file['comment']) ?>" style="max-width:100%" /></div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          ]]>
        </content>
        <author>
          <name><?= htmlspecialchars($note['user']['name'] ?? $note['user']['username']) ?></name>
          <uri><?= htmlspecialchars(INSTANCE . '/users/' . $note['user']['id']) ?></uri>
        </author>
        <mk:replyId><?= $note['replyId'] ?></mk:replyId>
        <mk:renoteCount><?= $note['renoteCount'] ?></mk:renoteCount>
        <mk:repliesCount><?= $note['repliesCount'] ?></mk:repliesCount>
        <mk:reactionCount><?= $note['reactionCount'] ?></mk:reactionCount>
      </entry>
    <?php endforeach; ?>
    </feed>
<?php }

function proxyAndConvertMedia($fileId) {
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

    header('Content-Type: image/jpeg');
    imagejpeg($image, null, 90);
    imagedestroy($image);
}