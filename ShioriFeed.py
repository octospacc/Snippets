#!/usr/bin/env python3

# *----------------------------------------------------------------------* #
# | [ ShioriFeed 🔖 ]                                                    | #
# | Simple service for getting an Atom/RSS feed from your Shiori profile | #
# | v. 2023-02-13-r2, OctoSpacc                                          | #
# *----------------------------------------------------------------------* #

# *---------------------------------* #
# | Configuration                   | #
# *---------------------------------* #
Host = ('localhost', 8176)
Debug = True
# *---------------------------------* #

# External Requirements: urllib3

# *-------------------------------------------------------------------------* #

import traceback
import json
from base64 import urlsafe_b64decode, urlsafe_b64encode
from html import escape as HtmlEscape
from http.server import HTTPServer, BaseHTTPRequestHandler
from socketserver import ThreadingMixIn
from urllib.request import urlopen, Request
from urllib.error import HTTPError, URLError
import threading

# Usage: http[s]://<This Server>/http[s]://<Shiori Server>/<Shiori Username (in base64url)>/<Shiori Password (in base64url)>

HomeTemplate = '''\
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<title>ShioriFeed 🔖</title>
		<meta name="description" content="Simple service for getting an Atom/RSS feed from your Shiori profile"/>
		<meta property="og:title" content="ShioriFeed 🔖"/>
		<meta property="og:description" content="Simple service for getting an Atom/RSS feed from your Shiori profile"/>
		<style>
			* { box-sizing: border-box; }
			body {
				margin: 0px;
				padding-top: 24px;
				padding-bottom: 24px;
				padding-left: 10%;
				padding-right: 10%;
				font-family: sans-serif;
				word-break: break-word;
				user-select: none;
				-ms-user-select: none;
				-moz-user-select: none;
				-khtml-user-select: none;
				-webkit-user-select: none;
				-webkit-touch-callout: none;
			}
			form > label { padding: 8px; }
			form > label > span { padding-bottom: 4px; }
			form > label, form > label > span {
				display: inline-block;
				width: 100%;
			}
			input {
				width: 100%;
				height: 2em;
			}
			input[type="submit"] { font-size: large; }
			textarea {
				width: 100%;
				height: 5em;
				font-size: large;
				resize: none;
			}
			details {
				background: lightgray;
				padding: 8px;
			}
			details > summary > h4 { display: inline; }
			/* {{PostCss}} */
		</style>
	</head>
	<body>
		<h2>ShioriFeed 🔖</h2>
		<p class="PostObscure">
			Enter the details of your account on a
			<a href="https://github.com/go-shiori/">Shiori</a>
			server to get an Aotm/RSS feed link.
		</p>
		<br />
		<!-- {{PostResult}} -->
		<p class="PostObscure">
			<form action="./" method="POST">
				<label class="PostObscure">
					<span>Server <small>(must start with protocol prefix)</small>:</span>
					<input type="text" name="Remote" placeholder="http[s]://..."/>
				</label>
				<br />
				<label class="PostObscure">
					<span>Username:</span>
					<input type="text" name="Username" placeholder="erre"/>
				</label>
				<br />
				<label class="PostObscure">
					<span>Password:</span>
					<input type="password" name="Password" placeholder="**********"/>
				</label>
				<br />
				<label class="PostObscure">
					<span>&nbsp;</span>
					<input type="submit" value="Submit"/>
				</label>
			</form>
		</p>
		<br />
		<!--
		<p>
			<details>
				<summary>
					<h4>Privacy Policy</h4>
				</summary>
				<p>
				<ul>
					<li>
						
					</li>
				</ul>
			</details>
		</p>
		-->
		<p>
			<span>v. 2023-02-13</span>
			▪️
			<a href="https://gitlab.com/octospacc/Snippets/-/blob/main/ShioriFeed.py">Source Code</a>
		</p>
		<script>
			var Box = document.querySelector('textarea');
			if (Box) {
				Box.value = location.origin + Box.value.substring('http[s]://<THIS SHIORIFEED SERVER ADDRESS>'.length);
			};
			Box.onclick = function() {
				try {
					navigator.clipboard.writeText(Box.value);
					alert('Copied to clipboard!');
				} catch(e) {};
			};
		</script>
	</body>
</html>
'''

def SessionHash(Remote, Username, Password):
	return f'{hash(Remote)}{hash(Username)}{hash(Password)}'

def MkFeed(Data, Remote, Username):
	Feed = ''
	Date = Data['bookmarks'][0]['modified'] if Data['bookmarks'] else ''
	for Mark in Data['bookmarks']:
		Feed += f'''
<item>
	<title>{HtmlEscape(Mark['title'])}</title>
	<description>{HtmlEscape(Mark['excerpt'])}</description>
	<link>{Remote}/bookmark/{Mark['id']}/content</link>
	<pubDate>{Mark['modified']}</pubDate>
	<guid isPermaLink="false">{Mark['id']}</guid>
</item>
		'''
	return f'''\
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/">
	<channel>
		<title>ShioriFeed ({HtmlEscape(Username)}) 🔖</title>
		<pubDate>{Date}</pubDate>
		<lastBuildDate>{Date}</lastBuildDate>
		{Feed}
	</channel>
</rss>
'''

def MkUrl(Post):
	Args = {}
	#Args = Post.split('&')
	for Arg in Post.split('&'):
		Arg = Arg.split('=')
		Args.update({Arg[0]: Arg[1]})
	return f'''\
http[s]://<THIS SHIORIFEED SERVER ADDRESS>\
/{Args['Remote']}\
/{urlsafe_b64encode(Args['Username'].encode()).decode()}\
/{urlsafe_b64encode(Args['Password'].encode()).decode()}/'''

def GetSession(Remote, Username, Password):
	try:
		Rq = urlopen(Request(f'{Remote}/api/login',
			data=json.dumps({'username': Username, 'password': Password, 'remember': True, 'owner': True}).encode(),
			headers={'User-Agent': f'ShioriFeed at {Host[0]}'}))
		if Rq.code == 200:
			Data = {SessionHash(Remote, Username, Password): json.loads(Rq.read().decode())['session']}
			Sessions.update(Data)
			return {
				'Code': 200,
				'Body': Data}
		else:
			return {
				'Code': Rq.code,
				'Body': f'[{Rq.code}] External Server Error\n\n{Rq.read().decode()}'}
	except Exception: #as Ex: #(HTTPError, URLError) as Ex:
		#print(traceback.format_exc())
		return {
			'Code': 500,
			'Body': '[500] Internal Server Error' + (f'\n\n{traceback.format_exc()}' if Debug else '')}

def RqHandle(Path, Attempt=0):
	try:
		Rs = {}
		Args = Path.strip().removeprefix('/').removesuffix('/').strip().split('/')
		if Args[0] == '':
			return {
				'Code': 200,
				'Body': HomeTemplate,
				'Content-Type': 'text/html'}
		else:
			Remote = '/'.join(Args[:-2])
			Username = urlsafe_b64decode(Args[-2]).decode()
			Password = urlsafe_b64decode(Args[-1]).decode()
			if not SessionHash(Remote, Username, Password) in Sessions:
				TrySession = GetSession(Remote, Username, Password)
				if TrySession['Code'] != 200:
					return TrySession
			Rq = urlopen(Request(f'{Remote}/api/bookmarks', headers={
				'X-Session-Id': Sessions[SessionHash(Remote, Username, Password)],
				'User-Agent': f'ShioriFeed at {Host[0]}'}))
			Rs['Code'] = Rq.code
			# Shiori got us JSON data, parse it and return our result
			if Rq.code == 200:
				Rs['Body'] = MkFeed(json.loads(Rq.read().decode()), Remote, Username)
				Rs['Content-Type'] = 'application/xml'
			# We probably got an expired Session-Id, let's try to renew it
			elif Rq.code == 500 and Attempt < 1:
				TrySession = GetSession(Remote, Username, Password)
				if TrySession['Code'] != 200:
					return TrySession
				return ReqHandle(Path, Attempt+1)
			else:
				Rs['Body'] = f'[{Rq.code}] External Server Error\n\n{Rq.read().decode()}'
		return Rs
	except Exception: #as Ex: #(HTTPError, URLError) as Ex:
		#print(traceback.format_exc())
		#Rs['Code'] = 500
		#Rs['Body'] = f'[500] Internal Server Error\n\n{traceback.format_exc()}'
		#Rs['Body'] = f'[500] Internal Server Error'
		#Rs['Content-Type'] = 'text/plain'
		return {
			'Code': 500,
			'Body': '[500] Internal Server Error' + (f'\n\n{traceback.format_exc()}' if Debug else '')}

class Handler(BaseHTTPRequestHandler):
	def do_GET(self):
		Rs = RqHandle(self.path)
		self.send_response(Rs['Code'])
		self.send_header('Content-Type', Rs['Content-Type'] if 'Content-Type' in Rs else 'text/plain')
		self.end_headers()
		self.wfile.write(Rs['Body'].encode())
	def do_POST(self):
		try:
			if self.path == '/':
				Body = HomeTemplate.replace('<!-- {{PostResult}} -->', f'''
<p>
	Here's your Atom feed:
	<textarea readonly="true">{MkUrl(self.rfile.read(int(self.headers['Content-Length'])).decode())}</textarea>
</p>
<br />
				''').replace('/* {{PostCss}} */', '.PostObscure { opacity: 0.5; }')
				self.send_response(200)
				self.send_header('Content-Type', 'text/html')
				self.end_headers()
				self.wfile.write(Body.encode())
			else:
				self.send_response(400)
				self.send_header('Content-Type', 'text/plain')
				self.end_headers()
				self.wfile.write(b'[400] Bad Request')
		except Exception:
			self.send_response(500)
			self.send_header('Content-Type', 'text/plain')
			self.end_headers()
			self.wfile.write(('[500] Internal Server Error' + (f'\n\n{traceback.format_exc()}' if Debug else '')).encode())
	# https://stackoverflow.com/a/3389505
	def log_message(self, format, *args):
		return
# https://stackoverflow.com/a/51559006
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
