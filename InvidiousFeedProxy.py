#!/usr/bin/env python3
from http.server import HTTPServer, BaseHTTPRequestHandler
from socketserver import ThreadingMixIn
from urllib.request import urlopen, Request
from urllib.error import HTTPError, URLError
import threading
import youtube_dl

# Requirements: urllib3 youtube_dl

Host = ('localhost', 8067)

def IsVideoShort(Id):
	if not Id:
		return None
	Info = youtube_dl.YoutubeDL().extract_info(Id, download=False)
	w, h = Info['width'], Info['height']
	if Info['duration'] < 60 and w < h and w/h == 0.5625: # == 9:16
		print(True)
		return True
	return False

def HandleFeed(Text):
	VideoId = None
	Lines = Text.splitlines()
	for i,Line in enumerate(Lines):
		if Line.strip().startswith('<yt:videoId>'):
			VideoId = Line.strip().split('>')[1].split('<')[0]
		if Line.strip().startswith('<title>') and IsVideoShort(VideoId) and not ('#shorts ' in Line or Line.strip().endswith('#shorts</title>')):
			Lines[i] = Line.replace('</title>', ' #shorts</title>')
	return '\n'.join(Lines)

def ReqHandle(Path):
	Res = {}
	try:
		Req = urlopen(Request(Path[1:], headers={'User-Agent': 'InvidiousFeedProxy'}))
		Res['Code'] = Req.code
		Res['Body'] = HandleFeed(Req.read().decode())
		Res['Content-Type'] = Req.headers['Content-Type']
	except (HTTPError, URLError) as e:
		print(e)
		Res['Code'] = Req.code
	return Res

# https://stackoverflow.com/a/51559006
class Handler(BaseHTTPRequestHandler):
	def do_GET(self):
		Res = ReqHandle(self.path)
		self.send_response(Res['Code'])
		self.send_header('Content-Type', Res['Content-Type'])
		self.end_headers()
		self.wfile.write(Res['Body'].encode())
class ThreadedHTTPServer(ThreadingMixIn, HTTPServer):
	pass
def Serve():
	ThreadedHTTPServer(Host, Handler).serve_forever()

if __name__ == '__main__':
	Serve()
