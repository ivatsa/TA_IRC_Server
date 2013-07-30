<?php
// prevent the server from timing out
set_time_limit(0);

// include the web sockets server script (the server is started at the far bottom of this file)
require 'class.PHPWebSocket.php';

// when a client sends data to the server
function wsOnMessage($clientID, $message, $messageLength, $binary) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	// check if message length is 0
	if ($messageLength == 0) {
		$Server->wsClose($clientID);
		return;
	}

//  

//
// JSON with PHP test
	$result = json_decode($message);
    
    // check action type
     if( $result->action == 'login') {
         $Server->log( "Decoded JSON: $result->action");
         
          // add him to broadcast list according to location id
          //$Server->broadcastHash
          
          
          if (array_key_exists($result->location_id, $Server->broadcastHash)) {
            echo "The Location list exists element is in the array";
            $Server->broadcastHash[$result->location_id][]= $clientID;
          }
          else {
              echo "Creating new array";
               $Server->broadcastHash[$result->location_id] = array($clientID);
          }
          echo "\n";
          print_r($Server->broadcastHash);
     }
     else if( $result->action == 'message') {
           
          $distributionList = $Server->broadcastHash[$result->location_id];
          $distributionList;
          if ( sizeof($distributionList) == 1 )
		                $Server->wsSend($clientID, "There isn't anyone else in the room, but I'll still listen to you. --Your Trusty Server");
	      else  {
		    //Send the message to everyone but the person who said it
	     	foreach ( $distributionList as $id)
                if ( $id != $clientID )
                    $Server->wsSend($id, "$result->username $clientID ($ip) said \"$message\"");
           
         }
     }
    
    
	
	
// End of JSON PHP test

	


//The speaker is the only person in the room. Don't let them feel lonely.
	//if ( sizeof($Server->wsClients) == 1 )
		//$Server->wsSend($clientID, "There isn't anyone else in the room, but I'll still listen to you. --Your Trusty Server");
	//else
		//Send the message to everyone but the person who said it
		//foreach ( $Server->wsClients as $id => $client )
			//if ( $id != $clientID )
				//$Server->wsSend($id, "Visitor $clientID ($ip) said \"$message\"");
}

// when a client connects
function wsOnOpen($clientID)
{
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( "$ip ($clientID) has connected." );

	//Send a join notice to everyone but the person who joined
	foreach ( $Server->wsClients as $id => $client )
		if ( $id != $clientID )
			$Server->wsSend($id, "Visitor $clientID ($ip) has joined the room.");
}

// when a client closes or lost connection
function wsOnClose($clientID, $status) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( "$ip ($clientID) has disconnected." );

	//Send a user left notice to everyone in the room
	foreach ( $Server->wsClients as $id => $client )
		$Server->wsSend($id, "Visitor $clientID ($ip) has left the room.");
}

// start the server
$Server = new PHPWebSocket();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
// for other computers to connect, you will probably need to change this to your LAN IP or external IP,
// alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))
$Server->wsStartServer('192.168.45.143', 9300);

?>
