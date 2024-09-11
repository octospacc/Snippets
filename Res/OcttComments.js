(async function(){
const JSDOM = require('jsdom').JSDOM;
const observerPolyfill = 'window.ResizeObserver = (class ResizeObserver { observe(){}; unobserve(){}; disconnect(){}; });';
const data = JSON.parse(process.argv[2]);
data.pageSlug = data.pageSlug.replace(/^\/+/,'', '');
const frameUrl = ('https://giscus.app/it/widget?' + Object.entries({ ...data,
	reactionsEnabled: 1,
	strict: 1,
	emitMetadata: 0,
	inputPosition: 'bottom',
	origin: (data.baseUrl + '/' + data.pageSlug),
	backLink: (data.baseUrl + '/' + data.pageSlug),
	term: data.pageSlug,
}).map(prop => `${prop[0]}=${encodeURIComponent(prop[1])}`).join('&'));
/*
const pageSlug = 'it/miscellanea/Sul-sitoctt/';
const pageUrl = ('https://sitoctt.octt.eu.org/' + pageSlug);
const frameUrl = ('https://giscus.app/it/widget?' + Object.entries({
	origin: pageUrl,
	theme: 'noborder_light',
	reactionsEnabled: 1,
	emitMetadata: 0,
	inputPosition: 'top',
	repo: 'octospacc/sitoctt',
	repoId: 'R_kgDOHbCR4A',
	category: 'Comments',
	categoryId: 'DIC_kwDOHbCR4M4CiAIZ',
	strict: 1,
	backLink: pageUrl,
	term: pageSlug,
}).map(prop => `${prop[0]}=${encodeURIComponent(prop[1])}`).join('&')); */
const frameHtml = (await (await fetch(frameUrl)).text())
	.replace('<head>', `<head>
		<script>${observerPolyfill}</script><style> form { display: none; } </style>
		<link rel="stylesheet" href="https://giscus.app/themes/${data.theme}.css"/>`)
	.replace('<body>', `<body>
		<h3>Please enable JavaScript for
			<code>${data.baseUrl.split('//')[1].split('/')[0]}</code>
			and <code>giscus.app</code> to post.</h3>`);
const frameWindow = (new JSDOM(frameHtml, { url: frameUrl, runScripts: "dangerously", resources: "usable" })).window;
while (!frameWindow.document.querySelector('form')) {
	(await (new Promise(resolve => setTimeout(resolve, 100))));
};
const result = frameWindow.document.documentElement.outerHTML;
console.log(result);
})();
