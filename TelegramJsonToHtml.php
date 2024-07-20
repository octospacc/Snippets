<?php
$tmeroot = 'https://t.me';
$cdnroot = 'https://telegram.org'; //$tmeroot;
$extracdnroot = '..'; //$cdnroot;

function safemkdir ( $path ) {
	if ( !is_dir($path) ) {
		return mkdir($path);
	}
}

function usernameorid ( $message ) {
	return ($message->from ? htmlspecialchars($message->from) : $message->from_id);
}

function colorfromid ( $id ) {
	if ( ($id = (int)(substr( $id, -2 ) * 0.07)) === 7 ) {
		$id = 0;
	}
	return $id;
}

function getreplytoid ( $message ) {
	return (property_exists( $message, 'reply_to_message_id' ) ? $message->reply_to_message_id : null);
}

function renderquotemessage ( $chat, $message ) {
	global $tmeroot;
?><div class="tgme_widget_message_bubble tgme_widget_message_bubble_quoting"><div class="tgme_widget_message_author accent_color"><a href="<?php echo $tmeroot; ?>/<?php echo $chat->id; ?>/<?php echo $message->id; ?>/">↩️ (in reply to)&nbsp;<span class="tgme_widget_message_owner_name"><span dir="auto"><?php echo usernameorid($message); ?></span></span></a></div><div class="tgme_widget_message_text js-message_text" dir="auto"><?php echo htmlspecialchars($message->full_text); ?></div><div class="tgme_widget_message_footer js-message_footer"><div class="tgme_widget_message_info js-message_info"><span class="tgme_widget_message_meta"><a class="tgme_widget_message_date" href="<?php echo $tmeroot; ?>/<?php echo $chat->id; ?>/<?php echo $message->id; ?>/"><time datetime="<?php echo $message->date; ?>" class="datetime"><?php echo $message->date; ?></time></a></span></div></div></div><?php
}

function rendermessage ( $chat, $message ) {
	global $tmeroot;
?><div class="tgme_widget_message text_not_supported_wrap js-widget_message" data-post="<?php echo $chat->id; ?>/<?php echo $message->id; ?>" data-user="<?php echo $message->from_id; ?>"><div class="tgme_widget_message_user"><a><i class="tgme_widget_message_user_photo bgcolor<?php echo colorfromid($message->from_id); ?>" data-content="<?php echo usernameorid($message)[0]; ?>"></i></a></div><div class="tgme_widget_message_bubble"><i class="tgme_widget_message_bubble_tail"><svg class="bubble_icon" width="9px" height="20px" viewBox="0 0 9 20"><g fill="none"><path class="background" fill="#ffffff" d="M8,1 L9,1 L9,20 L8,20 L8,18 C7.807,15.161 7.124,12.233 5.950,9.218 C5.046,6.893 3.504,4.733 1.325,2.738 L1.325,2.738 C0.917,2.365 0.89,1.732 1.263,1.325 C1.452,1.118 1.72,1 2,1 L8,1 Z"></path><path class="border_1x" fill="#d7e3ec" d="M9,1 L2,1 C1.72,1 1.452,1.118 1.263,1.325 C0.89,1.732 0.917,2.365 1.325,2.738 C3.504,4.733 5.046,6.893 5.95,9.218 C7.124,12.233 7.807,15.161 8,18 L8,20 L9,20 L9,1 Z M2,0 L9,0 L9,20 L7,20 L7,20 L7.002,18.068 C6.816,15.333 6.156,12.504 5.018,9.58 C4.172,7.406 2.72,5.371 0.649,3.475 C-0.165,2.729 -0.221,1.464 0.525,0.649 C0.904,0.236 1.439,0 2,0 Z"></path><path class="border_2x" d="M9,1 L2,1 C1.72,1 1.452,1.118 1.263,1.325 C0.89,1.732 0.917,2.365 1.325,2.738 C3.504,4.733 5.046,6.893 5.95,9.218 C7.124,12.233 7.807,15.161 8,18 L8,20 L9,20 L9,1 Z M2,0.5 L9,0.5 L9,20 L7.5,20 L7.5,20 L7.501,18.034 C7.312,15.247 6.64,12.369 5.484,9.399 C4.609,7.15 3.112,5.052 0.987,3.106 C0.376,2.547 0.334,1.598 0.894,0.987 C1.178,0.677 1.579,0.5 2,0.5 Z"></path><path class="border_3x" d="M9,1 L2,1 C1.72,1 1.452,1.118 1.263,1.325 C0.89,1.732 0.917,2.365 1.325,2.738 C3.504,4.733 5.046,6.893 5.95,9.218 C7.124,12.233 7.807,15.161 8,18 L8,20 L9,20 L9,1 Z M2,0.667 L9,0.667 L9,20 L7.667,20 L7.667,20 L7.668,18.023 C7.477,15.218 6.802,12.324 5.64,9.338 C4.755,7.064 3.243,4.946 1.1,2.983 C0.557,2.486 0.52,1.643 1.017,1.1 C1.269,0.824 1.626,0.667 2,0.667 Z"></path></g></svg></i><div class="tgme_widget_message_author accent_color"><span class="tgme_widget_message_owner_name"><span dir="auto"><?php echo usernameorid($message); ?></span></span></div><div class="tgme_widget_message_text js-message_text" dir="auto"><?php echo htmlspecialchars($message->full_text); ?></div><div class="tgme_widget_message_footer js-message_footer"><div class="tgme_widget_message_info js-message_info"><span class="tgme_widget_message_meta"><a class="tgme_widget_message_date" href="<?php echo $tmeroot; ?>/<?php echo $chat->id; ?>/<?php echo $message->id; ?>/"><time datetime="<?php echo $message->date; ?>" class="datetime"><?php echo $message->date; ?></time></a></span></div></div></div></div><?php
}

function recursereplies ( $chat, $message ) {
	if ( array_key_exists( $message->id, $chat->threads ) ) {
		foreach ( $chat->threads[$message->id] as $childid ) {
		?><div class="tgme_widget_reply"><?php
			fwrite( STDERR, "> {$childid}\n" );
			$child = $chat->messages[$childid];
			rendermessage( $chat, $child );
			recursereplies( $chat, $child );
		?></div><?php
		}
	}
}

fclose(STDOUT);
$output = (implode( '.', array_slice( explode( '.', $argv[1] ), 0, -1 ) ) . '-HTML' . DIRECTORY_SEPARATOR);
$chat = json_decode(file_get_contents($argv[1]));
safemkdir($output);

safemkdir("{$output}css");
$STDOUT = fopen( ("{$output}css" . DIRECTORY_SEPARATOR . 'tgme-dump.css'), 'w' );
?>div.tgme_head {
	display: revert;
	white-space: nowrap;
	overflow-x: auto;
}
div.tgme_head > h1 {
	display: inline-block;
	margin: 8px;
}
div.tgme_widget_reply {
	padding-left: 1em;
	padding-right: 1em;
}
div.tgme_widget_reply > div.tgme_widget_message {
	padding-top: 2em;
}
div.tgme_page_widget > div.tgme_widget_replies {
	margin-top: 2em;
	border-top: 2px solid gray;
}
div.tgme_page_widget_actions_wrap {
	position: sticky;
	bottom: 2em;
	padding-top: 2em;
}
div.tgme_page_widget_actions_wrap span.tgme_action_button_label {
	display: initial;
}
div.tgme_widget_message_bubble.tgme_widget_message_bubble_quoting {
	border-radius: 10px 10px 10px 10px;
	margin-bottom: 2em;
	margin-left: 4em;
	margin-right: 4em;
	opacity: 80%;
}
div.tgme_widget_message_bubble.tgme_widget_message_bubble_quoting div.tgme_widget_message_text {
	text-overflow: ellipsis;
	white-space: nowrap;
	overflow: hidden;
	opacity: 60%;
}<?php
fclose($STDOUT);

safemkdir("{$output}js");
$STDOUT = fopen( ("{$output}js" . DIRECTORY_SEPARATOR . 'tgme-init.js'), 'w' );
?>var tme_bg = document.getElementById('tgme_background');
if (tme_bg) {
	TWallpaper.init(tme_bg);
	TWallpaper.animate(true);
	window.addEventListener('focus', TWallpaper.update);
}
document.body.classList.remove('no_transition');
function toggleTheme (dark) {
	document.documentElement.classList.toggle('theme_dark', dark);
	window.Telegram && Telegram.setWidgetOptions({ dark: dark });
}
if (window.matchMedia) {
	var darkMedia = window.matchMedia('(prefers-color-scheme: dark)');
	toggleTheme(darkMedia.matches);
	darkMedia.addListener(function(e) { toggleTheme(e.matches); });
}<?php
fclose($STDOUT);

// prepare the message array, by enumerating threads and assigning keys as message id
$firstmessageurl = '';
$oldmessages = $chat->messages;
$chat->messages = [];
$chat->threads = [];
foreach ( $oldmessages as $message ) {
	if ( $message->type === 'service' ) {
		continue;
	}
	if ( !$firstmessageurl ) {
		$firstmessageurl = "{$tmeroot}/{$chat->id}/{$message->id}/";
	}
	$chat->messages[$message->id] = $message;
	if ( $replytoid = getreplytoid($message) ) {
		if ( !array_key_exists( $replytoid, $chat->threads ) ) {
			$chat->threads[$replytoid] = [];
		}
		array_push( $chat->threads[$replytoid], $message->id );
	}
}

$title = (htmlspecialchars($chat->name) . ' - Redirect');
$STDOUT = fopen( "{$output}index.html", 'w' );
?><!DOCTYPE html><html><head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta http-equiv="refresh" content="0; url='<?php echo $firstmessageurl; ?>'">
<title><?php echo $title; ?></title>
</head><body>
<h1><?php echo $title; ?></h1>
<a href="<?php echo $firstmessageurl; ?>"><?php echo $firstmessageurl; ?></a>
</body></html><?php
fclose($STDOUT);

$previousid = null;
foreach ( $chat->messages as $message ) {
	$nextmessage = next($chat->messages);
	fwrite( STDERR, "{$message->id}\n" );
	safemkdir("{$output}{$message->id}");
	$STDOUT = fopen( ($output . $message->id . DIRECTORY_SEPARATOR . 'index.html'), 'w' );
	$title = (usernameorid($message) . " &gt; " . htmlspecialchars($chat->name) . " (Thread {$message->date})");
?><!DOCTYPE html><html><head><meta charset="utf-8"><title><?php echo $title; ?></title><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta property="og:title" content="<?php echo $title; ?>"><meta property="og:site_name" content="Telegram"><meta property="og:description" content="<?php echo htmlspecialchars($message->full_text); ?>"><meta property="twitter:title" content="<?php echo $title; ?>"><meta name="twitter:card" content="summary"><meta name="twitter:description" content="<?php echo htmlspecialchars($message->full_text); ?>"><script>window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches&&document.documentElement&&document.documentElement.classList&&document.documentElement.classList.add('theme_dark');</script><link rel="icon" type="image/svg+xml" href="<?php echo $cdnroot; ?>/img/website_icon.svg"><link rel="apple-touch-icon" sizes="180x180" href="<?php echo $cdnroot; ?>/img/apple-touch-icon.png"><link rel="icon" type="image/png" sizes="32x32" href="<?php echo $cdnroot; ?>/img/favicon-32x32.png"><link rel="icon" type="image/png" sizes="16x16" href="<?php echo $cdnroot; ?>/img/favicon-16x16.png"><link rel="alternate icon" href="<?php echo $cdnroot; ?>/img/favicon.ico" type="image/x-icon"><link href="<?php echo $cdnroot; ?>/css/font-roboto.css" rel="stylesheet" type="text/css"><link href="<?php echo $cdnroot; ?>/css/bootstrap.min.css" rel="stylesheet"><link href="<?php echo $extracdnroot; ?>/css/tgme-dump.css" rel="stylesheet" type="text/css"><link href="<?php echo $cdnroot; ?>/css/telegram.css" rel="stylesheet" media="screen"><link href="<?php echo $cdnroot; ?>/css/widget-frame.css" rel="stylesheet" media="screen"></head><body><div class="tgme_background_wrap"><canvas id="tgme_background" class="tgme_background default" width="50" height="50" data-colors="dbddbb,6ba587,d5d88d,88b884"></canvas><div class="tgme_background_pattern default"></div></div><div class="tgme_page_wrap"><div class="tgme_head_wrap"><div class="tgme_head"><a href="<?php echo $cdnroot; ?>/" class="tgme_head_brand"><svg class="tgme_logo" height="34" viewBox="0 0 34 34" width="34" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><circle cx="17" cy="17" fill="var(--accent-btn-color)" r="17"></circle><path d="m7.06510669 16.9258959c5.22739451-2.1065178 8.71314291-3.4952633 10.45724521-4.1662364 4.9797665-1.9157646 6.0145193-2.2485535 6.6889567-2.2595423.1483363-.0024169.480005.0315855.6948461.192827.1814076.1361492.23132.3200675.2552048.4491519.0238847.1290844.0536269.4231419.0299841.65291-.2698553 2.6225356-1.4375148 8.986738-2.0315537 11.9240228-.2513602 1.2428753-.7499132 1.5088847-1.2290685 1.5496672-1.0413153.0886298-1.8284257-.4857912-2.8369905-1.0972863-1.5782048-.9568691-2.5327083-1.3984317-4.0646293-2.3321592-1.7703998-1.0790837-.212559-1.583655.7963867-2.5529189.2640459-.2536609 4.7753906-4.3097041 4.755976-4.431706-.0070494-.0442984-.1409018-.481649-.2457499-.5678447-.104848-.0861957-.2595946-.0567202-.3712641-.033278-.1582881.0332286-2.6794907 1.5745492-7.5636077 4.6239616-.715635.4545193-1.3638349.6759763-1.9445998.6643712-.64024672-.0127938-1.87182452-.334829-2.78737602-.6100966-1.12296117-.3376271-1.53748501-.4966332-1.45976769-1.0700283.04048-.2986597.32581586-.610598.8560076-.935815z" fill="#fff"></path></g></svg></a>&nbsp;<h1>Telegram: <a href="<?php echo $tmeroot; ?>/<?php echo $chat->id; ?>/"><?php echo htmlspecialchars($chat->name); ?></a></h1></div></div><div class="tgme_body_wrap"><div class="tgme_page tgme_page_post"><div class="tgme_page_widget_wrap" id="widget"><div class="tgme_page_widget"><?php
	if ( ($replytoid = getreplytoid($message)) && ($replytomessage = array_key_exists( $replytoid, $chat->messages )) ) {
		renderquotemessage( $chat, $chat->messages[$replytoid] );
	}
	rendermessage( $chat, $message );
	if ( array_key_exists( $message->id, $chat->threads ) ) {
	?><div class="tgme_widget_replies"><?php
		recursereplies( $chat, $message );
	?></div><?php
	}
?></div></div></div><div class="tgme_page_widget_actions_wrap" id="widget_actions_wrap"><div class="tgme_page_widget_actions" id="widget_actions"><div class="tgme_page_widget_actions_cont"><div class="tgme_page_widget_action_left" <?php echo (!$previousid ? 'style="visibility: hidden;"' : ''); ?>><div class="tgme_page_embed_btn"><a class="tgme_action_button_new" <?php echo ($previousid ? "href=\"{$tmeroot}/{$chat->id}/{$previousid}/\"" : ''); ?>><span class="tgme_action_button_label">⬅️ Previous</span></a></div></div><div class="tgme_page_widget_action_right" <?php echo (!$nextmessage ? 'style="visibility: hidden;"' : ''); ?>><div class="tgme_page_context_btn"><a class="tgme_action_button_new" <?php echo ($nextmessage ? "href=\"{$tmeroot}/{$chat->id}/{$nextmessage->id}/\"" : ''); ?>><span class="tgme_action_button_label">Next ➡️</span></a></div></div></div></div></div></div></div><script src="<?php echo $cdnroot; ?>/js/tgwallpaper.min.js"></script><script src="<?php echo $extracdnroot; ?>/js/tgme-init.js"></script></body></html><?php
	fclose($STDOUT);
	$previousid = $message->id;
}
