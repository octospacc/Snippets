<?php
/*
 * MIT License
 * 
 * Copyright (c) 2024 OctoSpacc
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

// <https://github.com/fastvolt/markdown>
require 'Res/FastVoltMarkdown.php';
use FastVolt\Helper\Markdown;
$markdown = Markdown::new();

$instance = 'https://memos.octt.eu.org';
$id = $_GET['id'];
$uid = $_GET['uid'];

$footer = "<footer><p><small>Powered by <a href=\"https://memos.octt.eu.org/m/8edpaJ4n4gtdbSZVDcBve3\">MemosViewer.php</a>. [<a href=\"https://gitlab.com/octospacc/Snippets/-/blob/main/MemosViewer.php\">Source Code</a>]</small></p></footer>";

if ( !$id && !$uid ) {
	$memos = array_slice( explode( "\n\tmemos/", file_get_contents( "{$instance}/memos.api.v1.MemoService/ListMemos", false, stream_context_create([ 'http' => [
		'method' => 'POST',
		'header' => 'Content-Type: application/grpc-web+proto',
		'content' => urldecode('%00%00%00%008%08%10%1A4row_status%20%3D%3D%20%22NORMAL%22%20%26%26%20visibilities%20%3D%3D%20%5B\'PUBLIC\'%5D'),
	]]) ) ), 1 );
	echo "<!DOCTYPE html><html lang=\"en\"><body><p>Latest public memos from <a href=\"{$instance}\">{$instance}</a>:</p><ul>";
	foreach ( $memos as $memo ) {
		$uid = explode( '%', explode( '%12%16', urlencode($memo) )[1] )[0];
		if ( $uid ) {
			//$user = explode( '*', explode( 'users/', $memo )[1] )[0];
			echo "<li><a href=\"{$instance}/m/{$uid}\">${uid}</a></li>";
		}
	}
	echo "</ul>{$footer}</body></html>";
	return;
}

if ( !$id ) {
	// as of writing this, there is no JSON API to get a memo by its uid
	// so, we first get the numeric id by sloppily parsing the GRPC API
	$id = explode( '%', urlencode(explode( 'memos/', file_get_contents( "{$instance}/memos.api.v1.MemoService/SearchMemos", false, stream_context_create([ 'http' => [
		'method' => 'POST',
		'header' => 'Content-Type: application/grpc-web+proto',
		'content' => urldecode('%00%00%00%00!%0A%1Fuid%20%3D%3D%20%22' . $uid . '%22'),
	]]) ) )[1]))[0];
}

// we always use the numeric id to get memo data via the JSON API
$memo = json_decode(file_get_contents("{$instance}/api/v1/memos/{$id}"));
$user = json_decode(file_get_contents("{$instance}/api/v1/{$memo->creator}"));

// patch the Markdown before parsing it, so that output is quasi-consistent with Memos
$content = '';
$inblock = false;
$lines = explode( "\n", $memo->content );
foreach ( $lines as $line ) {
	if ( str_starts_with( $line, '```' ) ) {
		$inblock = !$inblock;
	} else if ( str_starts_with( $line, '#' ) ) {
		// prevent hashtags from being interpreted as headings
		$firstword = explode( ' ', str_replace( "\t", ' ', $line ) )[0];
		if ( $firstword !== '#' ) {
			$content .= "&#x20;{$firstword}";
			$line = substr( $line, strlen($firstword) );
		}
	}
	$content .= $line . "\n";
	if ( !$inblock ) {
		$content .= "\n";
	}
}

$markdown->setContent($content);
$content = $markdown->toHtml();

$htmlparts = explode( '<pre><code class="language-__html">', $content );
$content = array_shift($htmlparts);
foreach ( $htmlparts as $part ) {
	[$inside, $after] = explode( '</code></pre>', $part );
	//$content .= '<pre><code class="language-__html">' . $inside . '</code></pre>' . $after;
	$content .= '<iframe src="data:text/html;utf8,' . urlencode('<meta charset="utf8"/><style>iframe{width:100%}</style>' . html_entity_decode($inside)) . '"></iframe>' . $after;
}

$warning = '';
$base = file_get_contents($instance /* . '/m/' . $uid */);
if ( $_GET['structure'] === 'original' ) {
	$warning = '<noscript><p class="warning">Please enable JavaScript for full functionality and perfect viewing.</p></noscript>';
} /* else if ( $_GET['structure'] === 'standalone' ) {
	$base = "
<!DOCTYPE html><html>
<head>
<meta charset=\"utf-8\"/>
</head>
<body><div id=\"root\"></div></body>
</html>
";
} */

$nickname = htmlspecialchars($user->nickname);
$pagetitle = "Memo by {$nickname}";
$pagedescription = htmlspecialchars($memo->content);
$htmlimage = implode( '', array_slice( explode( '"', implode( '', array_slice( explode( '<img src="', $content ), 1 ) ) ), 0, 1 ) );
if ( $htmlimage ) {
	$htmlimage = "<meta property=\"og:image\" content=\"{$htmlimage}\"/>";
}

$meta = "
<title>{$pagetitle}</title>
<meta property=\"og:title\" content=\"{$pagetitle}\"/>
<meta property=\"og:site_name\" content=\"Memos\"/>
<meta property=\"og:description\" content=\"{$pagedescription}\"/>
<meta name=\"description\" content=\"{$pagedescription}\"/>
{$htmlimage}
<style>
body {
	margin: 0;
}
div.MemosViewer * {
	list-style: revert;
	padding: revert;
	margin: revert;
}
div.MemosViewer, div.MemosViewer > article > header, div.MemosViewer > footer {
	padding: 1em;
}
div.MemosViewer a {
	color: revert;
}
div.MemosViewer > article > header p.warning {
	color: red;
}
div.MemosViewer iframe {
	width: 100%;
	height: calc(10em + 10px);
}
</style>
";

$body = "<div class=\"MemosViewer\">
<article>
<header>
<b>{$nickname}</b> on <span><a href=\"{$instance}/m/{$uid}\">{$memo->displayTime}</a>
[<small><a href=\"{$instance}/api/v1/memos/${id}\">JSON</a></small>]</span>
{$warning}
</header>
{$content}
</article>
{$footer}
</div>";

$base = str_replace( '<title>Memos</title>', '', $base );
$base = str_replace( "</head>", "{$meta}</head>", $base );
$base = str_replace( "<div id=\"root\"></div>", "<div id=\"root\">{$body}</div>", $base );
echo $base;
