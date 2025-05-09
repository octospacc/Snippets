<?php
const APPNAME = '🎭️ Proxatore';
const COBALT_API = 'http://192.168.1.125:9010/';

const OPTIONS_DEFAULTS = [
    'embedfirst' => false,
    'history' => true,
    'htmlmedia' => false,
    'relativemedia' => false,
    'mediaproxy' => false,
];

const OPTIONS_OVERRIDES = [
    'bbs.spacc.eu.org' => [
        'embedfirst' => true,
    ],
];

const PLATFORMS = [
	'spaccbbs' => ['bbs.spacc.eu.org'],
    'bluesky' => ['bsky.app'],
	'facebook' => ['facebook.com', 'm.facebook.com'],
	'instagram' => ['instagram.com'],
	//'juxt' => ['juxt.pretendo.network'],
	'reddit' => ['old.reddit.com', 'reddit.com'],
	'spotify' => ['open.spotify.com'],
	'telegram' => ['t.me', 'telegram.me'],
    'threads' => ['threads.net', 'threads.com'],
	'tiktok' => ['tiktok.com'],
	'twitter' => ['twitter.com'],
	'x' => ['x.com'],
	'xiaohongshu' => ['xiaohongshu.com'],
	'youtube' => ['youtube.com', 'm.youtube.com'],
];

const PLATFORMS_USERSITES = ['altervista.org', 'blogspot.com', 'wordpress.com'];

const PLATFORMS_ALIASES = [
	'x' => 'twitter',
];

const PLATFORMS_PROXIES = [
    'bluesky' => ['fxbsky.app'],
	'instagram' => ['ddinstagram.com', 'd.ddinstagram.com', 'kkinstagram.com'],
    'threads' => ['vxthreads.net'],
	'tiktok' => ['vxtiktok.com'],
	'twitter' => ['fxtwitter.com', 'vxtwitter.com', 'fixvx.com'],
	'x' => ['fixupx.com', 'girlcockx.com', 'stupidpenisx.com'],
];

const PLATFORMS_REDIRECTS = [
	'vm.tiktok.com' => 'tiktok',
	//'youtu.be' => 'youtube',
];

const PLATFORMS_API = [
    'tiktok' => ['https://www.tiktok.com/player/api/v1/items?item_ids=', [
        'description' => "['items'][0]['desc']",
        'video' => "['items'][0]['video_info']['url_list'][0]",
    ]],
];

const PLATFORMS_COBALT = ['instagram'];

const PLATFORMS_FAKE404 = ['telegram'];

const PLATFORMS_HACKS = ['bluesky', 'threads', 'twitter', 'x'];

const PLATFORMS_ORDERED = ['telegram'];

//const PLATFORMS_VIDEO = ['facebook', 'instagram'];

const PLATFORMS_NOIMAGES = ['altervista.org', 'wordpress.com'];

const PLATFORMS_PARAMS = [
    'facebook' => true,
    'xiaohongshu' => true,
    'youtube' => ['v'],
];

const EMBEDS = [
	'spotify' => ['open.spotify.com/embed/'],
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

define('APP_SLUG', explode(' ', APPNAME)[1]);
define('SCRIPT_NAME', /* $_SERVER['SCRIPT_NAME'] . */ '/');
define('HISTORY_FILE', './' . APP_SLUG . '.history.jsonl');

function normalizePlatform(string $platform): string {
    if (str_contains($platform, '.')) {
        $platform = lstrip($platform, '.', -2); //implode('.', array_slice(explode('.', $platform), -2));
    }
    return $platform;
}

function inPlatformArray(string $platform, array $array): bool {
    return in_array(normalizePlatform($platform), $array);
}

function platformMapGet(string $platform, array $array): mixed {
    return $array[normalizePlatform($platform)] ?? null;
}

function lstrip(string $str, string $sub, int $num): string {
	return implode($sub, array_slice(explode($sub, $str), $num));
}

function urlLast(string $url): string {
	return end(explode('/', trim(parse_url($url, PHP_URL_PATH), '/')));
}

function parseAbsoluteUrl(string $str) {
    $strlow = strtolower($str);
    if (str_starts_with($strlow, 'http://') || str_starts_with($strlow, 'https://')) {
        return implode('://', array_slice(explode('://', $str), 1));
    }
}

function redirectTo($url): void {
    if (!($absolute = parseAbsoluteUrl($url)) && !readProxatoreParam('history') /* && !(str_contains($url, '?proxatore-history=false') || str_contains($url, '&proxatore-history=false')) */) {
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        if (!isset($params['proxatore-history'])) {
            $url = $url . (str_contains($url, '?') ? '&' : '?') . 'proxatore-history=false';
        }
    }
	header('Location: ' . ($absolute ? '' : SCRIPT_NAME) . $url);
	die();
}

function fetchContent(string $url, int $redirects=-1): array {
	$ch = curl_init();
	//$useragent = 'Mozilla/5.0 (X11; Linux x86_64; rv:129.0) Gecko/20100101 Firefox/129.0';
    //$useragent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko/20100101 Firefox/134.0';
	$useragent = 'curl/' . curl_version()['version']; // format the UA like curl CLI otherwise some sites can't behave
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, $redirects);
	curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
	$data = [
		'body' => curl_exec($ch),
		'code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
		'url' => curl_getinfo($ch, CURLINFO_REDIRECT_URL) ?: curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
        // 'error' => curl_error($ch),
	];
	curl_close($ch);
	return $data;
}

function makeCanonicalUrl(array|null $item): string|null {
    return ($item
        ? ('https://' . (PLATFORMS[$item['platform']][0] ?: $item['platform']) . '/' . $item['relativeurl'])
        : null);
}

function makeEmbedUrl(string $platform, string $relativeUrl): string {
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

function makeScrapeUrl(string $platform, string $relativeUrl): string {
	return 'https://' . ((inPlatformArray($platform, PLATFORMS_HACKS)
        ? (PLATFORMS_PROXIES[$platform][0] ?: PLATFORMS[$platform][0])
        : PLATFORMS[$platform][0]
    ) ?: $platform) . '/' . $relativeUrl;
}

function getHtmlAttributes(DOMDocument|string $doc, string $tag, string $attr): array {
    if (is_string($doc)) {
        $doc = htmldom($doc);
    }
    $list = [];
    foreach ($doc->getElementsByTagName($tag) as $el) {
        $list[] = $el->getAttribute($attr);
    }
    return $list;
}

function parseMetaTags(DOMDocument $doc): array {
	$tags = [];
	foreach ($doc->getElementsByTagName('meta') as $meta) {
		if ($meta->hasAttribute('name') || $meta->hasAttribute('property')) {
			$tags[$meta->getAttribute('name') ?: $meta->getAttribute('property')] = $meta->getAttribute('content');
		}
	}
	return $tags;
}

function loadHistory(): array {
	$history = [];
	if (file_exists(HISTORY_FILE)) {
		$lines = file(HISTORY_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			$history[] = json_decode($line, true);
		}
	}
	return $history;
}

function saveHistory($entry): void {
    if (inPlatformArray($entry['platform'], PLATFORMS_FAKE404)) {
        $history = searchExactHistory($entry['platform'], implode('/', array_slice(explode('/', $entry['relativeurl']), -1)));
        if (sizeof($history)) {
            unset($history[0]['relativeurl']);
            unset($entry['relativeurl']);
            if (json_encode($history[0], JSON_UNESCAPED_SLASHES) === json_encode($entry, JSON_UNESCAPED_SLASHES)) {
                return;
            } else {
                // TODO update cache of main page
            }
        } else {
            // TODO update cache of main page
        }
    }
	$history = loadHistory();
	$history = array_filter($history, function ($item) use ($entry) {
		return (($item['platform'] !== $entry['platform']) || ($item['relativeurl'] !== $entry['relativeurl']));
	});
	$history[] = $entry;
	$lines = array_map(fn($item) => json_encode($item, JSON_UNESCAPED_SLASHES), $history);
	file_put_contents(HISTORY_FILE, implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX);
}

function searchHistory(string $query): array {
	$results = [];
    $fake404 = [];
	foreach (loadHistory() as $entry) {
		if (stripos(json_encode($entry, JSON_UNESCAPED_SLASHES), $query) !== false) {
            if (inPlatformArray($entry['platform'], PLATFORMS_FAKE404)) {
                $entry2 = $entry;
                unset($entry2['relativeurl']);
                foreach ($fake404 as $item) {
                    if (json_encode($entry2, JSON_UNESCAPED_SLASHES) === json_encode($item, JSON_UNESCAPED_SLASHES)) {
                        goto skip;
                    }
                }
                $fake404[] = $entry2;
            }
			$results[] = $entry;
            skip:
		}
	}
	return $results;
}

function searchExactHistory(string $platform, string $relativeUrl): array {
    return searchHistory(json_encode([
        'platform' => $platform,
        'relativeurl' => $relativeUrl,
    ], JSON_UNESCAPED_SLASHES));
}

function htmldom(string $body): DOMDocument {
    $doc = new DOMDocument();
    $doc->loadHTML(mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8'));
    return $doc;
}

function getAnyVideoUrl(string $txt) {
    if ($vidpos = (strpos($txt, '.mp4?') ?? strpos($txt, '.mp4'))) {
        $endpos = strpos($txt, '"', $vidpos);
        $vidstr = substr($txt, 0, $endpos);
        $startpos = $endpos - strpos(strrev($vidstr), '"');
        $vidstr = substr($txt, $startpos, $endpos-$startpos+1);
        $vidstr = html_entity_decode($vidstr);
        $vidstr = json_decode('"' . json_decode('"' . $vidstr . '"')) ?: json_decode('"' . json_decode('"' . $vidstr) . '"');
        return $vidstr;
    }
}

function makeResultObject(string $platform, string $relativeUrl, array $metaTags): array {
    return [
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
}

function makeParamsRelativeUrl(string $platform, string $url): string {
    parse_str(parse_url($url, PHP_URL_QUERY), $params);
    $url = parse_url($url, PHP_URL_PATH) . '?';
    foreach ($params as $key => $value) {
        if (in_array($key, PLATFORMS_PARAMS[$platform])) {
            $url .= "{$key}={$value}&";
        }
    }
    return rtrim($url, '?&');
}

function readBoolParam(string $key, $default=null, $array=null) {
    if (!$array) {
        $array = $_GET;
    }
    $value = $array[$key] ?? null;
    if ($value && $value !== '') {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    } else {
        return $default;
    }
}

function readProxatoreParam(string $key, $array=null) {
    return readBoolParam("proxatore-{$key}", OPTIONS_DEFAULTS[$key], $array);
    // TODO handle domain HTTP referer overrides
}

function getPageData($platform, $relativeUrl) {
    if ($platform && $relativeUrl && ($data = fetchContent(makeScrapeUrl($platform, $relativeUrl)))['body']) {
        // if (!in_array($platform, PLATFORMS_TRACKING)) {
        //     $relativeUrl = parse_url($relativeUrl, PHP_URL_PATH);
        // }
        if (isset(PLATFORMS_PARAMS[$platform])) {
            if (PLATFORMS_PARAMS[$platform] !== true) {
                $relativeUrl = makeParamsRelativeUrl($platform, $relativeUrl);
            }
        } else {
            $relativeUrl = parse_url($relativeUrl, PHP_URL_PATH);
        }
        $query = parse_url($data['url'], PHP_URL_QUERY);
        //$relativeUrl = substr((parse_url($data['url'], PHP_URL_PATH) . ($query ? "?{$query}" : '')), 1);
        $data['doc'] = htmldom($data['body']);
        $data['result'] = makeResultObject($platform, $relativeUrl, parseMetaTags($data['doc']));
        return $data;
    }
}

function getCobaltVideo(string $url) {
    $cobaltData = json_decode(file_get_contents(COBALT_API, false, stream_context_create(['http' => [
        'header' => [
            'Accept: application/json',
            'Content-Type: application/json',
        ],
        'method' => 'POST',
        'content' => json_encode(['url' => $url]),
    ]])));
    if ($cobaltData->status === 'redirect' && strpos($cobaltData->url, '.mp4')) {
        return $cobaltData->url;
    }
}

function fetchPageMedia(string $url, array &$result): void {
    $platform = $result['platform'];
    $relativeUrl = $result['relativeurl'];
    //if ((in_array($platform, PLATFORMS_VIDEO) && !$immediateResult['video']) || !$immediateResult['image']) {
    if ($api = platformMapGet($platform, PLATFORMS_API)) {
        $data = json_decode(fetchContent($api[0] . urlLast($relativeUrl))['body'], true);
        $values = [];
        foreach ($api[1] as $key => $query) {
            $values[$key] = eval("return \$data{$query};");
        }
        $result = array_merge($result, $values);
    } else {
        $cobaltVideo = null;
        if (COBALT_API && inPlatformArray($platform, PLATFORMS_COBALT)) {
            $cobaltVideo = getCobaltVideo($url);
        }
        $html = fetchContent(makeEmbedUrl($platform, $relativeUrl))['body'];
        if (!$result['video']) {
            $result['video'] = $cobaltVideo ?? getAnyVideoUrl($html);
        }
        if (!inPlatformArray($platform, PLATFORMS_NOIMAGES) /* !$immediateResult['image'] */) {
            $result['images'] = getHtmlAttributes($html, 'img', 'src');
            // if (sizeof($immediateResult['images'])) {
            //     //$immediateResult['image'] = $imgs[0];
            // }
        }
    }
}

function getYoutubeStreamUrl(string $relativeUrl): string {
    if ($video = preg_replace("/[^A-Za-z0-9-_]/", '', escapeshellarg(substr($relativeUrl, -11)))) {
        return trim(shell_exec("yt-dlp -g '{$video}'"));
    }
}

// TODO: redesign the endpoint names, they're kind of a mess
function handleApiRequest(array $segments): void {
	$api = substr($segments[0], 2, -2);
    $platform = $segments[1];
    $relativeUrl = implode('/', array_slice($segments, 2));
    if (($api === 'proxy' || $api === 'media')) {
        if ($platform === 'youtube') {
            header('Location: ' . getYoutubeStreamUrl($relativeUrl));
        } else if ($api === 'media' && end($segments) === '0') {
            $relativeUrl = substr($relativeUrl, 0, -2);
            $data = getPageData($platform, $relativeUrl)['result'];
            if ($url = ($data['video'] ?: $data['image'])) {
                header('Location: ' . $url);
            }
        }
    } else if ($api === 'fileproxy' && $platform === 'youtube') {
        header('Content-Type: video/mp4');
        readfile(getYoutubeStreamUrl($relativeUrl));
    } else if ($api === 'embed') {
        header('Location: ' . makeEmbedUrl($platform, $relativeUrl));
    }
    die();
}

function iframeHtml($result): void { ?>
	<?php if (inPlatformArray($result['platform'], PLATFORMS_ORDERED)): ?>
        <div>
            <a class="button" href="<?= abs(end(explode('/', $result['relativeurl']))-1) ?>">⬅️ Previous</a>
            <a class="button" style="float:right;" href="<?= end(explode('/', $result['relativeurl']))+1 ?>">➡️ Next</a>
        </div>
    <?php endif; ?>
    <iframe src="<?= htmlspecialchars(makeEmbedUrl($result['platform'], $result['relativeurl'])) ?>"></iframe>
<?php }

$path = $_SERVER['REQUEST_URI']; //parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$immediateResult = null;

if (isset($_GET['proxatore-search']) && ($search = $_GET['proxatore-search']) !== '') {
    if ($url = parseAbsoluteUrl($search)) {
        return redirectTo($url);
    } else {
        $searchResults = searchHistory($search);
    }
} else {
    $path = trim($path, '/');
    if ($url = parseAbsoluteUrl($path)) {
        //$path = $url;
        return redirectTo($url);
    }

    $segments = explode('/', $path);
    if (SCRIPT_NAME !== '/') {
        array_shift($segments);
    }

    $platform = null;
    $upstream = $segments[0] ?? null;
    $relativeUrl = implode('/', array_slice($segments, 1));

    if (str_starts_with($upstream, '__') && str_ends_with($upstream, '__')) {
        return handleApiRequest($segments);
    } else if (isset(PLATFORMS[$upstream])) {
        if (isset(PLATFORMS_ALIASES[$upstream])) {
            return redirectTo(PLATFORMS_ALIASES[$upstream] . '/' . $relativeUrl);
        } else {
        	$platform = $upstream;
        	$domain = PLATFORMS[$upstream][0];
        }
    } else {
        foreach ([PLATFORMS_PROXIES, PLATFORMS, EMBEDS] as $array) {
            foreach ($array as $platform => $domains) {
                if (in_array($upstream, $domains) || in_array(lstrip($upstream, 'www.', 1), $domains)) {
                    return redirectTo($platform . '/' . $relativeUrl);
                }
            }
            unset($platform);
        }
    }

    if (!$platform && isset(PLATFORMS_REDIRECTS[$upstream])) {
        // TODO: only strip query params for platforms that don't need them
        $relativeUrl = trim(parse_url(fetchContent("{$upstream}/{$relativeUrl}", 1)['url'], PHP_URL_PATH), '/');
        $platform = PLATFORMS_REDIRECTS[$upstream];
        return redirectTo("{$platform}/{$relativeUrl}");
    } else if (!$platform) {
        foreach (PLATFORMS_USERSITES as $domain) {
            if (str_ends_with($upstream, ".{$domain}")) {
                $platform = $upstream;
                break;
            }
        }
    }

    //if ($relativeUrl && $platform && ($content = fetchContent(makeScrapeUrl($platform, $relativeUrl)))['body']) {
    if ($data = getPageData($platform, $relativeUrl)) {
        http_response_code($data['code']);
        $immediateResult = $data['result'];
        if ($immediateResult['video'] && $immediateResult['videotype'] === 'text/html' && readProxatoreParam('htmlmedia')) {
            $proxy = ((readProxatoreParam('mediaproxy') || $_GET['proxatore-mediaproxy'] === 'video') ? 'file' : '');
            $immediateResult['video'] = SCRIPT_NAME . "__{$proxy}proxy__/{$platform}/{$immediateResult['video']}";
            $immediateResult['videotype'] = 'video/mp4';
        }
        fetchPageMedia($data['url'], $immediateResult);
        //}
        //if ($immediateResult['title'] || $immediateResult['description']) {
        //    saveHistory($immediateResult);
        //} else 
        if ($content['code'] >= 400) {
            $searchResults = searchExactHistory($platform, $immediateResult['relativeurl']);
            if (sizeof($searchResults)) {
                $immediateResult = $searchResults[0];
            }
        } else if (readProxatoreParam('history')) {
            saveHistory($immediateResult);
        }
        $immediateResult['description'] = preg_replace('/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/', '<a href="$0" target="_blank" rel="noopener nofollow" title="$0">$0</a>', $immediateResult['description']);
        if (readProxatoreParam('relativemedia')) {
            $count = 0;
            foreach (['video', 'image'] as $type) {
                if ($immediateResult[$type]) {
                    $immediateResult[$type] = SCRIPT_NAME . "__media__/{$platform}/{$immediateResult['relativeurl']}/{$count}";
                    $count++;
                }
            }
        }
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
.img {
    display: inline-block;
}
img, video {
    padding: 1em;
}
img[src=""], video[src=""] {
    display: none;
}
.img + .img,
video:not(video[src=""]) + .img {
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
ul.platforms a {
    text-decoration: none;
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
/* @media (prefers-color-scheme: dark) {
    body {
        background-color: #444;
        color: white;
    }
    .container {
        background-color: #222;
    }
    .history-item strong {
        color: white;
    }
    .history-item:hover {
        background-color: #333;
    }
    a {
        color:rgb(85, 155, 247);
    }
} */
</style>
</head>
<body>
    <div class="container">
        <h1><a href="<?= SCRIPT_NAME ?>"><?= APPNAME; ?></a></h1>
        <form method="get" action="<?= SCRIPT_NAME ?>">
            <div class="search-bar">
                <input type="text" required="required" name="proxatore-search" placeholder="Search or Input URL" value="<?= htmlspecialchars($_GET['proxatore-search'] ?: makeCanonicalUrl($immediateResult) ?: '') ?>">
                <button type="submit">Go 💣️</button>
            </div>
            <details style="margin-bottom: 20px;">
                <summary>Options</summary>
                <ul>
                    <li><label><input type="checkbox" name="proxatore-history" value="false" <?php if ($_GET['proxatore-history'] === 'false') echo 'checked="true"' ?> /> Incognito Mode (don't save query to global cache/history)</label></li>
                </ul>
            </details>
        </form>
        <?php if (!isset($searchResults)) {
            $platforms = '';
            $searchPrefix = (SCRIPT_NAME . '?proxatore-search=');
            echo '<p>Supported Platforms:</p><ul class="platforms">';
            foreach (array_keys(PLATFORMS) as $platform) {
                //echo ((isset(PLATFORMS_ALIASES[$platform])) ? "/" : "</li><li>") . $platform;
                $platforms .= ((isset(PLATFORMS_ALIASES[$platform])) ? '/' : "</a></li><li><a href='{$searchPrefix}\"platform\":\"{$platform}\"'>") . $platform;
            }
            foreach (PLATFORMS_USERSITES as $platform) {
                $platforms .= "</a></li><li><a href='{$searchPrefix}.{$platform}\",\"relativeurl\"'>{$platform}";
            }
            echo substr($platforms, strlen('</a></li>')) . '</a></li></ul>';
            // echo '<details><summary>Query API</summary><ul>
            //     <li>/?<code>proxatore-search=</code>{search term} — Make a full-text search or load a given URL</li>
            //     <li>...?<code>proxatore-history=</code>{true,false} — Specify if a given query must be stored in the global search history (default: true)</li>
            // </ul></details>';
            echo '<p>Made with 🕸️ and 🧨 by <a href="https://hub.octt.eu.org">OctoSpacc</a>.
                <small>Source Code: <a href="https://hlb0.octt.eu.org/Drive/Misc/Scripts/Proxatore.php">Proxatore.php</a>.</small>
            </p>';
        } ?>
		<?php if (isset($immediateResult) && readProxatoreParam('embedfirst')) iframeHtml($immediateResult); ?>
        <?php if (isset($searchResults)): ?>
            <?php if (!isset($immediateResult)): ?>
                <h3>Search results:</h3>
                <?php if (!sizeof($searchResults)): ?>
                    <p>Nothing was found.</p>
                <?php endif; ?>
            <?php endif; ?>
            <?php foreach ($searchResults as $item): ?>
                <div class="history-item <?php
                    similar_text($item['title'], $item['description'], $percent);
                    if ($percent > 90) echo 'ellipsize';
                ?>">
                    <p class="title">
                        <strong><?= htmlspecialchars($item['title']) ?></strong>
                        <small><?= htmlspecialchars($item['platform']) ?><!-- <?= htmlspecialchars($item['datetime']) ?> --></small>
                    </p>
                    <div style="text-align: center;">
                        <?php if ($item['video'] /* $item['video'] && $item['videotype'] !== 'text/html' */): ?>
                            <video src="<?= htmlspecialchars($item['platform'] === 'youtube' ? (SCRIPT_NAME . '__proxy__/youtube/' . $immediateResult['video']) : ($item['video'] ?? '')) ?>" controls="controls"></video>
                        <?php endif; ?>
                        <?php foreach (array_merge([$item['image']], $item['images']) as $image): ?>
                            <a class="img" href="<?= htmlspecialchars($image ?? '') ?>" target="_blank" rel="noopener nofollow"><img src="<?= htmlspecialchars($image ?? '') ?>" onerror="this.hidden=true" /></a>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <p>
                            <strong><?= htmlspecialchars($item['title']) ?></strong>
                            <small><?= htmlspecialchars($item['platform']) ?><!-- <?= htmlspecialchars($item['datetime']) ?> --></small>
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
        <?php if (isset($immediateResult) && !readProxatoreParam('embedfirst')) iframeHtml($immediateResult); ?>
    </div>
</body>
</html>
