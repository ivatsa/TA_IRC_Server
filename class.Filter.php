<?php

class Filter {
    
    
    public static $bannedWords = array("fuck","bullshit","jerk", "nude", "bastard", "dumbass");
    
    public static function getBannedWords()
    {
            return $this->bannedWords;
    }

    
}


?>