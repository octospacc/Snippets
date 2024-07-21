<?php
$memosinstance = 'https://memos.octt.eu.org';
$yacyinstance = 'https://yacy.spacc.eu.org';
require (dirname(__FILE__) . '/../root-secret/MemosYacyBridgeCreate.Config.php');
// $yacyusername = '';
// $yacypassword = '';
$memosapipath = "{$memosinstance}/memos.api.v1.MemoService";

if ( php_sapi_name() === 'cli' && $argv[1] ) {
	file_get_contents("{$yacyinstance}/?auth=");
	$authkey = 'www-authenticate:';
	$authrequest = trim(substr( array_values(array_filter( $http_response_header, function($header){ return str_starts_with( strtolower($header), 'www-authenticate' ); } ))[0], strlen($authkey) ));
	$authrealm = explode( '"', explode( ' realm="', $authrequest )[1] )[0];
	$authnonce = explode( '"', explode( ' nonce="', $authrequest )[1] )[0];
	$auth1 = md5("{$yacyusername}:{$authrealm}:{$yacypassword}");
	$auth2 = md5("GET:/?auth=");
	$authresponse = md5("{$auth1}:{$authnonce}:00000001:0a4f113b:auth:{$auth2}");
	$authrequest = str_replace( ' qop="auth"', ' qop=auth', $authrequest );
	///**/$auth2 = md5('GET:/index.html?auth');
	///**/$authresponse = md5("{$auth1}::00000001::auth:{$auth2}");
	//echo "\nreq {$authrequest}\nrealm {$authrealm}\nnonce {$authnonce}\n1 {$auth1}\n2 {$auth2}\nres {$authresponse}\n{$authrequest}, username=\"{$yacyusername}\", uri=\"/?auth=\", response=\"{$authresponse}\", nc=00000001, cnonce=\"0a4f113b\"\n";
	file_get_contents( ($yacyinstance . '/Crawler_p.html?crawlingDomMaxPages=10000&range=wide&intention=&crawlingQ=off&crawlingMode=url&crawlingURL=' . urlencode("{$memosinstance}/m/{$argv[1]}") . '&mustnotmatch=&crawlingFile%24file=&crawlingstart=Neuen%20Crawl%20starten&mustmatch=.*&createBookmark=on&bookmarkFolder=/crawlStart&indexMedia=on&crawlingIfOlderUnit=hour&cachePolicy=iffresh&indexText=on&crawlingIfOlderCheck=on&bookmarkTitle=&crawlingDomFilterDepth=1&crawlingDomFilterCheck=on&crawlingIfOlderNumber=1&crawlingDepth=1'), false, stream_context_create([ 'http' => [
		'header' => "Authorization: {$authrequest}, username=\"{$yacyusername}\", uri=\"/?auth=\", response=\"{$authresponse}\", nc=00000001, cnonce=\"0a4f113b\"",
	]]) );
	//echo var_dump($http_response_header);
	return;
}

$headers = "X-NoProxy: 1\n";
foreach ( getallheaders() as $key => $value ) {
	if ( !$value || $key === 'Accept-Encoding' ) {
		continue;
	}
	if ( $key === 'Host' ) {
		$value = explode( '//', $memosinstance )[1];
	}
	$headers .= "{$key}: {$value}\n";
}

$response = file_get_contents( "{$memosapipath}/{$_GET['endpoint']}", false, stream_context_create([ 'http' => [
	'method' => $_SERVER['REQUEST_METHOD'],
	'header' => $headers,
	'content' => file_get_contents('php://input'),
]]) );

shell_exec(sprintf( 'php %s %s > /dev/null 2>&1 &', __FILE__, explode( '%', explode( '%12%16', urlencode($response) )[1] )[0] ));

foreach ( $http_response_header as $header ) {
	header($header);
}
echo $response;
