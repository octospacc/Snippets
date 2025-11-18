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
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

// ========================= Configuration ========================= //
const INSTANCE = 'https://shark.octt.eu.org'; // Cambia con la tua istanza
const NOTES_LIMIT = 5;

// <https://github.com/fastvolt/markdown>
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
    // $markdown = Markdown::new();

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
    
    // Funzione per convertire MFM in HTML (semplificata)
    // function mfmToHtml($text) {
    //     $text = htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    //     $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
    //     $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
    //     $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2">$1</a>', $text);
    //     $text = nl2br($text);
    //     return $text;
    // }
    function mfmToHtml(string $input): string {
        $input = parseMFM($input);
        if (filter_var($_GET['markdown'] ?? '', FILTER_VALIDATE_BOOLEAN)) return $input;
    	//global $markdown;
    	//return $input;
    	//$text = parseMFM($text);
    	//for ($i=0; i<strlen($raw); $i++) {
    		// if (str_ends_with($text, '$[')) {
    		// 	while ($raw[$i])
    		// } else 
    	//	$text .= $raw[$i];
    	//}
        /* 
        $lines = explode("\n", parseMFM($text));
        for ($i=0; $i<sizeof($lines); $i++) {
            $line = $lines[$i];
            $ltrim = ltrim($line);
            if (str_starts_with($line, '#')) {
                $lines[$i] = ". $line";
            } elseif (str_starts_with($ltrim, '>') && $line !== $ltrim) {
                $lines[$i] = substr($line, 0, strlen($line) - strlen($ltrim)) . "\\$ltrim";
            }
            
        }
        */
    	// return Markdown::new()->setContent(implode("\n", $lines))->toHtml();
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
        // return $text;
    }
    
function parseMFM(string $input, int &$pos = 0): string {
    $output = '';
    $length = strlen($input);

    while ($pos < $length) {
        $inblock = false;
        // Detect start of MFM block
        if ($input[$pos] === '$' && $pos + 1 < $length && $input[$pos + 1] === '[') {
            $inblock = true;
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
            $output .= parseMFM($input, $pos);
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
    
    header('Content-Type: application/atom+xml; charset=utf-8');

    // Inizio del feed Atom
    echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    ?>
    <feed xmlns="http://www.w3.org/2005/Atom">
      <title>Note utente Misskey <?= $userId ?></title>
      <link href="<?= htmlspecialchars(selfPrefix() . $_SERVER['REQUEST_URI']) ?>" rel="self" />
      <link href="<?= htmlspecialchars(INSTANCE . '/users/' . $userId) ?>" rel="alternate" type="text/html" />
      <updated><?= date(DATE_ATOM) ?></updated>
      <id>tag:<?= parse_url(INSTANCE, PHP_URL_HOST) ?>,<?= date('Y-m-d') ?>:/feed/<?= htmlspecialchars($userId) ?></id>
    
    <?php foreach ($notes as $note): ?>
      <entry>
        <title><?= htmlspecialchars(substr(strip_tags($note['text'] ?? 'Nota senza testo'), 0, 50)) ?>...</title>
        <link href="<?= INSTANCE ?>/notes/<?= $note['id'] ?>"/>
        <id>tag:<?= parse_url(INSTANCE, PHP_URL_HOST) ?>,<?= substr($note['createdAt'], 0, 10) ?>:/notes/<?= $note['id'] ?></id>
        <updated><?= date(DATE_ATOM, strtotime($note['createdAt'])) ?></updated>
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
                    // if (str_ends_with(strtolower($url), '.webp')) {
                        // $url = preg_replace('/\.webp$/i', '.jpg', $url);
                        $url = selfPrefix() . $_SERVER['DOCUMENT_URI'] . '?media=' . end(explode('/', $url));
                    }
                  ?>
                  <div><img src="<?= htmlspecialchars($url) ?>" alt="media allegato" style="max-width:100%"/></div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          ]]>
        </content>
        <author>
          <name><?= htmlspecialchars($note['user']['name'] ?? $note['user']['username']) ?></name>
        </author>
      </entry>
    <?php endforeach; ?>
    </feed>
<?php }

// ----------------------------
// Funzione: proxy e conversione immagine
function proxyAndConvertMedia($fileId) {
    $url = INSTANCE . '/files/' . $fileId;
    // $ext = strtolower(pathinfo(parse_url($fileId, PHP_URL_PATH), PATHINFO_EXTENSION));

    // // Se è già JPEG, inoltra direttamente
    // if ($ext === 'jpg' || $ext === 'jpeg') {
    //     header('Content-Type: image/jpeg');
    //     header("Location: $url");
    //     return;
    // }

    // Scarica il file
    $imageData = file_get_contents($url);
    if (!$imageData) {
        http_response_code(500);
        echo 'Errore nel download del file.';
        return;
    }

    // // Se è già JPEG, inoltra direttamente
    // if ($ext === 'jpg' || $ext === 'jpeg') {
    //     header('Content-Type: image/jpeg');
    //     echo $imageData;
    //     return;
    // }

    // Altrimenti, converti in JPEG
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