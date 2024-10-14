<!DOCTYPE html>
<?php
$APP_NAME = 'ViewUltra';
$APP_PATH = "./{$APP_NAME}.php";
$RESULT_COUNT = 35;

$url = $_GET['url'];
$omni = $_GET['omni'];
$embed = $_GET['embed'];
$search = $_GET['search'];

$url0 = $url;
$platform = null;
$metadata = [];

require 'Res/http_build_url.php';

function parse_url_sure ( $url ) {
	if ( gettype($url) == 'string' ) {
		$url = parse_url($url);
	}
	return $url;
}

if ( $api = $_GET['api'] ) {
	$search = urlencode($search);
	switch ($api) {
		case 'related':
			echo file_get_contents("https://www.bing.com/videos/api/custom/details?vdpp=rvrv&mmcaptn=Bing.Video&skey=LpjYneKXrGPhU1rWE1pUvUE7_tPlNQt3oAXWj_pFOfk&safesearch=Moderate&mkt=it-it&setlang=en-gb&iid=cusvdp&sfx=1&q={$search}&modules=videoresult,videoreco,queryresultvideos,relatedsearches,relatedads,videochat&channels=relatedvideos&recocount={$RESULT_COUNT}&recomidboost=true&recooffset=0&chaidx=-1&armk=ra&mmscn=Default");
		case 'search':
			//echo file_get_contents("https://duckduckgo.com/v.js?l=wt-wt&o=json&sr=1&q={$search}&vqd=4-179096413383356441354158635150751105868&f=,,,&p=-1");
			echo file_get_contents("https://www.bing.com/videos/async/rankedans?q={$search}&mmasync=1&varh=VideoResultInfiniteScroll&vdpp=VideoResultAsync&count={$RESULT_COUNT}&first=0&IID=vrpalis&SFX=1");
		case 'trending':
			echo file_get_contents("https://www.bing.com/videos/api/custom/trending?mmcaptn=Bing.VideoOneColumnFeeds&recopid=VideoLandingPage&channels=trending&offset=0&videocount={$RESULT_COUNT}&skey=LpjYneKXrGPhU1rWE1pUvUE7_tPlNQt3oAXWj_pFOfk&safesearch=Moderate&iid=vlp&sfx=1");
		default:
			http_response_code(400);
	}
	return;
}
if ( !$embed ) {
	if ( $omni ) {
		$url = parse_url_sure($omni);
		if ( !$search && !array_key_exists('host', parse_url_sure($url)) ) {
			$url = null;
			$search = $omni;
		}
	}
	if ( $url ) {
		$url = parse_url_sure($url);
		switch ($url['host']) {
			case 'youtu.be':
				$url = http_build_url($url['scheme'] . '://youtube.com/watch?v=' . substr($url['path'], 1));
			break;
		}
		$href = parse_url_sure($url);
		$path = explode('/', rtrim($url['path'], '/'));
		parse_str($href['query'], $query);
		switch ($href['host']) {
			case 'bilibili.com':
			case 'www.bilibili.com':
				$href = ($href['scheme'] . '://player.bilibili.com/player.html?isOutside=true&p=1&bvid=' . end($path));
				$platform = 'bilibili';
			break;
			case 'tiktok.com':
			case 'www.tiktok.com':
				$href = ($href['scheme'] . '://www.tiktok.com/embed/v3/' . end($path) . '?mute=0');
				$platform = 'tiktok';
			break;
			case 'youtube.com':
			case 'www.youtube.com':
			// case 'youtube-nocookie.com':
			// case 'www.youtube-nocookie.com':
				$href = ($href['scheme'] . '://www.youtube-nocookie.com/embed/' . $query['v']);
				$platform = 'youtube';
			break;
		}
		$url = http_build_url($url);
		$embed = http_build_url($href);
		if ( $platform ) {
			$doc = new DOMDocument();
			libxml_use_internal_errors(true); 
			$doc->loadHTML(mb_convert_encoding(file_get_contents($url), 'HTML-ENTITIES', 'UTF-8'));
			libxml_clear_errors();
			foreach ( $doc->getElementsByTagName('meta') as $metaEl ) {
				$key = $metaEl->getAttribute('name')
					?: $metaEl->getAttribute('property')
					?: $metaEl->getAttribute('itemprop');
				$value = $metaEl->getAttribute('content');
				if ( !$key && !$value ) {
					continue;
				}
				if (!array_key_exists($key, $metadata)) {
					$metadata[$key] = $value;
				} elseif (!in_array($content, explode('\n', $metadata[$key]))) {
					$metadata[$key] .= "\n{$value}";
				}
			}
		} else {
			
		}
	}
}
$description = 'View ' . ($metadata['og:title']?: 'this content') . ' on ' . $APP_NAME;
?>
<html lang="en">
<head>
<title><?php echo $metadata['og:title'] ?> | <?php echo $APP_NAME ?></title>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="description" content="<?php echo $description ?>"/>
<meta property="og:site_name" content="<?php echo $APP_NAME ?>"/>
<meta property="og:title" content="<?php echo $metadata['og:title'] ?>"/>
<meta property="og:image" content="<?php echo $metadata['og:image'] ?>"/>
<meta property="og:image:width" content="<?php echo $metadata['og:image:width'] ?>"/>
<meta property="og:image:height" content="<?php echo $metadata['og:image:height'] ?>"/>
<style>
* {
	box-sizing: border-box;
}
table td > * {
	height: 2em;
}
table td.full,
table td.full > * {
	width: 100%;
}
body.vu-app {
	color: white;
	background-color: black;
	position: absolute;
	margin: 0;
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
}
a {
	color: hotpink;
}
.font-mega {
	font-weight: bold;
	font-size: large;
}
.vu-main {
	flex: 1;
	flex-direction: row;
	position: sticky;
	top: 0;
}
.vu-content {
	flex: 1;
	display: flex;
	flex-direction: column;
	height: 100vh;
}
.vu-content > iframe {
	flex: 1;
	width: 100%;
	height: 100%;
	border: none;
}
.vu-details > summary {
	margin: 1em;
}
/* .vu-details > summary > * {
	display: inline-block;
} */
@media (min-width: 600px) {
	.vu-main {
		display: flex;
	}
	.vu-content {
		height: 100%;
	}
	.vu-related {
		height: 0;
		width: 30%;
		min-width: 150px;
		max-width: 300px;
	}
	.vu-details > div {
		position: absolute;
		width: calc(100vw - 30%);
	}
}
.vu-related .mc_fgvc,
.vu-related .mc_fgvc_u,
.vu-related .tvsr .vsb_vid_cat,
.vu-related .isvsr .vsb_vid_cat {
	width: 100% !important;
	padding: 0 !important;
	margin: 0 !important;
}
.vu-related .mc_fgvc_u * {
	border-radius: revert !important;
}
.vu-related .mc_fgvc_u .mc_vtvc_meta {
	background-color: gray !important;
}
.vu-related .mc_fgvc_u .mc_vtvc_meta * {
	color: white !important;
}
.vu-related #mm_vidreco_sublabel,
.vu-related #fbdialog,
.vu-related .fbdialog.b_cards {
	display: none !important;
}
</style>
</head>
<body class="vu-app">
	<!--<form style="display: flex; height: 2em; margin-bottom: 8px;">
		<a href="./ViewUltra.php">ViewUltra</a>
		<input type="text" name="omnibox" style="flex: auto; margin-right: 8px;"/>
		<input type="submit" value="Go ↩️"/>
	</form>-->
	<form>
		<table><!--<tbody><tr>-->
			<td><a class="font-mega" href="<?php echo $APP_PATH ?>"><?php echo $APP_NAME ?></a></td>
			<td class="full"><input type="text" name="omni" placeholder="🔍️ Search or input URL..." value="<?php echo current(array_filter([$omni, $search, $url0, $embed])) ?>"/></td>
			<td><input type="submit" value="Go ↩️"/></td>
		<!--</tr></tbody>--></table>
	</form>
	<div class="vu-main">
		<?php if ( $embed ) {
		?>
			<div class="vu-content">
				<iframe src="<?php echo $embed ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen="allowfullscreen"></iframe>
				<details class="vu-details" open="open">
					<summary>
						<span class="font-mega"><?php echo $metadata['og:title'] ?></span>
						(<?php echo $metadata['og:site_name'] ?>)
						<?php echo $metadata['datePublished'] ?>
					</summary>
					<div>
						<p><?php echo $metadata['og:description'] ?></p>
						<p><a href="<?php echo $url ?>"><?php echo $url ?></a></p>
					</div>
				</details>
			</div>
		<?php } elseif ( !$search ) { ?>
			<p>It works!!! (semicit.)<br/>Work in progress.</p>
		<?php } ?>
		<div class="vu-related">
			<p>Please enable JavaScript to view related content.</p>
		</div>
	</div>
<script>(function(){
	var $APP_PATH = <?php echo json_encode($APP_PATH) ?>;
	var $metadata = <?php echo json_encode($metadata) ?>;
	var $search = ($metadata['og:title'] || <?php echo json_encode($search) ?>);
	var searchEncoded = encodeURIComponent($search || '');
	//var getParams = (new URLSearchParams(window.location.search));
	var relatedEl = document.querySelector('.vu-related');
	relatedEl.innerHTML = '';
	if (!$search) {
		return;
	}
	function fetchRelated () {
		relatedEl.innerHTML = '<p>Loading...</p>';
		fetch($APP_PATH + '?api=search&search=' + searchEncoded)
			.then(response => response.text())
			.then(html => {
				var fragmentEl = Object.assign(document.createElement('div'), { innerHTML: html });
				Array.from(fragmentEl.querySelectorAll('script')).forEach(scriptEl => scriptEl.remove());
				Array.from(fragmentEl.querySelectorAll('div.mc_fgvc_u a[href]')).forEach(linkEl => {
					var targetUrl = (linkEl.getAttribute('ourl') || linkEl.querySelector('[ourl]').getAttribute('ourl'));
					linkEl.href = ($APP_PATH + '?url=' + encodeURIComponent(targetUrl) + '&search=' + searchEncoded);
				});
				relatedEl.innerHTML = fragmentEl.innerHTML;
			})
			.catch(err => {
				console.error(err);
				relatedEl.innerHTML = ('<p>' + err + '</p><p><button>Retry</button></p>');
				relatedEl.querySelector('button').onclick = fetchRelated;
			});
	}
	fetchRelated();
	window.ViewUltra = $metadata;
})();</script>
</body>
</html>
