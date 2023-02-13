#!/usr/bin/env python3

# *----------------------------------------------------------------------* #
# | [ ShioriFeed 🔖 ]                                                    | #
# | Simple service for getting an Atom/RSS feed from your Shiori profile | #
# | v. 2023-02-13-r3, OctoSpacc                                          | #
# *----------------------------------------------------------------------* #

# *---------------------------------* #
# | Configuration                   | #
# *---------------------------------* #
Host = ('localhost', 8176)
Debug = False
# *---------------------------------* #

# External Requirements: urllib3

# TODO:
# - Atom feed
# - Actually valid RSS
# - Include content of links into XML
# - Include other XML metadata (author)
# - Write privacy policy
# - Fix the URL copy thing

# *-------------------------------------------------------------------------* #

import traceback
import json
from base64 import urlsafe_b64decode as b64decode, urlsafe_b64encode as b64encode
from html import escape as HtmlEscape
from http.server import HTTPServer, BaseHTTPRequestHandler
from socketserver import ThreadingMixIn
from urllib.request import urlopen, Request
from urllib.error import HTTPError, URLError
import threading

HomeTemplate = '''\
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<!--
			"bookmark" Emoji icon - Copyright 2021 Google Inc. All Rights Reserved.
			<https://fonts.google.com/noto/specimen/Noto+Color+Emoji/about>
			<https://scripts.sil.org/cms/scripts/page.php?item_id=OFL_web>
		-->
		<link rel="shortcut icon" type="image/png" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAKAAAACgCAMAAAC8EZcfAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAMAUExURUdwTPRBM/FLNfRBM/NEM+5QNvRBM85ONfRBM/RBM/VBM/RBM+dCM4hMM/RBM/RBM/RBM/RBM+1UN/RBM/VBM/NFNPRBM/RBM/RBM/VBM/RBM/RBM/RBM+xXN/RBM990PvRBM/RBM/RBM/RBM/RBM/RBM/RBM/FJNPRBM/RBM/RBM/RBM/RBM/RBM/RBM/RBM35HMYhMM4hMM4hMM/RBM/RBM/RBM/RBM/RBM/RBM/RBM/RBM/RBM/RBM/RBM/RBM/RBM/RBM/JHNIhMM4hMM/RBM/RBM/RBM91zPvRBM/RBM/RBM4hMM5JQNIhMM/RBM/RBM/RBM/RBM/RBM/RBM/RBM/RBM/RBM/RBM990PohMM4dLM990PodLM4hMM4pNM+B0Pt90PolMM4ZMM4hMM4hMM4hMM4hMM990PvRBM95zPfRBM4dMM990PohMM4hMM990PohMM990PvRBM990PvRBM990PohMM990PqdJM41OM990Pt90Pt90PvRBM5VRNIhMM990PohMM4hMM4hMM4hMM45MM4tNM990PohMM4hMM9JtPbFfOE0xKt90Pn5HMs5rPN90PthxPd50Pt90PrljOU0xKt90Ps1FM0wxKrxjOYRJMtVuPUwxKk0xKlQ0K7tjOV84LK9JM99MzvRBM990PohMM/zszodMM/Q/MeB0Pvzw0fzu0PZBM4lMM00xKtpyPfQ+MZtUNd1zPuB1Po5NM9BsPItNM/d9avRFNu1BM5dSNfzmyfVYSPrIrvVeTfZlU/VQQKJXNqZZNrpjOZFQNPiLd/zqzPzhxPzrzZROM8lpOvZqWfvWuvvdwPVSQvq4n/idh6pJM7xGM1MzK9ZvPdRuPbRgOL9lOcxrPPvQtPq9pPVLPPRIOfmslZ5WNXtFMd5DM8VnOveDb/iSfWg7LfmzmviXgvrHraNJM+ZCM8pqPNlEM/Z1Yviii/rLsLFHM81EM9JCMq5eN+F1Pl44LPmkjvztz7M6MPmjjJtKM4g4LZ04Lm5AMK1dN0dwTGi1UKgAAAEAdFJOUwBICTUPBPgB/d8T/gL8B/o/7w312QXnKX9r6vIjCjH6xdbMFsLtixu9ZE3PLaxYvwQlB+4rtnE6j6bT5JSdHBj89yAUVVzcojGbYVLzDNt2hEOXZ8m5JlWw5kikRDizGcP04z6HejS5qLNwn54T6myc9SaoznvvXDzijmF7jnrqxtbTyKcb+a9baW+uZzC22fYt+eChs/lIh+rWHJB6TqtP7u7//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////wAl04dNAAAKGUlEQVR42u2cd1AbVx7HVxI6kBDIBiyLJjC9Y7rBgA0xxhSbXkzcu+MWx44vPZfitOu95Pos0i6WqQKBAZtiA3ZccAvujnEj8SW55JLT1cytyorFaFcr6a20M8fvD8OInXmfeb/2fb99FgTN2Iyxznates6DzXzL1q1f/4PnnFnL98y6esxWPsXWTfR/ul5nAdueYSfgO+vrDbZ/FRvdvGudCgesX/niHPYBrvrR+OUGnHD9S/6sA/xjd2PrlWYcMeBlJ7YBJqyQI+rBDiPh07vYRrhkg1yOnLrS1sBWQuctW92wTRxrNhKyzstPbEqSy+WDeK4EvMy6TJm/ZAXm5tt4IAa8xL5qs/o7GGErTnjgRfaVw4Q1RMKVq9hHePBJLFVaPzQQ7mdhX35CSziA5/I2DzYSYrn8tzZDojzFQt1wEItD9XFDS/npMhYSJrwhR058YHDyd5ezkHD1islE+cn3TD4iyi6riUp2dxTht4vlyGCzfgt/k2DigWDpvBxXsSSdZzfBv2wZsbHN3+SGh2HDoxfmT3s8JsUF1pkgyk4Hum0HDmwLJ6byVjnSrg/Dy6+ufty9nAwYt4y5dsBzf+c1LcnPiHu4cTcehm0DOxOnPl4hgSdtB5dxvrW/XW+oeYTDkmizGx6Gg0kbiY8LwzwJfHBOIeOF+e1HKhOtdy2mGxrH6hsa2m4jOwlR6Be7GJ5iXjFMZ6zbGC709/+YKLH3yuXtx5vbrrTLd09GYVol/LjFMqxs30O6cW1Qv44gDpa/jbU8devtduzHCwbn8/ME0/hgcSqz5+iNu5Fv2vAD5zpCZ6vTCmwEwf6Rr9DXQg/pbNiEScqY1dGb3NRXjEfi134pwv+Q+KYcN7d3tR9k7xDDJi0wm9ks2TrZebXTI2PzrSs2Em7FPpTFu8Ik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"/>
		<title>ShioriFeed 🔖</title>
		<meta name="description" content="Simple service for getting an Atom/RSS feed from your Shiori profile"/>
		<meta property="og:title" content="ShioriFeed 🔖"/>
		<meta property="og:description" content="Simple service for getting an Atom/RSS feed from your Shiori profile"/>
		<style>
			* { box-sizing: border-box; }
			body {
				color: #232323;
				background: #eeeeee;
				font-family: "Source Sans Pro", sans-serif;
				margin: 0px;
				padding-top: 24px;
				padding-bottom: 24px;
				padding-left: 10%;
				padding-right: 10%;
				word-break: break-word;
				user-select: none;
				-ms-user-select: none;
				-moz-user-select: none;
				-khtml-user-select: none;
				-webkit-user-select: none;
				-webkit-touch-callout: none;
			}
			a { color: #f44336; }
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
			span.Separator {
				display: inline-block;
				width: 0.25em;
				height: 0.25em;
				margin: 0.25em;
				vertical-align: middle;
				background: #292929;
			}
			@media (prefers-color-scheme: dark) {
				body {
					color: #ffffff;
					background: #292929;
				}
				span.Separator { background: #eeeeee; }
			}
			/* {{PostCss}} */
		</style>
	</head>
	<body>
		<h2>ShioriFeed 🔖</h2>
		<p class="PostObscure">
			Enter the details of your account on a
			<a href="https://github.com/go-shiori/">Shiori</a>
			server to get an Atom/RSS feed link.
		</p>
		<p class="PostObscure">
			<small>Note: still a work-in-progress!</small>
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
			<span>v. 2023-02-13-r3</span>
			<span class="Separator"></span>
			<a href="https://gitlab.com/octospacc/Snippets/-/blob/main/ShioriFeed.py">Source Code</a>
		</p>
		<script>
			var Box = document.querySelector('textarea');
			if (Box) {
				//BoxFocused = false;
				Box.value = location.origin + Box.value.substring('http[s]://<THIS SHIORIFEED SERVER ADDRESS>'.length);
				//Box.onfocusout = function() { console.log(1); BoxFocused = false; };
				//Box.onfocusin = function() { console.log(2); BoxFocused = true; };
				Box.onclick = function() {
					try {
						//if (BoxFocused) {
							navigator.clipboard.writeText(Box.value);
							alert('Copied to clipboard!');
						//};
					} catch(e) {};
				};
			};
		</script>
	</body>
</html>
'''

def SessionHash(Remote, Username, Password):
	return f'{hash(Remote)}{hash(Username)}{hash(Password)}'

#def GetContent(Id, Remote, Session):
#	try:
#		
#	except Exception:
#		

def MkFeed(Data, Remote, Username, Password, Type="RSS"):
	Feed = ''
	Date = Data['bookmarks'][0]['modified'] if Data['bookmarks'] else ''
	for Mark in Data['bookmarks']:
		#if Mark['hasContent']:
		Link = f"{Remote}/bookmark/{Mark['id']}/content"
		ImgLink = f"{Remote}/bookmark/{Mark['id']}/thumb"
		Cover = f'<![CDATA[<a href="{Link}"><img src="{ImgLink}"/></a>]]>' if Mark['imageURL'] else ''
		#elif Mark['hasArchive']:
		#	Link = f"{Remote}/bookmark/{Mark['id']}/archive"
		#else:
		#	Link = Mark['url']
		Feed += f'''
<item>
	<title>{HtmlEscape(Mark['title'])}</title>
	<description>{Cover}{HtmlEscape(Mark['excerpt'])}</description>
	<!-- <content:encoded>HtmlEscape(We try fetching the content here)</content:encoded> -->
	<link>{Link}</link>
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

def MkUrl(Post, Type="RSS"):
	Args = {}
	#Args = Post.split('&')
	for Arg in Post.split('&'):
		Arg = Arg.split('=')
		Args.update({Arg[0]: Arg[1]})
	return f'''\
http[s]://<THIS SHIORIFEED SERVER ADDRESS>\
/{Args['Remote']}\
/{b64encode(Args['Username'].encode()).decode()}\
/{b64encode(Args['Password'].encode()).decode()}\
/{Type}.xml'''

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
			Shift = 1 if Args[-1].lower().startswith(('atom.xml', 'rss.xml')) else 0
			Remote = '/'.join(Args[:-(2+Shift)])
			Username = b64decode(Args[-(2+Shift)]).decode()
			Password = b64decode(Args[-(1+Shift)]).decode()
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
				Rs['Body'] = MkFeed(json.loads(Rq.read().decode()), Remote, Username, Password)
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
	Here's your RSS feed:
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
