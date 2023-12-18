var postBody = '';
var allowedSender = '+39**********'; 

Android.onSmsReceived = function(message, sender) {
	if (sender === allowedSender) {
		log.innerHTML += '<br>' + message;
		parseMessage(message);
	}
}

function parseMessage(message) {
	var message = message.trim();
	var messageLower = message.toLowerCase();
	if (messageLower.slice(0, '<post>'.length) === '<post>') {
		log.innerHTML += '<br>Start new message';
		postBody = '';
		message = message.slice('<post>'.length);
	}
	postBody += message.trim();
	if (messageLower.slice(-'</post>'.length) === '</post>') {
		log.innerHTML += '<br>End new message';
		postBody = postBody.slice(0, -'</post>'.length);
		sendPost();
	}
}

function sendPost() {
	var req = new XMLHttpRequest();
	req.open('POST', 'http://192.168.1.125:8056/webhook/...', true);
	log. innerHTML+= '<br>Sending post'; 
	req.send(postBody);
}
