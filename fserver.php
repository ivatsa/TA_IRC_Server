    <?php
    // prevent the server from timing out
    set_time_limit(0);
    
    // include the web sockets server script (the server is started at the far bottom of this file)
    require 'class.PHPWebSocket.php';
    require 'class.User.php';
    require 'class.Filter.php';
    include ('wordlist-regex.php');
    include ('censor.function.php');
    
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
        $Server->log( "Decoded JSON: $result->action");
        
        // check action type
             switch ($result->action)
             {
                 
                 case 'login':
                 
                 // add user to logger user list
                 $newUser = new User($result->username, $clientID, $result->location_id, "");
                 $Server->loggedUsers[$clientID] = $newUser;
                 $Server->loggedUsers[$clientID]->printUser();
                 
                  // add him to broadcast list according to location id
                  //$Server->broadcastHash
                  if (array_key_exists($result->location_id, $Server->broadcastHash)) 
                  {
                    echo "The Location list exists element is in the array";
                    $Server->broadcastHash[$result->location_id][$clientID] = $result->username;
                  }
                  else 
                  {
                      echo "Creating new array";
                       $Server->broadcastHash[$result->location_id] = array($clientID => $result->username);
                  }
                  
                  // build login JSON
                  $numClients = sizeof($Server->broadcastHash[$result->location_id]);
                  $loggedUser = $result->username;
                  $loginMsg = "$loggedUser has joined the room";
                  $loginJSON =  "{ \"action\" : \"message\",
                                              \"message\" : {\"username\" : \"Server\", \"text\": \"$loginMsg\"}, 
                                              \"location_id\" : \"$result->location_id\", 
                                              \"number_of_clients\" : \"$numClients\" }";
                  $numClientsJSON = "{ \"action\" : \"serverupdate\",
                                                        \"message\" : {\"username\" : \"Server\", \"text\": \"\"}, 
                                                        \"location_id\" : \"$result->location_id\", 
                                                        \"number_of_clients\" : \"$numClients\" }";
                  
                  $distributionList = $Server->broadcastHash[$result->location_id];
                  foreach ( $distributionList as $id => $username)
                  {
                        // send joined room update to everyone else
                        if( $id != $clientID){
                            $Server->wsSend($id, $loginJSON);
                        }
                        $Server->wsSend($id, $numClientsJSON);
                   }
                   
                  echo "\n";
                  echo "\n\n-------------------------Sending LOGIN msgs to ------------------------- \n";
                  print_r($distributionList);
                  print_r($Server->broadcastHash);
                  print_r($Server->wsClients);
                  echo "---------------------------------------------------------------------------------\n";
                  break;
             
             
                  case 'message':

                   if (array_key_exists($result->location_id, $Server->broadcastHash)) 
                  {
                      
                      $text = $result->message->text;
                      $censored = censorString($text, Filter::$bannedWords, 'X');
                      print_r($censored);
                      print_r($censored['clean']);
                      $cleanText = $censored['clean'];
                                            
                      $messageJSON =  "{ \"action\" : \"message\",
                                              \"message\" : {\"username\" : \"Server\", \"text\": \"$cleanText\"}, 
                                              \"location_id\" : \"$result->location_id\", 
                                              \"number_of_clients\" : \"\" }";
                      
                      
                      
                        $distributionList = $Server->broadcastHash[$result->location_id];
                        echo "\n \n -------------------------Sending MESSAGES to -------------------------  $result->location_id  \n";
                        print_r($distributionList);
                        print_r($Server->wsClients);
                        echo "---------------------------------------------------------------------------------\n";
                        //The speaker is the only person in the room. Don't let them feel lonely.
                        if ( sizeof($distributionList) == 1 )
                                $Server->wsSend($clientID, "There isn't anyone else in the room, but I'll still listen to you. --Your Trusty Server");
                        else  {
                            //Send the message to everyone but the person who said it
                            foreach ( $distributionList as $id => $username)
                            {
                                if ( $id != $clientID )
                                {
                                    $Server->wsSend($id, $messageJSON);
                                }
                            }
                        }
                    }
                break;
             
                case 'switch':
                 $currentUser = $Server->loggedUsers[$clientID];
                 $currentUser->printUser();
                 $currentUsername = $currentUser->getUsername();
                 $currentUserLocationID = $currentUser->getLocationID();
                 
                 // remove from previous location id
                 unset($Server->broadcastHash[$currentUserLocationID][$clientID]);
                 $distributionList = $Server->broadcastHash[$currentUserLocationID];
                 
                 $numClients = sizeof($Server->broadcastHash[$currentUserLocationID]);  
                 $logoutMsg = "$currentUsername has left the room";
                  $logoutJSON =  "{ \"action\" : \"message\",
                                              \"message\" : {\"username\" : \"Server\", \"text\": \"$logoutMsg\"}, 
                                              \"location_id\" : \"$currentUserLocationID\", 
                                              \"number_of_clients\" : \"$numClients\" }";
                                              
                  foreach ( $distributionList as $id => $username)
                  {
                        $Server->wsSend($id, $logoutJSON);
                  }
                  
                  // add to new location id
                   if (array_key_exists($result->location_id, $Server->broadcastHash)) 
                  {
                    echo "The Location list exists element is in the array";
                    $Server->broadcastHash[$result->location_id][$clientID] = $result->username;
                  }
                  else 
                  {
                      echo "Creating new array";
                       $Server->broadcastHash[$result->location_id] = array($clientID => $result->username);
                  }
                  $Server->loggedUsers[$clientID]->setLocationID($result->location_id);
                  
                  // build login JSON
                  $numClients = sizeof($Server->broadcastHash[$result->location_id]);
                  $loginMsg = "$currentUsername has joined the room";
                  $loginJSON =  "{ \"action\" : \"message\",
                                              \"message\" : {\"username\" : \"Server\", \"text\": \"$loginMsg\"}, 
                                              \"location_id\" : \"$result->location_id\", 
                                              \"number_of_clients\" : \"$numClients\" }";
                  $numClientsJSON = "{ \"action\" : \"serverupdate\",
                                                        \"message\" : {\"username\" : \"Server\", \"text\": \"\"}, 
                                                        \"location_id\" : \"$result->location_id\", 
                                                        \"number_of_clients\" : \"$numClients\" }";
                                                        
                  $distributionList = $Server->broadcastHash[$result->location_id];
                  foreach ( $distributionList as $id => $username)
                  {
                        // send joined room update to everyone else
                        if( $id != $clientID){
                            $Server->wsSend($id, $loginJSON);
                        }
                        $Server->wsSend($id, $numClientsJSON);
                   }
        
                              echo "\n";
                  echo "\n\n-------------------------Room switch for -------------------------$currentUsername \n";
                  print_r($distributionList);
                  print_r($Server->broadcastHash);
                  print_r($Server->wsClients);
                  echo "---------------------------------------------------------------------------------\n";
        
                  break;
                 
            
                case 'logout':
                    wsLogout($clientID, null);
                    break;
            
            }
        
        
    // End of JSON PHP test
    
        
    
    
    
        //if ( sizeof($Server->wsClients) == 1 )
            //$Server->wsSend($clientID, "There isn't anyone else in the room, but I'll still listen to you. --Your Trusty Server");
        //else
            //Send the message to everyone but the person who said it
            //foreach ( $Server->wsClients as $id => $client )
                //if ( $id != $clientID )
                    //$Server->wsSend($id, "Visitor $clientID ($ip) said \"$message\"");
    }
    
    
    function wsLogout($clientID, $status) 
    {
        global $Server;
        echo "====== >> client ID $clientID \n";
        if (array_key_exists($clientID, $Server->loggedUsers))
        {
                $currentUser = $Server->loggedUsers[$clientID];
                $currentUser->printUser();
                $currentUserLocationID = $currentUser->getLocationID();
                $currentUsername = $currentUser->getUsername();
                
                
                unset($Server->broadcastHash[$currentUserLocationID][$clientID]);
                $distributionList = $Server->broadcastHash[$currentUserLocationID];
    
              $numClients = sizeof($Server->broadcastHash[$currentUserLocationID]);
              echo "\n ======> num clients $numClients";
              if($numClients == 0)  {
                  echo "\n unsetting $currentUserLocationID";
                 // $index = array_search($currentUserLocationID, $Server->broadcashHash) ;
                   // unset($Server->broadcashHash[$index]);
              }
              $logoutMsg = "$currentUsername has left the room";
              $logoutJSON =  "{ \"action\" : \"message\",
                                          \"message\" : {\"username\" : \"Server\", \"text\": \"$logoutMsg\"}, 
                                          \"location_id\" : \"$currentUserLocationID\", 
                                          \"number_of_clients\" : \"$numClients\" }";
                foreach ( $distributionList as $id => $username)
                {
                    $Server->wsSend($id, $logoutJSON);
                }
                unset($Server->loggerUsers[$clientID]);
                echo "------------------LOGOUT------------------------ $currentUsername \n";
        }
        print_r($Server->broadcastHash);
        print_r($Server->wsClients);
        echo "-------------------------------------------------------------";
        wsOnClose($clientID, $status);
    }
    
    // when a client connects
    function wsOnOpen($clientID)
    {
        global $Server;
        $ip = long2ip( $Server->wsClients[$clientID][6] );
    
        $Server->log( "$ip ($clientID) has connected." );
    
        //Send a join notice to everyone but the person who joined
        //foreach ( $Server->wsClients as $id => $client )
        //	if ( $id != $clientID )
        //		$Server->wsSend($id, "Visitor $clientID ($ip) has joined the room.");
    }
    
    // when a client closes or lost connection
    function wsOnClose($clientID, $status) {
        global $Server;
        $ip = long2ip( $Server->wsClients[$clientID][6] );
    
        $Server->log( "$ip ($clientID) has disconnected." );
        $Server->wsClients[$clientID][2] = 3;
        
    //Send a user left notice to everyone in the room
        //foreach ( $Server->wsClients as $id => $client )
        //	$Server->wsSend($id, "Visitor $clientID ($ip) has left the room.");
    }
    
    // start the server
    $Server = new PHPWebSocket();
    $Server->bind('message', 'wsOnMessage');
    $Server->bind('open', 'wsOnOpen');
    $Server->bind('close', 'wsLogout');
    // for other computers to connect, you will probably need to change this to your LAN IP or external IP,
    // alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))
    $Server->wsStartServer('192.168.45.143', 9300);
    
    ?>
    