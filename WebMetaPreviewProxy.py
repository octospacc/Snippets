#!/usr/bin/env python3
from html import escape as HtmlEscape
from http.server import HTTPServer, BaseHTTPRequestHandler
from socketserver import ThreadingMixIn
from urllib.request import urlopen, Request
from urllib.error import HTTPError, URLError
from bs4 import BeautifulSoup

# *--------------------* #
# | Configuration      | #
# *--------------------* #
Host = ('localhost', 8080)
Debug = True
# *--------------------* #

# Must be stealth about this since there are some nasty servers in the world! (Zuck, Musk, ...)
ProxyHeaders = {
	"User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36",
	"Sec-Fetch-Site": "same-origin",
}

AppMeta = {
	"SiteName": "Web Meta Preview Proxy",
	"Title": "Web Meta Preview Proxy",
	"Description": "HTML metadata provided in a proxied and paginated form.",
}

# <https://stackoverflow.com/a/51559006>
class ThreadedHTTPServer(ThreadingMixIn, HTTPServer):
	pass

class Handler(BaseHTTPRequestHandler):
	def do_GET(self):
		Res = {}
		Url = self.path[1:]
		try:
			Req = urlopen(Request(Url, headers=ProxyHeaders))
			Res['Code'] = 200
			Res['Body'] = MakePage(Code=Req.code, Url=Url, Meta=HtmlToMeta(Req.read().decode()))
		except (HTTPError, URLError) as e:
			print(e)
			Res['Code'] = 500
			Res['Body'] = MakePage(Url=Url)
		self.send_response(Res['Code'])
		self.send_header('Content-Type', 'text/html')
		self.end_headers()
		self.wfile.write(Res['Body'].encode())

def Serve():
	ThreadedHTTPServer(Host, Handler).serve_forever()

#def SureList(Val):
#	return (Val if type(Val) == list else [Val])

def DictKeyIf(Dict:dict, Key:str):
	if Key in Dict:
		return Dict[Key]

def SoupAttrIf(Obj, Attr:str, Else=None):
	if Obj:
		if hasattr(Obj, Attr) and getattr(Obj, Attr):
			return getattr(Obj, Attr)
		return Obj.get(Attr)
		#if Attr in Obj and Obj[Attr]:
		#	return Obj[Attr]
	else:
		return Else

#def TryVals(Vals:list, Else=None):
#	for Val in SureList(Vals):
#		if Val:
#			return Val
#	return Else

#def DictJoin(a:dict=None, b:dict=None):
#	c = dict(a) if a else {}
#	c.update(b) if b else {}
#	return c

def DictHtmlSafe(Dict:dict):
	New = {}
	for Key in Dict:
		if Dict[Key]:
			New[Key] = HtmlEscape(Dict[Key])
	return New

def MakePage(Meta:dict=None, Code:int=None, Url:str=None):
	return f'''<!DOCTYPE html><html>
<head>
	<meta charset="utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<style>
		body {{
			word-break: break-all;
		}}

		.Bold,
		dl > dt {{
			font-weight: bold;
			padding-top: 1.00em;
			padding-bottom: 0.50em;
		}}
	</style>
	{MetaToHtmlHead(ContextAppMeta(Meta))}
</head>
<body>
	<!--
	<form method="GET" action="/">
		<input type="text" placeholder="URL" value="{Url if Url else ''}"/>
		<button type="submit">Go</button>
	</form>
	-->
	<p><span class="Bold">Proxied URL</span>: <code><a href="{Url}">{Url}</a></code></p>
	<p><span class="Bold">Proxied HTTP Response</span>: <code>{Code}</code></p>
	{MetaToHtmlBody(Meta) if Meta else '<p>Could not retrieve any metadata from the requested URL.</p>'}
	<p>
		<span>[<a href="https://gitlab.com/octospacc/Snippets/-/blob/main/WebMetaPreviewProxy.py">Source Code</a>]</span>
	</p>
</body>
</html>'''

def ContextAppMeta(Meta:dict=None):
	#New = DictJoin(AppMeta, Meta)
	New = dict(AppMeta)
	if Meta:
		if Meta['SiteName']:    New['SiteName'] = f"{Meta['SiteName']} | {AppMeta['SiteName']}"
		if Meta['Title']:       New['Title'] = f"{Meta['Title']} | {AppMeta['Title']}"
		if Meta['Description']: New['Description'] = f"{Meta['Description']} | {AppMeta['Description']}"
		if Meta['Image']:       New['Image'] = Meta['Image']
	return New

def MetaToHtmlHead(Meta:dict):
	Meta = DictHtmlSafe(Meta)
	return f'''\
{f'<meta property="og:site_name" content="{Meta["SiteName"]}"/>' if DictKeyIf(Meta, 'SiteName') else ''}
{f'<title>{Meta["Title"]}</title><meta property="og:title" content="{Meta["Title"]}"/>' if DictKeyIf(Meta, 'Title') else ''}
{f'<meta name="description" content="{Meta["Description"]}"/><meta property="og:description" content="{Meta["Description"]}"/>' if DictKeyIf(Meta, 'Description') else ''}
{f'<meta property="og:image" content="{Meta["Image"]}"/>' if DictKeyIf(Meta, 'Image') else ''}
	'''

def MetaToHtmlBody(Meta:dict):
	Meta = DictHtmlSafe(Meta)
	return f'''<dl>
{f'<dt>Site Name</dt>   <dd>{Meta["SiteName"]}</dd>'            if DictKeyIf(Meta, 'SiteName')    else ''}
{f'<dt>Title</dt>       <dd>{Meta["Title"]}</dd>'               if DictKeyIf(Meta, 'Title')       else ''}
{f'<dt>Description</dt> <dd>{Meta["Description"]}</dd>'         if DictKeyIf(Meta, 'Description') else ''}
{f'<dt>Image</dt>       <dd><img src="{Meta["Image"]}"/></dd>'  if DictKeyIf(Meta, 'Image')       else ''}
{f'<dt>Type</dt>        <dd>{Meta["Type"]}</dd>'                if DictKeyIf(Meta, 'Type')        else ''}
{f'<dt>Html</dt>        <dd><code>{Meta["_Html_"]}</code></dd>' if DictKeyIf(Meta, '_Html_') and Debug else ''}
</dl>'''

def HtmlToMeta(Html:str):
	Soup = BeautifulSoup(Html, 'html.parser')

	Title = SoupAttrIf(Soup.find('meta', property='og:title'), 'content')
	if not Title:
		Title = SoupAttrIf(Soup.find('title'), 'text')

	Description = SoupAttrIf(Soup.find('meta', attrs={"property": "og:description"}), 'content')
	if not Description:
		Description = SoupAttrIf(Soup.find('meta', attrs={"name": "description"}), 'content')

	return {
		"SiteName": SoupAttrIf(Soup.find('meta', attrs={"property": "og:site_name"}), 'content'),
		"Title": Title,
		"Description": Description,
		"Image": SoupAttrIf(Soup.find('meta', attrs={"property": "og:image"}), 'content'),
		"Type": SoupAttrIf(Soup.find('meta', attrs={"property": "og:type"}), 'content'),
		"_Html_": Html,
	}

if __name__ == '__main__':
	try:
		Serve()
	except KeyboardInterrupt:
		pass
