<?php
/**
 * Traffic Service
 * Integrates with external APIs for real-time traffic data
 * Supports Google Maps Distance Matrix API and Mapbox
 */

require_once __DIR__ . '/../config/database.php';

class TrafficService {
    private $conn;
    private $provider;
    private $apiKey;
    private $cacheEnabled;
    private $cacheDuration; // in seconds
    
    public function __construct($provider = 'google', $apiKey = null) {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        $this->provider = $provider;
        $this->apiKey = $apiKey ?? $this->getApiKeyFromEnv();
        $this->cacheEnabled = true;
        $this->cacheDuration = 900; // 15 minutes default
    }
    
    /**
     * Get API key from environment
     */
    private function getApiKeyFromEnv() {
        if ($this->provider === 'google') {
            return getenv('GOOGLE_MAPS_API_KEY') ?: '';
        } elseif ($this->provider === 'mapbox') {
            return getenv('MAPBOX_API_KEY') ?: '';
        }
        return '';
    }
    
    /**
     * Get distance and duration between two points with traffic
     */
    public function getDistanceWithTraffic($originLat, $originLon, $destLat, $destLon, $departureTime = null) {
        // Check cache first
        if ($this->cacheEnabled) {
            $cached = $this->getFromCache($originLat, $originLon, $destLat, $destLon);
            if ($cached) {
                return $cached;
            }
        }
        
        // Fetch from API
        $data = match($this->provider) {
            'google' => $this->getGoogleDistanceMatrix($originLat, $originLon, $destLat, $destLon, $departureTime),
            'mapbox' => $this->getMapboxDirections($originLat, $originLon, $destLat, $destLon),
            default => null
        };
        
        if ($data && $this->cacheEnabled) {
            $this->saveToCache($originLat, $originLon, $destLat, $destLon, $data);
        }
        
        return $data;
    }
    
    /**
     * Get distance matrix for multiple origins and destinations (Google Maps)
     */
    private function getGoogleDistanceMatrix($originLat, $originLon, $destLat, $destLon, $departureTime = null) {
        if (empty($this->apiKey)) {
            error_log("Google Maps API key not configured");
            return $this->getFallbackDistance($originLat, $originLon, $destLat, $destLon);
        }
        
        $origin = "$originLat,$originLon";
        $destination = "$destLat,$destLon";
        
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json";
        $params = [
            'origins' => $origin,
            'destinations' => $destination,
            'departure_time' => $departureTime ?? 'now',
            'traffic_model' => 'best_guess',
            'key' => $this->apiKey
        ];
        
        $url .= '?' . http_build_query($params);
        
        try {
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            
            if ($data['status'] === 'OK' && isset($data['rows'][0]['elements'][0])) {
                $element = $data['rows'][0]['elements'][0];
                
                if ($element['status'] === 'OK') {
                    $distanceKm = $element['distance']['value'] / 1000; // Convert meters to km
                    $durationMinutes = $element['duration']['value'] / 60; // Convert seconds to minutes
                    $trafficDurationMinutes = isset($element['duration_in_traffic']) 
                        ? $element['duration_in_traffic']['value'] / 60 
                        : $durationMinutes;
                    
                    // Determine traffic level
                    $trafficLevel = $this->calculateTrafficLevel($durationMinutes, $trafficDurationMinutes);
                    
                    return [
                        'distance_km' => round($distanceKm, 2),
                        'duration_minutes' => round($durationMinutes, 1),
                        'traffic_duration_minutes' => round($trafficDurationMinutes, 1),
                        'traffic_level' => $trafficLevel,
                        'data_source' => 'google'
                    ];
                }
            }
            
            error_log("Google Distance Matrix API error: " . ($data['error_message'] ?? 'Unknown error'));
        } catch (Exception $e) {
            error_log("Google Distance Matrix API exception: " . $e->getMessage());
        }
        
        return $this->getFallbackDistance($originLat, $originLon, $destLat, $destLon);
    }
    
    /**
     * Get directions from Mapbox
     */
    private function getMapboxDirections($originLat, $originLon, $destLat, $destLon) {
        if (empty($this->apiKey)) {
            error_log("Mapbox API key not configured");
            return $this->getFallbackDistance($originLat, $originLon, $destLat, $destLon);
        }
        
        $url = "https://api.mapbox.com/directions/v5/mapbox/driving-traffic/$originLon,$originLat;$destLon,$destLat";
        $params = [
            'access_token' => $this->apiKey,
            'geometries' => 'geojson',
            'overview' => 'simplified'
        ];
        
        $url .= '?' . http_build_query($params);
        
        try {
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            
            if ($data['code'] === 'Ok' && isset($data['routes'][0])) {
                $route = $data['routes'][0];
                
                $distanceKm = $route['distance'] / 1000; // Convert meters to km
                $durationMinutes = $route['duration'] / 60; // Convert seconds to minutes
                
                return [
                    'distance_km' => round($distanceKm, 2),
                    'duration_minutes' => round($durationMinutes, 1),
                    'traffic_duration_minutes' => round($durationMinutes, 1),
                    'traffic_level' => 'moderate', // Mapbox doesn't provide traffic level directly
                    'data_source' => 'mapbox'
                ];
            }
            
            error_log("Mapbox Directions API error: " . ($data['message'] ?? 'Unknown error'));
        } catch (Exception $e) {
            error_log("Mapbox Directions API exception: " . $e->getMessage());
        }
        
        return $this->getFallbackDistance($originLat, $originLon, $destLat, $destLon);
    }
    
    /**
     * Calculate traffic level based on duration difference
     */
    private function calculateTrafficLevel($normalDuration, $trafficDuration) {
        $ratio = $trafficDuration / $normalDuration;
        
        if ($ratio < 1.1) return 'low';
        if ($ratio < 1.3) return 'moderate';
        if ($ratio < 1.5) return 'heavy';
        return 'severe';
    }
    
    /**
     * Fallback to Haversine distance calculation
     */
    private function getFallbackDistance($lat1, $lon1, $lat2, $lon2) {
        $R = 6371; // Earth's radius in kilometers
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $R * $c;
        
        // Estimate duration (assuming average speed of 50 km/h in city)
        $duration = ($distance / 50) * 60; // Convert to minutes
        
        return [
            'distance_km' => round($distance, 2),
            'duration_minutes' => round($duration, 1),
            'traffic_duration_minutes' => round($duration * 1.2, 1), // Add 20% for traffic
            'traffic_level' => 'moderate',
            'data_source' => 'haversine'
        ];
    }
    
    /**
     * Get from cache
     */
    private function getFromCache($originLat, $originLon, $destLat, $destLon) {
        try {
            $sql = "SELECT * FROM route_traffic_cache 
                    WHERE origin_lat = ? AND origin_lon = ? 
                    AND destination_lat = ? AND destination_lon = ?
                    AND expires_at > NOW()
                    ORDER BY cached_at DESC
                    LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$originLat, $originLon, $destLat, $destLon]);
            $cached = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cached) {
                return [
                    'distance_km' => $cached['distance_km'],
                    'duration_minutes' => $cached['duration_minutes'],
                    'traffic_duration_minutes' => $cached['traffic_duration_minutes'],
                    'traffic_level' => $cached['traffic_level'],
                    'data_source' => $cached['data_source'] . ' (cached)'
                ];
            }
        } catch (PDOException $e) {
            error_log("Cache retrieval error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Save to cache
     */
    private function saveToCache($originLat, $originLon, $destLat, $destLon, $data) {
        try {
            $sql = "INSERT INTO route_traffic_cache (
                origin_lat, origin_lon, destination_lat, destination_lon,
                distance_km, duration_minutes, traffic_duration_minutes,
                traffic_level, data_source, expires_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $originLat,
                $originLon,
                $destLat,
                $destLon,
                $data['distance_km'],
                $data['duration_minutes'],
                $data['traffic_duration_minutes'],
                $data['traffic_level'],
                str_replace(' (cached)', '', $data['data_source']),
                $this->cacheDuration
            ]);
        } catch (PDOException $e) {
            error_log("Cache save error: " . $e->getMessage());
        }
    }
    
    /**
     * Clear expired cache entries
     */
    public function clearExpiredCache() {
        try {
            $sql = "DELETE FROM route_traffic_cache WHERE expires_at < NOW()";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Clear cache error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get traffic-optimized route
     */
    public function getOptimizedRoute($warehouse, $stops, $numVehicles = 1) {
        // This would integrate with the existing route optimization
        // but use traffic data for more accurate routing
        
        $routes = [];
        $stopsPerVehicle = ceil(count($stops) / $numVehicles);
        
        for ($v = 0; $v < $numVehicles; $v++) {
            $vehicleStops = array_slice($stops, $v * $stopsPerVehicle, $stopsPerVehicle);
            
            if (empty($vehicleStops)) continue;
            
            $route = [$warehouse];
            $current = $warehouse;
            $remaining = $vehicleStops;
            $totalDistance = 0;
            $totalDuration = 0;
            
            while (!empty($remaining)) {
                $bestStop = null;
                $bestData = null;
                $bestIndex = null;
                $minDuration = INF;
                
                foreach ($remaining as $index => $stop) {
                    $data = $this->getDistanceWithTraffic(
                        $current['lat'],
                        $current['lon'],
                        $stop['lat'],
                        $stop['lon']
                    );
                    
                    if ($data['traffic_duration_minutes'] < $minDuration) {
                        $minDuration = $data['traffic_duration_minutes'];
                        $bestStop = $stop;
                        $bestData = $data;
                        $bestIndex = $index;
                    }
                }
                
                if ($bestStop) {
                    $bestStop['distance'] = $bestData['distance_km'];
                    $bestStop['duration'] = $bestData['traffic_duration_minutes'];
                    $bestStop['traffic_level'] = $bestData['traffic_level'];
                    
                    $route[] = $bestStop;
                    $totalDistance += $bestData['distance_km'];
                    $totalDuration += $bestData['traffic_duration_minutes'];
                    $current = $bestStop;
                    unset($remaining[$bestIndex]);
                    $remaining = array_values($remaining); // Re-index
                }
            }
            
            // Add return to warehouse
            $returnData = $this->getDistanceWithTraffic(
                $current['lat'],
                $current['lon'],
                $warehouse['lat'],
                $warehouse['lon']
            );
            
            $totalDistance += $returnData['distance_km'];
            $totalDuration += $returnData['traffic_duration_minutes'];
            
            $routes[$v] = [
                'route' => $route,
                'total_km' => round($totalDistance, 2),
                'total_duration_minutes' => round($totalDuration, 1),
                'traffic_considered' => true
            ];
        }
        
        return $routes;
    }
    
    /**
     * Get current traffic conditions for a route
     */
    public function getRouteTrafficConditions($routeId) {
        try {
            require_once __DIR__ . '/Route.php';
            $routeModel = new Route();
            $route = $routeModel->getRouteWithStops($routeId);
            
            if (!$route || empty($route['stops'])) {
                return null;
            }
            
            $conditions = [];
            $totalDelay = 0;
            
            for ($i = 0; $i < count($route['stops']) - 1; $i++) {
                $from = $route['stops'][$i];
                $to = $route['stops'][$i + 1];
                
                $data = $this->getDistanceWithTraffic(
                    $from['latitude'],
                    $from['longitude'],
                    $to['latitude'],
                    $to['longitude']
                );
                
                $delay = $data['traffic_duration_minutes'] - $data['duration_minutes'];
                $totalDelay += $delay;
                
                $conditions[] = [
                    'from' => $from['address'],
                    'to' => $to['address'],
                    'traffic_level' => $data['traffic_level'],
                    'delay_minutes' => round($delay, 1),
                    'distance_km' => $data['distance_km']
                ];
            }
            
            return [
                'segments' => $conditions,
                'total_delay_minutes' => round($totalDelay, 1),
                'overall_traffic_level' => $this->getOverallTrafficLevel($conditions)
            ];
        } catch (Exception $e) {
            error_log("Get route traffic conditions error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Determine overall traffic level from segments
     */
    private function getOverallTrafficLevel($segments) {
        $levels = array_column($segments, 'traffic_level');
        $counts = array_count_values($levels);
        
        if (isset($counts['severe']) && $counts['severe'] > 0) return 'severe';
        if (isset($counts['heavy']) && $counts['heavy'] >= count($segments) / 2) return 'heavy';
        if (isset($counts['moderate'])) return 'moderate';
        return 'low';
    }
    
    /**
     * Set cache duration
     */
    public function setCacheDuration($seconds) {
        $this->cacheDuration = $seconds;
    }
    
    /**
     * Enable/disable caching
     */
    public function setCacheEnabled($enabled) {
        $this->cacheEnabled = $enabled;
    }
}
?>
