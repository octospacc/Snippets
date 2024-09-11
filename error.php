<?php
http_response_code($_GET['code']);
$realsite = '<p style="font-size: smaller;">
	(Are you were not looking for my <b>real web site</b> very likely?
	<a href="//www.octt.eu.org">www.octt.eu.org</a>)
</p>';
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<title><?php print $_GET['code'] ?></title>
	<style>
	body { text-align: center; margin: 20px; color: #e0e0e0; background-color: #1f1f1f; }
	a { color: lightpink; }
	p, div { width: calc(100vw - 40px); vertical-align: middle; }
	button { cursor: pointer; }
	#NoJs, #Action, #Fate { height: calc(100vh - 40px); display: table-cell; }
	#Action > button { font-size: xx-large; }
	#Action, #Fate { display: none; }
	</style>
</head>
<body>
<div id="NoJs">
	<p>
		When if you have eliminaminated the <b>Java Script</b>, the what ever remains.
		Nothing more than of composes the empty page, so <b>nothing</b> is more consumed.
		Relax yourself to the <b>HTML silence</b>.
	</p>
	<br/>
	<?php echo($realsite); ?>
</div>
<script>
document.body.innerHTML += `
<p id="Action">
	<button id="Do">What happened?</button>
</p>
<div id="Fate" hidden="true">
	<h1><?php print $_GET['code'] ?></h1>
	<br/>
	<p>
		<a href="javascript:Bgm.play()">
			<!-- Credits: https://missingnumber.com.mx/rhythm-heaven-nintendo-ds/ -->
			<img id="Gfx" src="/Res/LoveLab-Sad.gif"/>
		</a>
	</p>
	<br/>
	<p>
		I would have been loving to being able of loving you, but you are continuing trying break <b>my server</b>. And the site in it my <b>heart</b> too. I'm making sorry for <b>everything</b>. 💔
	</p>
	<br/>
	<?php echo($realsite); ?>
	<!-- Credits: https://www.youtube.com/watch?v=x9W7WNVHrWk -->
	<audio id="Bgm" src="/Res/LoveLab-Ext.webm"></audio>
</div>
`;
NoJs.remove();
Action.style.display = 'table-cell';
Bgm.volume = 0.5;
function Sad() {
	Action.remove();
	Fate.style.display = 'table-cell';
	Bgm.play();
};
if ((!navigator.cookieEnabled) || (document.cookie.search('WhatHappened=true') != -1)) {
	Sad();
};
Do.onclick = function(){
	document.cookie = 'WhatHappened=true; max-age=31536000';
	Sad();
};
</script>
</body>
</html>
