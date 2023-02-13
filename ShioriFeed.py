#!/usr/bin/env python3
import traceback
import json
from base64 import urlsafe_b64decode, urlsafe_b64encode
from html import escape as HTMLEscape
from http.server import HTTPServer, BaseHTTPRequestHandler
from socketserver import ThreadingMixIn
from urllib.request import urlopen, Request
from urllib.error import HTTPError, URLError
import threading

# Requirements: urllib3
# Usage: http[s]://<This Server>/http[s]://<Shiori Server>/<Shiori Username (in base64url)>/<Shiori Password (in base64url)>
Host = ('localhost', 8176)

HomeTemplate = '''
<!DOCTYPE html>
<html>
	<head>
		<title>ShioriFeed</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<style>
			body {
				margin: 0px;
				padding-top: 24px;
				padding-bottom: 24px;
				padding-left: 10%;
				padding-right: 10%;
				font-family: sans-serif;
				ford-break: break-word;
				user-select: none;
				-ms-user-select: none;
				-moz-user-select: none;
				-khtml-user-select: none;
				-webkit-user-select: none;
				-webkit-touch-callout: none;
			}
			details {
				background: lightgray;
				padding: 8px;
			}
			label > span {
				display: inline-block;
				padding-bottom: 4px;
			}
			input {
				width: 100%;
				height: 2em;
			}
			input[type="submit"] {
				width: calc(100% + 8px);
				font-size: large;
			}
			textarea {
				width: 100%;
				height: 5em;
				resize: none;
			}
		</style>
	</head>
	<body>
		<h2>ShioriFeed</h2>
		<p>
			Enter details about your account on a
			<a href="https://github.com/go-shiori/">Shiori</a>
			server to get an RSS feed link.
		</p>
		<br />
		{{PostResult}}
		<p>
		<form action="/" method="POST">
			<label>
				<span>
					Server <small>(must start with protocol prefix)</small>:
				</span>
				<br />
				<input type="text" name="Remote" placeholder="http[s]://..."/>
			</label>
			<br />
			<label>
				<span>
					Username:
				</span>
				<br />
				<input type="text" name="Username" placeholder="erre"/>
			</label>
			<br />
			<label>
				<span>
					Password:
				</span>
				<br />
				<input type="password" name="Password" placeholder="**********"/>
			</label>
			<br />
			<input type="submit" value="Submit"/>
		</form>
		</p>
		<br />
		<p>
			<details>
				<summary>
					Privacy Policy
				</summary>
				<ul>
					<li>
						
					</li>
				</ul>
			</details>
		</p>
		<p>
			<a href="https://gitlab.com/octospacc/Snippets/-/blob/main/ShioriFeed.py">Source Code</a>
		</p>
	</body>
</html>
'''

def MkFeed(Data, Remote, Username):
	Feed = ''
	if not Data['bookmarks']:
		return '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/"></rss>'
	Date = Data['bookmarks'][0]['modified']
	for Mark in Data['bookmarks']:
		Feed += f'''
<item>
	<title>{HTMLEscape(Mark['title'])}</title>
	<description>{HTMLEscape(Mark['excerpt'])}</description>
	<link>{Remote}/bookmark/{Mark['id']}/content</link>
	<pubDate>{Mark['modified']}</pubDate>
	<guid isPermaLink="false">{Mark['id']}</guid>
</item>
		'''
	return f'''\
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/">
	<channel>
		<title>ShioriFeed ({HTMLEscape(Username)})</title>
		<pubDate>{Date}</pubDate>
		<lastBuildDate>{Date}</lastBuildDate>
		{Feed}
	</channel>
</rss>
'''

def GetSession(Remote, Username, Password):
	try:
		Rq = urlopen(Request(f'{Remote}/api/login',
			data=json.dumps({'username': Username, 'password': Password, 'remember': True, 'owner': True}).encode(),
			headers={'User-Agent': f'ShioriFeed at {Host[0]}'}))
		if Rq.code == 200:
			Data = {f'{Remote}/{Username}/{Password}': json.loads(Rq.read().decode())['session']}
			Sessions.update(Data)
			return Data
	except Exception as Ex: #(HTTPError, URLError) as Ex:
		print(traceback.format_exc())
	return False

def RqHandle(Path, Attempt=0):
	Rs = {}
	try:
		RqItems = Path.strip().removeprefix('/').removesuffix('/').strip().split('/')
		if RqItems[0] == '':
			Rs['Code'] = 200
			Rs['Body'] = HomeTemplate.replace('{{PostResult}}', '')
			Rs['Content-Type'] = 'text/html'
		else:
			Remote = '/'.join(RqItems[:-2])
			Username = urlsafe_b64decode(RqItems[-2]).decode()
			Password = urlsafe_b64decode(RqItems[-1]).decode()
			if not f'{Remote}/{Username}/{Password}' in Sessions:
				GetSession(Remote, Username, Password)
			Rq = urlopen(Request(f'{Remote}/api/bookmarks', headers={
				'X-Session-Id': Sessions[f'{Remote}/{Username}/{Password}'],
				'User-Agent': f'ShioriFeed at {Host[0]}'}))
			Rs['Code'] = Rq.code
			if Rq.code == 200:
				Rs['Body'] = MkFeed(json.loads(Rq.read().decode()), Remote, Username)
				Rs['Content-Type'] = 'application/xml'
			elif Rq.code == 500 and Attempt < 1:
				GetSession(Remote, Username, Password)
				return ReqHandle(Path, Attempt+1)
			else:
				Rs['Body'] = f'[{Rq.code}] External Server Error\n\n{Rq.read().decode()}'
				Rs['Content-Type'] = 'text/plain'
	except Exception as Ex: #(HTTPError, URLError) as Ex:
		print(traceback.format_exc())
		Rs['Code'] = 500
		#Rs['Body'] = f'[500] Internal Server Error\n\n{traceback.format_exc()}'
		Rs['Body'] = f'[500] Internal Server Error'
		Rs['Content-Type'] = 'text/plain'
	return Rs

# https://stackoverflow.com/a/51559006
class Handler(BaseHTTPRequestHandler):
	def do_GET(self):
		Rs = RqHandle(self.path)
		self.send_response(Rs['Code'])
		self.send_header('Content-Type', Rs['Content-Type'])
		self.end_headers()
		self.wfile.write(Rs['Body'].encode())
	def do_POST(self):
		if self.path == '/':
			Params = self.rfile.read(int(self.headers['Content-Length']))
			self.send_response(200)
			self.send_header('Content-Type', 'text/html')
			self.end_headers()
			self.wfile.write(HomeTemplate.replace('{{PostResult}}', f'''
<p>
	Here's your RSS feed:
	<textarea readonly="true"> </textarea>
</p>
<br />
			''').encode())
		else:
			self.send_response(400)
			self.send_header('Content-Type', 'text/plain')
			self.end_headers()
			self.wfile.write(b'[400] Bad Request')
	def log_request(self, code='-', size='-'):
		return
class ThreadedHTTPServer(ThreadingMixIn, HTTPServer):
	pass
def Serve():
	ThreadedHTTPServer(Host, Handler).serve_forever()

if __name__ == '__main__':
	Sessions = {}
	try:
		Serve()
	except KeyboardInterrupt:
		pass
