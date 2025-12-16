<?php
// lib/LocationSearch.php
require_once __DIR__ . '/RateLimiter.php';

class LocationSearch {
    private $cache = [];
    private $cacheFile;
    
    public function __construct() {
        $this->cacheFile = __DIR__ . '/../cache/locations.json';
        $this->loadCache();
    }
    
    private function loadCache() {
        if (file_exists($this->cacheFile)) {
            $data = file_get_contents($this->cacheFile);
            $this->cache = json_decode($data, true) ?? [];
        }
    }
    
    private function saveCache() {
        // Keep only last 1000 cached items to prevent file from growing too large
        if (count($this->cache) > 1000) {
            $this->cache = array_slice($this->cache, -1000, 1000, true);
        }
        
        // Ensure cache directory exists
        $dir = dirname($this->cacheFile);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        
        file_put_contents($this->cacheFile, json_encode($this->cache));
    }
    
    public function geocode($query, $countrycode = 'et', $useCache = true) {
        $cacheKey = md5(strtolower(trim($query)) . '|' . $countrycode);
        
        // Check cache first
        if ($useCache && isset($this->cache[$cacheKey])) {
            $cached = $this->cache[$cacheKey];
            // Cache valid for 30 days
            if (time() - $cached['timestamp'] < 2592000) {
                return $cached['data'];
            }
        }
        
        RateLimiter::wait();
        
        $base = 'https://nominatim.openstreetmap.org/search';
        $params = http_build_query([
            'q' => $query,
            'format' => 'json',
            'limit' => 5, // Get more results for better accuracy
            'countrycodes' => $countrycode,
            'addressdetails' => 1,
            'accept-language' => 'en'
        ]);
        $url = $base . '?' . $params;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'DeliveryRoutePlanner/2.0 (contact@example.com)',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json']
        ]);
        
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || empty($resp)) {
            error_log("Geocoding failed for query: $query, HTTP Code: $httpCode");
            return null;
        }
        
        $results = json_decode($resp, true);
        if (!$results || empty($results)) {
            return null;
        }
        
        // Return first result and cache it
        $result = [
            'lat' => floatval($results[0]['lat']),
            'lon' => floatval($results[0]['lon']),
            'display_name' => $results[0]['display_name'],
            'address' => $results[0]['address'] ?? []
        ];
        
        // Cache the result
        $this->cache[$cacheKey] = [
            'data' => $result,
            'timestamp' => time()
        ];
        $this->saveCache();
        
        return $result;
    }
    
    public function search($query, $countrycode = 'et', $limit = 10) {
        if (strlen(trim($query)) < 2) {
            return [];
        }
        
        RateLimiter::wait(0.2); // Faster search rate limit
        
        $base = 'https://nominatim.openstreetmap.org/search';
        $params = http_build_query([
            'q' => $query,
            'format' => 'json',
            'limit' => $limit,
            'countrycodes' => $countrycode,
            'addressdetails' => 1,
            'accept-language' => 'en',
            'dedupe' => 1
        ]);
        $url = $base . '?' . $params;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'DeliveryRoutePlanner/2.0 Search',
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $resp = curl_exec($ch);
        curl_close($ch);
        
        $results = json_decode($resp, true);
        if (!$results) {
            return [];
        }
        
        // Format results with more details
        $formatted = [];
        foreach ($results as $item) {
            $formatted[] = [
                'lat' => floatval($item['lat']),
                'lon' => floatval($item['lon']),
                'display_name' => $item['display_name'],
                'type' => $item['type'] ?? 'unknown',
                'importance' => $item['importance'] ?? 0,
                'address' => $item['address'] ?? []
            ];
        }
        
        // Sort by importance
        usort($formatted, function($a, $b) {
            return $b['importance'] <=> $a['importance'];
        });
        
        return $formatted;
    }
    
    public function reverseGeocode($lat, $lon) {
        $cacheKey = md5("reverse|{$lat}|{$lon}");
        
        if (isset($this->cache[$cacheKey])) {
            $cached = $this->cache[$cacheKey];
            if (time() - $cached['timestamp'] < 2592000) {
                return $cached['data'];
            }
        }
        
        RateLimiter::wait();
        
        $base = 'https://nominatim.openstreetmap.org/reverse';
        $params = http_build_query([
            'lat' => $lat,
            'lon' => $lon,
            'format' => 'json',
            'zoom' => 18,
            'addressdetails' => 1
        ]);
        $url = $base . '?' . $params;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'DeliveryRoutePlanner/2.0 Reverse',
            CURLOPT_TIMEOUT => 8
        ]);
        
        $resp = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($resp, true);
        if (!$result) {
            return null;
        }
        
        $formatted = [
            'lat' => $lat,
            'lon' => $lon,
            'display_name' => $result['display_name'] ?? "Location at $lat, $lon",
            'address' => $result['address'] ?? []
        ];
        
        $this->cache[$cacheKey] = [
            'data' => $formatted,
            'timestamp' => time()
        ];
        $this->saveCache();
        
        return $formatted;
    }
}
