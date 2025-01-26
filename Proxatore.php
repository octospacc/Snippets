<?php
const APPNAME = '🎭️ Proxatore';

const PLATFORMS = [
	'facebook' => ['facebook.com', 'm.facebook.com'],
	'instagram' => ['instagram.com'],
    //'juxt' => ['juxt.pretendo.network'],
	'reddit' => ['old.reddit.com', 'reddit.com'],
    'spotify' => ['open.spotify.com'],
	'telegram' => ['t.me', 'telegram.me'],
	'tiktok' => ['tiktok.com'],
	'twitter' => ['twitter.com'],
	'x' => ['x.com'],
	'xiaohongshu' => ['xiaohongshu.com'],
	'youtube' => ['youtube.com', 'm.youtube.com'],
];

const PLATFORMS_ALIASES = [
	'x' => 'twitter',
];

const PLATFORMS_PROXIES = [
	'instagram' => ['ddinstagram.com', 'd.ddinstagram.com'],
	'tiktok' => ['vxtiktok.com'],
	'twitter' => ['fxtwitter.com', 'vxtwitter.com'],
	'x' => ['fixupx.com', 'stupidpenisx.com'],
];

const PLATFORMS_REDIRECTS = [
	'vm.tiktok.com' => 'tiktok',
    //'youtu.be' => 'youtube',
];

const PLATFORMS_HACKS = ['twitter', 'x'];

const PLATFORMS_ORDERED = ['telegram'];

const PLATFORMS_VIDEO = ['facebook', 'instagram'];

const PLATFORMS_PARAMS = [
    'facebook' => true,
    'xiaohongshu' => true,
    'youtube' => ['v'],
];

const EMBEDS = [
	'reddit' => ['embed.reddit.com'],
];

const EMBEDS_PREFIXES_SIMPLE = [
	'tiktok' => 'www.tiktok.com/embed/v3/',
	'twitter' => 'platform.twitter.com/embed/Tweet.html?id=',
];

const EMBEDS_PREFIXES_PARAMS = [
	'youtube' => 'www.youtube.com/embed/[v]',
];

const EMBEDS_SUFFIXES = [
	'instagram' => '/embed/captioned/',
	'telegram' => '?embed=1&mode=tme',
];

define('EMBEDS_PREFIXES_FULL', [
	'facebook' => 'www.facebook.com/plugins/post.php?href=' . urlencode('https://www.facebook.com/'),
]);

define('SCRIPT_NAME', /* $_SERVER['SCRIPT_NAME'] . */ '/');
define('HISTORY_FILE', './' . $_SERVER['SCRIPT_NAME'] . '.history.jsonl');

function lstrip($str, $sub) {
	return implode($sub, array_slice(explode($sub, $str), 1));
}

function urlLast($url) {
	return end(explode('/', trim(parse_url($url, PHP_URL_PATH), '/')));
}

function parseAbsoluteUrl($str) {
    $strlow = strtolower($str);
    if (str_starts_with($strlow, 'http://') || str_starts_with($strlow, 'https://')) {
        return implode('://', array_slice(explode('://', $str), 1));
    }
}

function redirectTo($internalUrl) {
	header('Location: ' . SCRIPT_NAME . $internalUrl);
	die();
}

function fetchContent($url, $redirects=-1) {
	$ch = curl_init();
	//$useragent = 'Mozilla/5.0 (X11; Linux x86_64; rv:129.0) Gecko/20100101 Firefox/129.0';
    //$useragent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0';
	$useragent = 'curl/' . curl_version()['version'];
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, $redirects);
	curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
	$body = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return [
		'body' => $body,
		'code' => $code,
		'url' => curl_getinfo($ch, CURLINFO_REDIRECT_URL),
	];
}

function makeCanonicalUrl($item) {
	if (!$item) {
		return NULL;
	}
	return 'https://' . (PLATFORMS[$item['platform']][0] ?: $item['platform']) . '/' . $item['relativeurl'];
}

function makeEmbedUrl($platform, $relativeUrl) {
    $url = NULL;
	if (isset(EMBEDS_PREFIXES_SIMPLE[$platform])) {
		$url = EMBEDS_PREFIXES_SIMPLE[$platform] . urlLast($relativeUrl);
	} else if (isset(EMBEDS_PREFIXES_PARAMS[$platform])) {
        $url = EMBEDS_PREFIXES_PARAMS[$platform];
        foreach (PLATFORMS_PARAMS[$platform] as $key) {
            parse_str(parse_url($relativeUrl, PHP_URL_QUERY), $params);
            $url = str_replace("[$key]", $params[$key], $url);
        }
	} else if (isset(EMBEDS_PREFIXES_FULL[$platform])) {
		$url = EMBEDS_PREFIXES_FULL[$platform] . urlencode($relativeUrl);
	} else {
		$url = (EMBEDS[$platform][0] ?: PLATFORMS[$platform][0] ?: PLATFORMS_PROXIES[$platform][0] ?: $platform) . '/' . trim($relativeUrl, '/') . (EMBEDS_SUFFIXES[$platform] ?? '');
	}
    return "https://{$url}";
//	switch ($platform) {
//		case 'tiktok':
//			return 'https://www.tiktok.com/embed/v3/' . urlLast($relativeUrl);
//		case 'twitter':
//			return 'https://platform.twitter.com/embed/Tweet.html?id=' . urlLast($relativeUrl);
//		default:
//			return 'https://' . (EMBEDS[$platform][0] ?: PLATFORMS_PROXIES[$platform][0] ?: PLATFORMS[$platform][0] ?: '') . '/' . $relativeUrl . (EMBEDS_SUFFIXES[$platform] ?? '');
//	}
}

function makeScrapeUrl($platform, $relativeUrl) {
	return 'https://' . ((in_array($platform, PLATFORMS_HACKS) ? (PLATFORMS_PROXIES[$platform][0] ?: PLATFORMS[$platform][0]) : PLATFORMS[$platform][0]) ?: $platform) . '/' . $relativeUrl;
}

function parseMetaTags($doc) {
	$metaTags = [];
	foreach ($doc->getElementsByTagName('meta') as $meta) {
		if ($meta->hasAttribute('name') || $meta->hasAttribute('property')) {
			$metaTags[$meta->getAttribute('name') ?: $meta->getAttribute('property')] = $meta->getAttribute('content');
		}
	}
	return $metaTags;
}

function loadHistory() {
	$history = [];
	if (file_exists(HISTORY_FILE)) {
		$lines = file(HISTORY_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			$history[] = json_decode($line, true);
		}
	}
	return $history;
}

function saveHistory($entry) {
	$history = loadHistory();
	$history = array_filter($history, function ($item) use ($entry) {
		return $item['platform'] !== $entry['platform'] || $item['relativeurl'] !== $entry['relativeurl'];
	});
	$history[] = $entry;
	$lines = array_map(fn($item) => json_encode($item, JSON_UNESCAPED_SLASHES), $history);
	file_put_contents(HISTORY_FILE, implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX);
}

function searchHistory($keyword) {
	$results = [];
	$history = loadHistory();
	foreach ($history as $entry) {
		if (stripos(json_encode($entry, JSON_UNESCAPED_SLASHES), $keyword) !== false) {
			$results[] = $entry;
		}
	}
	return $results;
}

$path = $_SERVER['REQUEST_URI'];//parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$immediateResult = null;

if (isset($_GET['proxatore-search']) && ($search = $_GET['proxatore-search']) !== '') {
    //if (str_starts_with(strtolower($search), 'https://')) {
    //    redirectTo(lstrip($search, 'https://'));
    if ($url = parseAbsoluteUrl($search)) {
        redirectTo($url);
    } else {
        $searchResults = searchHistory($search);
    }
} else {
    $path = trim($path, '/');
    if ($url = parseAbsoluteUrl($path)) {
        //$path = $url;
        redirectTo($url);
    }

    $segments = explode('/', $path);
    if (SCRIPT_NAME !== '/') {
        array_shift($segments);
    }

    $platform = null;
    $upstream = $segments[0] ?? null;
    $relativeUrl = implode('/', array_slice($segments, 1));

    if (($upstream === '__proxy__' || $upstream === '__media__') && $segments[1] === 'youtube') {
        if ($video = preg_replace("/[^A-Za-z0-9-_]/", '', substr($relativeUrl, -11))) {
            header("Location: " . shell_exec("yt-dlp -g '{$video}'"));
            die();
        }
    }

    if (isset(PLATFORMS[$upstream])) {
        if (isset(PLATFORMS_ALIASES[$upstream])) {
            redirectTo(PLATFORMS_ALIASES[$upstream] . '/' . $relativeUrl);
        }
        $platform = $upstream;
        $domain = PLATFORMS[$upstream][0];
    } else {
        foreach ([PLATFORMS_PROXIES, PLATFORMS, EMBEDS] as $array) {
            foreach ($array as $platform => $domains) {
                if (in_array($upstream, $domains) || in_array(lstrip($upstream, 'www.'), $domains)) {
                    redirectTo($platform . '/' . $relativeUrl);
                }
            }
            unset($platform);
        }
    }

    if (!$platform && isset(PLATFORMS_REDIRECTS[$upstream])) {
        // TODO: only strip query params for platforms that don't need them
        $relativeUrl = trim(parse_url(fetchContent("$upstream/$relativeUrl", 1)['url'], PHP_URL_PATH), '/');
        $platform = PLATFORMS_REDIRECTS[$upstream];
        redirectTo($platform . '/' . $relativeUrl);
    } else if (!$platform && (str_ends_with($upstream, '.wordpress.com') || str_ends_with($upstream, '.blogspot.com'))) {
        $platform = $upstream;
    }

    if ($relativeUrl && $platform && ($content = fetchContent(makeScrapeUrl($platform, $relativeUrl)))['body']) {
        http_response_code($content['code']);
        // if (!in_array($platform, PLATFORMS_TRACKING)) {
        //     $relativeUrl = parse_url($relativeUrl, PHP_URL_PATH);
        // }
        if (isset(PLATFORMS_PARAMS[$platform])) {
            if (PLATFORMS_PARAMS[$platform] !== true) {
                parse_str(parse_url($relativeUrl, PHP_URL_QUERY), $params);
                $relativeUrl = parse_url($relativeUrl, PHP_URL_PATH) . '?';
                foreach ($params as $key => $value) {
                    if (in_array($key, PLATFORMS_PARAMS[$platform])) {
                        $relativeUrl .= "{$key}={$value}&";
                    }
                }
            }
        } else {
            $relativeUrl = parse_url($relativeUrl, PHP_URL_PATH);
        }
        $doc = new DOMDocument();
        $doc->loadHTML($content['body']);
        $metaTags = parseMetaTags($doc);
        $immediateResult = [
            'platform' => $platform,
            'relativeurl' => $relativeUrl,
            //'datetime' => date('Y-m-d H:i:s'),
            //'request_time' => time(),
            'locale' => $metaTags['og:locale'] ?? '',
            'type' => $metaTags['og:type'] ?? '',
            'image' => $metaTags['og:image'] ?? '',
            'video' => $metaTags['og:video'] ?: $metaTags['og:video:url'] ?: '',
            'videotype' => $metaTags['og:video:type'] ?? '',
            'title' => $metaTags['og:title'] ?: $metaTags['og:title'] ?: '',
            //'author' => $metaTags['og:site_name'] ?? '',
            'description' => $metaTags['og:description'] ?: $metaTags['description'] ?: '',
            'images' => [],
        ];
        //if ((in_array($platform, PLATFORMS_VIDEO) && !$immediateResult['video']) || !$immediateResult['image']) {
            $html = fetchContent(makeEmbedUrl($platform, $relativeUrl))['body'];
            if (!$immediateResult['video'] && ($vidpos = strpos($html, '.mp4'))) {
                //$startpos = 0;//strpos(strrev(substr($html, 0, $vidpos)), '"');
                $endpos = strpos($html, '"', $vidpos); //strpos(substr($html, $vidpos), '"');
                $vidstr = substr($html, 0, $endpos);
                //echo $vidstr;
                $startpos = $endpos - strpos(strrev($vidstr), '"');
                $vidstr = substr($html, $startpos, $endpos-$startpos+1);
                //echo '|' . $vidpos . '|' . $startpos . '|' . $endpos; //substr($html, $startpos, $endpos);
                $vidstr = html_entity_decode($vidstr);
                //$vidstr = json_decode('"' . json_decode('"' . ($vidstr) . '"') . '');
                $vidstr = json_decode('"' . json_decode('"' . $vidstr . '"')) ?: json_decode('"' . json_decode('"' . $vidstr) . '"');
                //$vidstr = json_decode('"' . $vidstr . '"');
                //echo $vidstr;
                $immediateResult['video'] = $vidstr;
                //echo '|'.$startpos.'|'.$endpos.'|';
            }
            //if (!$immediateResult['image']) {
                $doc->loadHTML($html);
                foreach ($doc->getElementsByTagName('img') as $img) {
                    array_push($immediateResult['images'], $img->getAttribute('src'));
                }
                if (sizeof($immediateResult['images'])) {
                    //$immediateResult['image'] = $imgs[0];
                }
            //}
        //}
        //if ($immediateResult['title'] || $immediateResult['description']) {
        //    saveHistory($immediateResult);
        //} else 
        if ($content['code'] >= 400) {
            $searchResults = searchHistory(json_encode([
                'platform' => $platform,
                'relativeurl' => $relativeUrl,
            ], JSON_UNESCAPED_SLASHES));//('"platform":"' . $platform . '","relativeurl":"' . $relativeUrl . '"');
            if (sizeof($searchResults)) {
                $immediateResult = $searchResults[0];
            }
        } else {
            saveHistory($immediateResult);
        }
        $immediateResult['description'] = preg_replace('/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/', '<a href="$0" target="_blank" rel="noopener nofollow" title="$0">$0</a>', $immediateResult['description']);
        $searchResults = [$immediateResult];
    } else {
        http_response_code(404);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= APPNAME ?></title>
<meta name="description" content="<?= htmlspecialchars($immediateResult['description'] ?? 'Content Proxy for viewing media and text from various platforms.') ?>" />
<meta property="og:title" content="<?= htmlspecialchars($immediateResult['title'] ?? APPNAME) ?>" />
<meta property="og:description" content="<?= htmlspecialchars($immediateResult['description'] ?? 'View content from supported platforms.') ?>" />
<!--<meta property="og:locale" content="<?= htmlspecialchars($immediateResult['locale'] ?? '') ?>" />-->
<meta property="og:type" content="<?= htmlspecialchars($immediateResult['type'] ?? '') ?>" />
<meta property="og:image" content="<?= htmlspecialchars($immediateResult['image'] ?? '') ?>" />
<?php if ($immediateResult['video']): ?>
<meta property="og:video" content="<?= htmlspecialchars($immediateResult['video']) ?>" />
<meta property="og:video:type" content="<?= htmlspecialchars($immediateResult['videotype'] ?: 'video/mp4') ?>" />
<?php endif; ?>
<meta property="og:site_name" content="<?= APPNAME . ' ' . $immediateResult['platform'] ?>" />
<meta property="og:url" content="<?= htmlspecialchars(makeCanonicalUrl($immediateResult)) ?>" />
<link rel="canonical" href="<?= htmlspecialchars(makeCanonicalUrl($immediateResult)) ?>" />
<!--<link rel="alternate" type="application/json+oembed" href="" />
<link rel="alternate" type="application/xml+oembed" href="" />-->
<style>
* {
    box-sizing: border-box;
}
body {
    font-family: 'Roboto', Arial, sans-serif;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    background-color: #f0f2f5;
    color: #1c1e21;
}
iframe {
    width: 100%;
    height: 90vh;
    border: none;
}
.container {
    max-width: 900px;
    width: 90%;
    margin: 20px;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}
a.button {
    padding: 0.5em;
    border: 1px solid gray;
    border-radius: 8px;
    text-decoration: none;
    margin: 0.5em;
    display: inline-block;
}
a.button.block {
    display: block;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
}
a.button.block code {
    text-decoration: underline;
}
h1, h1 a {
    text-align: center;
    margin-bottom: 20px;
    font-size: 2rem;
    color: #1877f2;
    text-decoration: none;
}
h2 {
    font-size: 1.5rem;
    margin-top: 20px;
    color: #444;
    border-bottom: 2px solid #1877f2;
    padding-bottom: 5px;
}
.history-item {
    display: flex;
    align-items: center;
    border-bottom: 1px solid #e6e6e6;
    padding: 15px 0;
    transition: background-color 0.3s;
}
.history-item:hover {
    background-color: #f9f9f9;
}
.history-item img, .history-item video {
    /*width: 49%;
    max-width: 49%;*/
    width: 100%;
    max-width: 100%;
    /* max-width: 100px;
    max-height: 100px; */
    margin-right: 15px;
    border-radius: 4px;
    object-fit: cover;
}
.history-item div {
    /*display: flex;*/
    flex-direction: column;
    justify-content: center;
    max-width: 49%;
    width: 49%;
    /*padding: 1em;*/
}
img, video {
    padding: 1em;
}
img[src=""], video[src=""] {
    display: none;
}
img + img,
video:not(video[src=""]) + img {
    max-width: 45% !important;
}
.history-item strong {
    font-size: 1.2rem;
    color: #1c1e21;
    margin-bottom: 5px;
    display: -webkit-box;
}
.history-item.ellipsize strong {
    -webkit-line-clamp: 5;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.history-item small {
    font-size: 0.9rem;
    color: #606770;
}
.history-item .title {
    display: none;
}
.search-bar {
    margin-bottom: 20px;
    display: flex;
    justify-content: center;
}
.search-bar input {
    flex: 1;
    max-width: 600px;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 25px;
    font-size: 1rem;
    transition: box-shadow 0.3s, border-color 0.3s;
}
.search-bar input:focus {
    border-color: #1877f2;
    box-shadow: 0 0 5px rgba(24, 119, 242, 0.5);
    outline: none;
}
.search-bar button {
    margin-left: 10px;
    padding: 10px 20px;
    background-color: #1877f2;
    color: white;
    border: none;
    border-radius: 25px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.3s;
}
.search-bar button:hover {
    background-color: #155dbb;
}
@media (max-width: 600px) {
    .search-bar input {
        width: 100%;
        margin-bottom: 10px;
    }
    .search-bar {
        flex-direction: column;
    }
    .search-bar button {
        width: 100%;
        margin: 0;
    }
    .history-item {
        flex-direction: column;
        align-items: flex-start;
    }
    .history-item img {
        margin-bottom: 10px;
        max-width: 100%;
    }
    .history-item div {
        max-width: 100%;
        width: 100%;
    }
    .history-item .title {
        display: block;
    }
}
</style>
</head>
<body>
    <div class="container">
        <h1><a href="<?= SCRIPT_NAME ?>"><?php echo APPNAME; ?></a></h1>
        <form class="search-bar" method="get" action="<?= SCRIPT_NAME ?>">
            <input type="text" required="required" name="proxatore-search" placeholder="Search or Input URL" value="<?= htmlspecialchars($_GET['proxatore-search'] ?: makeCanonicalUrl($immediateResult) ?: '') ?>">
            <button type="submit">Go 💣️</button>
        </form>
        <?php if (!isset($searchResults)) {
            //$platforms = '';
            echo '<p>Supported Platforms:</p><ul>';
            foreach (PLATFORMS as $platform => $_) {
                echo ((isset(PLATFORMS_ALIASES[$platform])) ? "/" : "</li><li>") . $platform;
                //$platforms .= ((isset(PLATFORMS_ALIASES[$platform])) ? "/" : "</li><li>") . $platform;
            }
            //echo trim(trim($platforms, '</li'), '>') . 
            echo '</li></ul><p>Source Code: <a href="https://hlb0.octt.eu.org/Drive/Misc/Scripts/Proxatore.php">Proxatore.php</a></p>';
        } ?>
        <?php if (isset($searchResults)): ?>
            <?php foreach ($searchResults as $item): ?>
                <div class="history-item <?php
                    similar_text($item['title'], $item['description'], $percent);
                    if ($percent > 90) echo 'ellipsize';
                ?>">
                    <p class="title">
                        <strong><?= htmlspecialchars($item['title']) ?></strong>
                        <small><?= htmlspecialchars($item['platform']) ?><!-- | <?= htmlspecialchars($item['datetime']) ?>--></small>
                    </p>
                    <div style="text-align: center;">
                        <?php if (/*$item['video'] && $item['videotype'] !== 'text/html'*/true): ?>
                            <video src="<?= htmlspecialchars($item['platform'] === 'youtube' ? (SCRIPT_NAME . '__proxy__/youtube/' . $immediateResult['video']) : ($item['video'] ?? '')) ?>" controls="controls"></video>
                        <?php endif; ?>
                        <img src="<?= htmlspecialchars($item['image'] ?? '') ?>" />
                        <?php foreach ($item['images'] as $image): ?>
                            <img src="<?= htmlspecialchars($image ?? '') ?>" onerror="this.hidden=true" />
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <p>
                            <strong><?= htmlspecialchars($item['title']) ?></strong>
                            <small><?= htmlspecialchars($item['platform']) ?><!-- | <?= htmlspecialchars($item['datetime']) ?>--></small>
                        </p>
                        <p style="white-space: preserve-breaks; border-left: 2px solid black; padding: 1em; word-break: break-word;"><?= /*htmlspecialchars*/($item['description']) ?></p>
                        <p>
                            <a class="button block" href="<?= htmlspecialchars(makeCanonicalUrl($item)) ?>" target="_blank" rel="noopener nofollow">Original on <code><?= htmlspecialchars(PLATFORMS[$item['platform']][0] ?: $item['platform']) ?>/<?= htmlspecialchars($item['relativeurl']) ?></code></a>
                            <a class="button block" href="<?= htmlspecialchars(SCRIPT_NAME . $item['platform'] . '/' . $item['relativeurl']) ?>"><?= APPNAME ?> Permalink</a>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if (isset($immediateResult)): ?>
            <?php if (in_array($immediateResult['platform'], PLATFORMS_ORDERED)): ?>
                <div>
                    <a class="button" href="<?= abs(end(explode('/', $immediateResult['relativeurl']))-1) ?>">⬅️ Previous</a>
                    <a class="button" style="float:right;" href="<?= end(explode('/', $immediateResult['relativeurl']))+1 ?>">➡️ Next</a>
                </div>
            <?php endif; ?>
            <iframe src="<?= htmlspecialchars(makeEmbedUrl($immediateResult['platform'], $immediateResult['relativeurl'])) ?>"></iframe>
        <?php endif; ?>
    </div>
</body>
</html>
