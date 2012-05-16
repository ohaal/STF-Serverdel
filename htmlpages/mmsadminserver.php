<?php
// prevent the server from timing out
set_time_limit(0);

// include the web sockets server script (the server is started at the far bottom of this file)
require 'class.PHPWebSocket.php';

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

// when a client sends data to the server
function wsOnMessage($clientID, $message, $messageLength, $binary) {
	global $Server;
	global $userlist;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	if ($messageLength == 0) {
		$Server->wsClose($clientID);
		return;
	}
	
	// TODO: Add handling of different events
	// addmms
	// chatmsg
	// accept
	// decline
	
	// Params contain type and data
	$params = json_decode($message, true);

	// Parse command based on type
	switch ($params['type']) {
		case 'chatmsg':
//The speaker is the only person in the room. Don't let them feel lonely.
//		if ( sizeof($Server->wsClients) == 1 ) {
//			$Server->wsSend($clientID, 'You are the only one here.');
//		}
			$message = $params['data'][0];
			$data = array(
				'clientid' => $clientID,
				'ip' => $ip,
				'nickname' => $userlist[$clientID],
				'message' => $message
			);
			SendToAllClients('chatmsg', $data);
		break;
			
		case 'setnick':
			// If clientID already has a nick, we pass the old nickname on aswell
			$oldnickname = array_key_exists($clientID, $userlist) ? $userlist[$clientID] : '';
			$newnickname = $params['data'][0]; 
			
			// Avoid confusion, disallow two users the same nickname, add trailing number
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
				'oldnickname' => $oldnickname
			);
			SendToAllClientsExcept($clientID, 'setnick', $data);
			$data = array(
				'userlist' => $userlist
			);
			SendToAllClients('updateuserlist', $data); // At the moment this means that userlist is updated whenver anyone connects
		break;
			
		default:
			// :|
		break;
	}
}

// when a client connects
function wsOnOpen($clientID)
{
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( "$ip ($clientID) has connected." );
	// You may have expected a message about user connecting sent to all users here, but user connected
	// is sent upon nick change instead, because we don't have the nickname when a user connects
}

// when a client closes or lost connection
function wsOnClose($clientID, $status) {
	global $Server;
	global $userlist;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( "$ip ($clientID) has disconnected." );

	//Send a user left notice to everyone in the room
	$data = array(
		'message' => $userlist[$clientID].' disconnected.'
	);
	unset($userlist[$clientID]);
	SendToAllClients('servmsg', $data);
	$data = array(
		'userlist' => $userlist
	);
	SendToAllClients('updateuserlist', $data);
}

$userlist = array();
// start the server
$Server = new PHPWebSocket();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
// for other computers to connect, you will probably need to change this to your LAN IP or external IP,
// alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))
$Server->wsStartServer('192.168.40.190', 1337);

?>