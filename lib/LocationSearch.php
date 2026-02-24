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

        // --- NEW: Check local delivery_locations table first ---
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT id, name, latitude, longitude, description FROM delivery_locations WHERE name = ? OR name LIKE ? OR description LIKE ? LIMIT 1");
            $searchTerm = "%$query%";
            $stmt->execute([$query, $searchTerm, $searchTerm]);
            $local = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($local && !empty($local['latitude']) && !empty($local['longitude'])) {
                $result = [
                    'lat' => floatval($local['latitude']),
                    'lon' => floatval($local['longitude']),
                    'display_name' => $local['name'] . ($local['description'] ? " - " . $local['description'] : ""),
                    'address' => ['suburb' => $local['name']]
                ];
                
                // Cache the local result
                $this->cache[$cacheKey] = [
                    'data' => $result,
                    'timestamp' => time()
                ];
                $this->saveCache();
                
                return $result;
            }
        } catch (Exception $e) {
            error_log("Local geocode check failed: " . $e->getMessage());
        }
        // --- END LOCAL CHECK ---
        
        RateLimiter::wait(1.1); // Increased for stability and compliance
        
        $base = 'https://nominatim.openstreetmap.org/search';
        
        // Improve query for better local results
        $finalQuery = $query;
        // If it's a neighborhood name, adding the city context helps
        if (strpos(strtolower($query), 'addis') === false) {
            $finalQuery .= ", Addis Ababa";
        }

        $params = http_build_query([
            'q' => $finalQuery,
            'format' => 'json',
            'limit' => 5,
            'countrycodes' => $countrycode,
            'addressdetails' => 1,
            'accept-language' => 'en'
        ]);
        $url = $base . '?' . $params;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'RedSeaStockManagement/1.0 (dev-admin@stockmanage.et)',
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json']
        ]);
        
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200 || empty($resp)) {
            error_log("Geocoding failed for query: $finalQuery, HTTP Code: $httpCode, Curl Error: $curlError");
            return null;
        }
        
        $results = json_decode($resp, true);
        if (!$results || empty($results)) {
            // Try one more time without the parentheses if present
            if (preg_match('/\(.*?\)/', $query)) {
                $strippedQuery = preg_replace('/\s*\(.*?\)\s*/', ' ', $query);
                return $this->geocode(trim($strippedQuery), $countrycode, $useCache);
            }
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

        // --- NEW: Save back to delivery_locations if we have a match ---
        if (isset($local['id']) && (empty($local['latitude']) || empty($local['longitude']))) {
            try {
                $update = $db->prepare("UPDATE delivery_locations SET latitude = ?, longitude = ? WHERE id = ?");
                $update->execute([$result['lat'], $result['lon'], $local['id']]);
            } catch (Exception $e) {
                error_log("Failed to update local coordinates for {$local['name']}: " . $e->getMessage());
            }
        }
        // --- END SAVE ---

        return $result;
    }
    
    public function search($query, $countrycode = 'et', $limit = 10) {
        if (strlen(trim($query)) < 2) {
            return [];
        }

        $formatted = [];
        
        // --- NEW: Search local delivery_locations table ---
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT name, latitude, longitude, description FROM delivery_locations WHERE name LIKE ? OR description LIKE ? LIMIT 5");
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm]);
            $locals = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($locals as $local) {
                $formatted[] = [
                    'lat' => !empty($local['latitude']) ? floatval($local['latitude']) : null,
                    'lon' => !empty($local['longitude']) ? floatval($local['longitude']) : null,
                    'display_name' => "[LOCAL] " . $local['name'] . ($local['description'] ? " - " . $local['description'] : ""),
                    'type' => 'local_delivery_point',
                    'importance' => 1.0, // High priority for exact local matches
                    'address' => ['suburb' => $local['name']]
                ];
            }
        } catch (Exception $e) {
            error_log("Local search check failed: " . $e->getMessage());
        }
        // --- END LOCAL SEARCH ---
        
        RateLimiter::wait(0.5); 
        
        $base = 'https://nominatim.openstreetmap.org/search';
        $params = http_build_query([
            'q' => $query,
            'format' => 'json',
            'limit' => $limit,
            'countrycodes' => $countrycode,
            'addressdetails' => 1,
            'accept-language' => 'en',
            'dedupe' => 1,
            'email' => 'dev-admin@stockmanage.et'
        ]);
        $url = $base . '?' . $params;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'RedSeaStockManagement/1.0 (dev-admin@stockmanage.et)',
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $resp = curl_exec($ch);
        curl_close($ch);
        
        $results = json_decode($resp, true);
        if ($results) {
            // Format results with more details
            foreach ($results as $item) {
                $formatted[] = [
                    'lat' => floatval($item['lat']),
                    'lon' => floatval($item['lon']),
                    'display_name' => $item['display_name'],
                    'type' => $item['type'] ?? 'unknown',
                    'importance' => floatval($item['importance'] ?? 0),
                    'address' => $item['address'] ?? []
                ];
            }
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
        
        RateLimiter::wait(1.1);
        
        $base = 'https://nominatim.openstreetmap.org/reverse';
        $params = http_build_query([
            'lat' => $lat,
            'lon' => $lon,
            'format' => 'json',
            'zoom' => 18,
            'addressdetails' => 1,
            'email' => 'dev-admin@stockmanage.et'
        ]);
        $url = $base . '?' . $params;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'RedSeaStockManagement/1.0 (dev-admin@stockmanage.et)',
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true
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
