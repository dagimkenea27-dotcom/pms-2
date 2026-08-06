<?php
// lib/RateLimiter.php

class RateLimiter {
    private static $lastCall = 0;
    
    public static function wait($minInterval = 0.5) {
        $now = microtime(true);
        $elapsed = $now - self::$lastCall;
        
        if ($elapsed < $minInterval) {
            usleep(($minInterval - $elapsed) * 1000000);
        }
        
        self::$lastCall = microtime(true);
    }
}
