<?php
class User {
    
    var $username;
    var $clientID;
    var $locationID;
    var $messageBuffer;
    
    public function __construct($username, $clientID, $locationID, $messageBuffer) {
        echo "constructing";
        $this->username = $username;
        $this->clientID = $clientID;
        $this->locationID = $locationID;
        $this->messageBuffer = $messageBuffer;
    }
    
    public function getUsername() {
        return $this->username;
    }
    public function setUsername($x) {
        $this->username = $x;
    }
    public function getClientID() {
        return $this->clientID;
    }
    public function setClientID($x) {
        $this->clientID = $x;
    }
    public function getLocationID() {
        return $this->locationID;
    }
    public function setLocationID($x) {
        echo "\n setting new location id \n";
        $this->locationID = $x;
    }
    public function getMessageBuffer() {
        return $this->messageBuffer;
    }
    public function setMessageBuffer($x) {
        $this->messageBuffer = $x;
    }
    public function printUser() {
        echo "\n USER : $this->username  -- $this->clientID -- $this->locationID -- $this->messageBuffer \n";
    }
    
}
?>
