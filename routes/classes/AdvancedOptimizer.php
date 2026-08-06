<?php
/**
 * Advanced Route Optimizer
 * Implements multiple optimization algorithms
 */

class AdvancedOptimizer {
    
    /**
     * Savings Algorithm (Clarke-Wright)
     * Better for multiple vehicles than nearest neighbor
     */
    public static function savingsAlgorithm($warehouse, $points, $numVehicles) {
        if (empty($points)) {
            return self::emptyRoutes($warehouse, $numVehicles);
        }
        
        // Calculate savings for all pairs
        $savings = [];
        for ($i = 0; $i < count($points); $i++) {
            for ($j = $i + 1; $j < count($points); $j++) {
                $distIW = self::distance($points[$i], $warehouse);
                $distJW = self::distance($points[$j], $warehouse);
                $distIJ = self::distance($points[$i], $points[$j]);
                
                $saving = $distIW + $distJW - $distIJ;
                
                $savings[] = [
                    'i' => $i,
                    'j' => $j,
                    'saving' => $saving
                ];
            }
        }
        
        // Sort by savings (descending)
        usort($savings, function($a, $b) {
            return $b['saving'] <=> $a['saving'];
        });
        
        // Initialize routes (each point is its own route)
        $routes = [];
        foreach ($points as $idx => $point) {
            $routes[$idx] = [$point];
        }
        
        // Merge routes based on savings
        foreach ($savings as $save) {
            $i = $save['i'];
            $j = $save['j'];
            
            // Find which routes contain i and j
            $routeI = null;
            $routeJ = null;
            
            foreach ($routes as $rIdx => $route) {
                if (in_array($points[$i], $route, true)) $routeI = $rIdx;
                if (in_array($points[$j], $route, true)) $routeJ = $rIdx;
            }
            
            // Merge if they're in different routes and at endpoints
            if ($routeI !== null && $routeJ !== null && $routeI !== $routeJ) {
                $canMerge = false;
                
                // Check if i is at end of its route and j at start of its route
                if ($routes[$routeI][count($routes[$routeI])-1] === $points[$i] &&
                    $routes[$routeJ][0] === $points[$j]) {
                    $routes[$routeI] = array_merge($routes[$routeI], $routes[$routeJ]);
                    unset($routes[$routeJ]);
                    $canMerge = true;
                }
                // Or vice versa
                elseif ($routes[$routeJ][count($routes[$routeJ])-1] === $points[$j] &&
                        $routes[$routeI][0] === $points[$i]) {
                    $routes[$routeJ] = array_merge($routes[$routeJ], $routes[$routeI]);
                    unset($routes[$routeI]);
                    $canMerge = true;
                }
                
                if ($canMerge && count($routes) <= $numVehicles) {
                    break; // Don't merge below vehicle count
                }
            }
        }
        
        // Convert to final format
        $finalRoutes = [];
        $routes = array_values($routes); // Re-index
        
        for ($v = 0; $v < $numVehicles; $v++) {
            if (isset($routes[$v])) {
                $route = [$warehouse];
                $totalDist = 0;
                $prev = $warehouse;
                
                foreach ($routes[$v] as $point) {
                    $dist = self::distance($prev, $point);
                    $point['distance'] = $dist;
                    $route[] = $point;
                    $totalDist += $dist;
                    $prev = $point;
                }
                
                // Return to warehouse
                $returnDist = self::distance($prev, $warehouse);
                $wh = $warehouse;
                $wh['distance'] = $returnDist;
                $route[] = $wh;
                $totalDist += $returnDist;
                
                $finalRoutes[$v] = [
                    'route' => $route,
                    'total_km' => $totalDist
                ];
            } else {
                $finalRoutes[$v] = [
                    'route' => [$warehouse, $warehouse],
                    'total_km' => 0
                ];
            }
        }
        
        return $finalRoutes;
    }
    
    /**
     * Genetic Algorithm for route optimization
     * Good for complex scenarios with many constraints
     */
    public static function geneticAlgorithm($warehouse, $points, $numVehicles, $generations = 100) {
        if (empty($points)) {
            return self::emptyRoutes($warehouse, $numVehicles);
        }
        
        $populationSize = 50;
        $mutationRate = 0.1;
        $eliteSize = 5;
        
        // Initialize population
        $population = [];
        for ($i = 0; $i < $populationSize; $i++) {
            $population[] = self::randomSolution($points, $numVehicles);
        }
        
        // Evolve
        for ($gen = 0; $gen < $generations; $gen++) {
            // Evaluate fitness
            $fitness = [];
            foreach ($population as $idx => $solution) {
                $fitness[$idx] = self::calculateFitness($warehouse, $solution);
            }
            
            // Sort by fitness (lower is better - total distance)
            asort($fitness);
            
            // Select elite
            $elite = [];
            $eliteIndices = array_slice(array_keys($fitness), 0, $eliteSize, true);
            foreach ($eliteIndices as $idx) {
                $elite[] = $population[$idx];
            }
            
            // Create new generation
            $newPopulation = $elite;
            
            while (count($newPopulation) < $populationSize) {
                // Tournament selection
                $parent1 = self::tournamentSelect($population, $fitness);
                $parent2 = self::tournamentSelect($population, $fitness);
                
                // Crossover
                $child = self::crossover($parent1, $parent2);
                
                // Mutation
                if (mt_rand() / mt_getrandmax() < $mutationRate) {
                    $child = self::mutate($child);
                }
                
                $newPopulation[] = $child;
            }
            
            $population = $newPopulation;
        }
        
        // Return best solution
        $fitness = [];
        foreach ($population as $idx => $solution) {
            $fitness[$idx] = self::calculateFitness($warehouse, $solution);
        }
        asort($fitness);
        $bestIdx = array_key_first($fitness);
        
        return self::formatSolution($warehouse, $population[$bestIdx]);
    }
    
    /**
     * Multi-objective optimization
     * Balances distance, time, and cost
     */
    public static function multiObjective($warehouse, $points, $numVehicles, $weights = null) {
        // Default weights: distance 40%, time 30%, cost 30%
        if (!$weights) {
            $weights = ['distance' => 0.4, 'time' => 0.3, 'cost' => 0.3];
        }
        
        // Generate multiple solutions using different algorithms
        $solutions = [];
        
        // Nearest neighbor (fast, good for time)
        $solutions[] = [
            'routes' => self::nearestNeighbor($warehouse, $points, $numVehicles),
            'type' => 'nearest_neighbor'
        ];
        
        // Savings algorithm (good for distance)
        $solutions[] = [
            'routes' => self::savingsAlgorithm($warehouse, $points, $numVehicles),
            'type' => 'savings'
        ];
        
        // Evaluate each solution
        $best = null;
        $bestScore = PHP_FLOAT_MAX;
        
        foreach ($solutions as $solution) {
            $score = self::multiObjectiveScore($solution['routes'], $weights);
            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $solution['routes'];
            }
        }
        
        return $best;
    }
    
    // Helper methods
    
    private static function distance($a, $b) {
        $R = 6371;
        $dLat = deg2rad($b['lat'] - $a['lat']);
        $dLon = deg2rad($b['lon'] - $a['lon']);
        $x = sin($dLat/2)**2 + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dLon/2)**2;
        return $R * 2 * atan2(sqrt($x), sqrt(1 - $x));
    }
    
    private static function emptyRoutes($warehouse, $numVehicles) {
        $routes = [];
        for ($i = 0; $i < $numVehicles; $i++) {
            $routes[$i] = [
                'route' => [$warehouse, $warehouse],
                'total_km' => 0
            ];
        }
        return $routes;
    }
    
    private static function nearestNeighbor($warehouse, $points, $numVehicles) {
        // Simple distribution then nearest neighbor for each
        $pointsPerVehicle = ceil(count($points) / $numVehicles);
        $routes = [];
        
        for ($v = 0; $v < $numVehicles; $v++) {
            $vehiclePoints = array_slice($points, $v * $pointsPerVehicle, $pointsPerVehicle);
            
            if (empty($vehiclePoints)) {
                $routes[$v] = ['route' => [$warehouse, $warehouse], 'total_km' => 0];
                continue;
            }
            
            $route = [$warehouse];
            $current = $warehouse;
            $remaining = $vehiclePoints;
            $totalDist = 0;
            
            while (!empty($remaining)) {
                $nearest = null;
                $nearestDist = PHP_FLOAT_MAX;
                $nearestIdx = null;
                
                foreach ($remaining as $idx => $point) {
                    $dist = self::distance($current, $point);
                    if ($dist < $nearestDist) {
                        $nearestDist = $dist;
                        $nearest = $point;
                        $nearestIdx = $idx;
                    }
                }
                
                $nearest['distance'] = $nearestDist;
                $route[] = $nearest;
                $totalDist += $nearestDist;
                $current = $nearest;
                unset($remaining[$nearestIdx]);
                $remaining = array_values($remaining);
            }
            
            $returnDist = self::distance($current, $warehouse);
            $wh = $warehouse;
            $wh['distance'] = $returnDist;
            $route[] = $wh;
            $totalDist += $returnDist;
            
            $routes[$v] = ['route' => $route, 'total_km' => $totalDist];
        }
        
        return $routes;
    }
    
    private static function randomSolution($points, $numVehicles) {
        shuffle($points);
        $solution = array_chunk($points, ceil(count($points) / $numVehicles));
        return array_pad($solution, $numVehicles, []);
    }
    
    private static function calculateFitness($warehouse, $solution) {
        $totalDist = 0;
        foreach ($solution as $route) {
            if (empty($route)) continue;
            $dist = self::distance($warehouse, $route[0]);
            for ($i = 0; $i < count($route) - 1; $i++) {
                $dist += self::distance($route[$i], $route[$i+1]);
            }
            $dist += self::distance($route[count($route)-1], $warehouse);
            $totalDist += $dist;
        }
        return $totalDist;
    }
    
    private static function tournamentSelect($population, $fitness, $tournamentSize = 3) {
        $selected = [];
        for ($i = 0; $i < $tournamentSize; $i++) {
            $selected[] = array_rand($population);
        }
        
        $best = $selected[0];
        foreach ($selected as $idx) {
            if ($fitness[$idx] < $fitness[$best]) {
                $best = $idx;
            }
        }
        
        return $population[$best];
    }
    
    private static function crossover($parent1, $parent2) {
        // Simple one-point crossover
        $point = mt_rand(0, min(count($parent1), count($parent2)) - 1);
        return array_merge(array_slice($parent1, 0, $point), array_slice($parent2, $point));
    }
    
    private static function mutate($solution) {
        // Swap two random points
        if (count($solution) < 2) return $solution;
        
        $route1 = array_rand($solution);
        $route2 = array_rand($solution);
        
        if (!empty($solution[$route1]) && !empty($solution[$route2])) {
            $idx1 = array_rand($solution[$route1]);
            $idx2 = array_rand($solution[$route2]);
            
            $temp = $solution[$route1][$idx1];
            $solution[$route1][$idx1] = $solution[$route2][$idx2];
            $solution[$route2][$idx2] = $temp;
        }
        
        return $solution;
    }
    
    private static function formatSolution($warehouse, $solution) {
        $routes = [];
        foreach ($solution as $idx => $routePoints) {
            if (empty($routePoints)) {
                $routes[$idx] = ['route' => [$warehouse, $warehouse], 'total_km' => 0];
                continue;
            }
            
            $route = [$warehouse];
            $totalDist = 0;
            $prev = $warehouse;
            
            foreach ($routePoints as $point) {
                $dist = self::distance($prev, $point);
                $point['distance'] = $dist;
                $route[] = $point;
                $totalDist += $dist;
                $prev = $point;
            }
            
            $returnDist = self::distance($prev, $warehouse);
            $wh = $warehouse;
            $wh['distance'] = $returnDist;
            $route[] = $wh;
            $totalDist += $returnDist;
            
            $routes[$idx] = ['route' => $route, 'total_km' => $totalDist];
        }
        
        return $routes;
    }
    
    private static function multiObjectiveScore($routes, $weights) {
        $totalDistance = 0;
        $totalTime = 0;
        $totalCost = 0;
        
        foreach ($routes as $route) {
            $totalDistance += $route['total_km'];
            $totalTime += $route['total_km'] / 30; // Assume 30 km/h avg
            $totalCost += $route['total_km'] * 5; // Assume 5 ETB per km
        }
        
        // Normalize and weight
        $score = ($totalDistance * $weights['distance']) +
                 ($totalTime * $weights['time'] * 10) + // Scale time
                 ($totalCost * $weights['cost'] / 10);  // Scale cost
        
        return $score;
    }
}
