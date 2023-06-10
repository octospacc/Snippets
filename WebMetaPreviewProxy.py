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
Debug = False
# *--------------------* #

# Don't let the Zuck know this is a bot
UserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59'

AppMeta = {
	"Title": "Web Meta Preview Proxy",
	"Description": "A simple way to live.",
}

# <https://stackoverflow.com/a/51559006>
class ThreadedHTTPServer(ThreadingMixIn, HTTPServer):
	pass

class Handler(BaseHTTPRequestHandler):
	def do_GET(self):
		Res = {}
		Url = self.path[1:]
		try:
			Req = urlopen(Request(Url, headers={"User-Agent": UserAgent}))
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
		.Bold,
		dl > dt {{
			font-weight: bold;
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
	<p><span class="Bold">Proxied URL</span>: <code>{Url}</code></p>
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
		if Meta['Title']: New['Title'] = f"{Meta['Title']} | {AppMeta['Title']}"
		if Meta['Description']: New['Description'] = Meta['Description']
	return New

def MetaToHtmlHead(Meta:dict):
	Meta = DictHtmlSafe(Meta)
	return f'''\
{f'<title>{Meta["Title"]}</title><meta property="og:title" content="{Meta["Title"]}"/>' if Meta["Title"] else ''}
{f'<meta name="description" content="{Meta["Description"]}"/><meta property="og:description" content="{Meta["Description"]}"/>' if Meta["Description"] else ''}
	'''

def MetaToHtmlBody(Meta:dict):
	Meta = DictHtmlSafe(Meta)
	return f'''<dl>
{f'<dt>Title</dt>       <dd>{Meta["Title"]}</dd>'              if DictKeyIf(Meta, 'Title')       else ''}
{f'<dt>Description</dt> <dd>{Meta["Description"]}</dd>'        if DictKeyIf(Meta, 'Description') else ''}
{f'<dt>Image</dt>       <dd><img src="{Meta["Image"]}"/></dd>' if DictKeyIf(Meta, 'Image')       else ''}
{f'<dt>Type</dt>        <dd>{Meta["Type"]}</dd>'               if DictKeyIf(Meta, 'Type')        else ''}
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
		"Title": Title,
		"Description": Description,
		"Image": SoupAttrIf(Soup.find('meta', attrs={"property": "og:image"}), 'content'),
		"Type": SoupAttrIf(Soup.find('meta', attrs={"property": "og:type"}), 'content'),
	}

if __name__ == '__main__':
	try:
		Serve()
	except KeyboardInterrupt:
		pass
