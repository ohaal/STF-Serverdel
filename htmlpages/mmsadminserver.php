<?php
// prevent the server from timing out
set_time_limit(0);

// include the web sockets server script (the server is started at the far bottom of this file)
require 'class.PHPWebSocket.php';
require_once 'config.php';
require_once 'mms.php';

/////////////////////////////////////////
// HELPER FUNCTIONS
/////////////////////////////////////////
function SendToAllClients($type, $data) {
	global $Server;
	foreach ( $Server->wsClients as $id => $client ) {
		SendToClient($id, $type, $data);
	}
}
function SendToAllClientsExcept($clientID, $type, $data) {
	global $Server;
	foreach ( $Server->wsClients as $id => $client ) {
		if ( $id != $clientID ) {
			SendToClient($id, $type, $data);
		}
	}
}
function SendToClient($clientID, $type, $data) {
	global $Server;
	$Server->wsSend($clientID, $type.' '.json_encode($data));
}

/////////////////////////////////////////
// EVENTS
/////////////////////////////////////////
function eventChatMsg($clientID, $ip, $params) {
	global $userlist;
	$message = $params[0];
	
	$data = array(
		'clientid' => $clientID,
		'ip' => $ip,
		'nickname' => $userlist[$clientID],
		'message' => $message
	);
	SendToAllClients('chatmsg', $data);
}

// This is triggered when an MMS is received
function eventUpdateMms($clientID, $ip) {
	global $Server;
	$mms = new mmsReaction();
	$Server->log('Received poke from self ('.$ip.'). Telling all clients to review MMS lists.');
	
	$data = array(
		'accepted' => $mms->getAccepted(),
		'declined' => $mms->getDeclined(),
		'queued' => $mms->getQueued()
	);
	SendToAllClientsExcept($clientID, 'updatemmslist', $data);
}

// This is triggered when a user accepts an MMS
function eventSetAccepted($clientID, $ip, $params) {
	$mms = new mmsReaction();
	$msgid = $params[0];
	
	$success = $mms->setAccepted($msgid);
	
	if ($success) {
		$data = array(
			'accepted' => $mms->getAccepted(),
			'declined' => $mms->getDeclined(),
			'queued' => $mms->getQueued()
		);
		SendToAllClientsExcept($clientID, 'updatemmslist', $data);
	}
	else {
		$data = array(
			'message' => 'A database error occured: Unable to accept message'
		);
		SendToClient($clientID, 'servmsg', $data);
	}
}

// This is triggered when a user declines an MMS
function eventSetDeclined($clientID, $ip, $params) {
	$mms = new mmsReaction();
	$msgid = $params[0];
	
	$success = $mms->setDeclined($msgid);
	
	if ($success) {
		$data = array(
			'accepted' => $mms->getAccepted(),
			'declined' => $mms->getDeclined(),
			'queued' => $mms->getQueued()
		);
		SendToAllClientsExcept($clientID, 'updatemmslist', $data);
	}
	else {
		$data = array(
			'message' => 'A database error occured: Unable to decline message'
		);
		SendToClient($clientID, 'servmsg', $data);
	}
}

// This is triggered whenever someone sets their nick, which at the moment is only when someone connects to the server
function eventSetNick($clientID, $ip, $params) {
	global $userlist;
	$newnickname = $params[0];
	
	// Avoid confusion, disallow two users the same nickname, add unique trailing number
	$i = 0;
	foreach ($userlist as $id => $nickname) {
		if ($i > 0 && $newnickname.$i == $nickname) {
			$i++;
		}
		if ($newnickname == $nickname) {
			$i++;
		}
	}
	if ($i > 0) {
		$newnickname = $newnickname.$i;
	}
	$userlist[$clientID] = $newnickname;

	$data = array(
		'clientid' => $clientID,
		'ip' => $ip,
		'newnickname' => $newnickname,
	);
	SendToAllClientsExcept($clientID, 'setnick', $data);
	$data = array(
		'userlist' => $userlist
	);
	SendToAllClients('updateuserlist', $data); // At the moment this means that userlist is updated whenever anyone connects
}
/////////////////////////////////////////
// END OF EVENTS
/////////////////////////////////////////

// Event distributer - receives messages from clients and distributes them as necessary
function wsOnMessage($clientID, $message, $messageLength, $binary) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	if ($messageLength == 0) {
		$Server->wsClose($clientID);
		return;
	}
	
	// Params contain type and data
	$params = json_decode($message, true);

	// Parse command based on type
	switch ($params['type']) {
		case 'chatmsg':
			eventChatMsg($clientID, $ip, $params['data']);
		break;
		
		case 'setnick':
			eventSetNick($clientID, $ip, $params['data']);
		break;
		
		case 'setaccepted':
			eventSetAccepted($clientID, $ip, $params['data']);
		break;
		
		case 'setdeclined':
			eventSetDeclined($clientID, $ip, $params['data']);
		break;
		
		default:
			// :|
		break;
	}
}

// WHEN A CLIENT CONNECTS
// You may expect a message about user connecting sent to all users here, but user connected is sent
// upon nick change instead, because we don't have the nickname when the initial connection occurs
function wsOnOpen($clientID)
{
	global $Server;
	$mms = new mmsReaction();
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( "$ip ($clientID) has connected." );
	
	// Get current list of accepted, declined and queued MMS
	// items from DB, and send items to client
	$data = array(
		'accepted' => $mms->getAccepted(),
		'declined' => $mms->getDeclined(),
		'queued' => $mms->getQueued()
	);
	SendToClient($clientID, 'updatemmslist', $data);
}

// WHEN A CLIENT CLOSES OR LOSES CONNECTION
function wsOnClose($clientID, $status) {
	global $Server, $userlist, $config;
	$mms = new mmsReaction();
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	// This is a bit of a hack to save time by avoiding implementing a WebSocket client in PHP
	// We tell the users to update their MMS lists if the IP disconnecting is the servers IP
	if ($ip == $config['ws_ip_lan_bind']) {
		eventUpdateMms($clientID, $ip);
		return;
	}
	
	if (key_exists($clientID, $userlist)) {
		$Server->log( "$ip ($clientID) has disconnected." );
		//Send a user left notice to everyone in the room except user who left
		$data = array(
			'message' => $userlist[$clientID].' disconnected.'
		);
		SendToAllClientsExcept($clientID, 'servmsg', $data);
		unset($userlist[$clientID]);
		$data = array(
			'userlist' => $userlist
		);
		SendToAllClientsExcept($clientID, 'updateuserlist', $data);
	}
	else {
		error_log('Attempted to remove clientID which was not in userlist - misconfigured server address? ID:'.$clientID, 0);
		return;
	}
}

$userlist = array();
// start the server
$Server = new PHPWebSocket();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
// for other computers to connect, you will probably need to change this to your LAN IP or external IP,
// alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))
$Server->wsStartServer($config['ws_ip_lan_bind'], $config['ws_port']);
$Server->log('Server started.');
?>