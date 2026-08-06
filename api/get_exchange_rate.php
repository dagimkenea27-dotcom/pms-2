<?php
// api/get_exchange_rate.php
require_once "../config/auth.php";

Auth::startSession();

header('Content-Type: application/json');

// Restore authentication requirement
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get currency parameter
$currency = isset($_GET['currency']) ? strtoupper($_GET['currency']) : 'USD';

// Default rate fallback
$rate = 120.0;

try {
    // Use exchangerate-api.com to get real-time exchange rates
    // Base currency is USD, so we need to convert from USD to ETB first
    // Then convert from USD to target currency
    $apiUrl = 'https://api.exchangerate-api.com/v4/latest/USD';
    
    // Set a timeout context
    $ctx = stream_context_create(array(
        'http' => array(
            'timeout' => 10
        )
    ));
    
    $response = @file_get_contents($apiUrl, false, $ctx);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        if (isset($data['rates']['ETB']) && isset($data['rates'][$currency])) {
            // Convert from USD to ETB, then adjust for target currency
            $etbRate = $data['rates']['ETB'];  // ETB per USD
            $targetRate = $data['rates'][$currency];  // Target currency per USD
            
            // Calculate ETB per target currency
            $rate = $etbRate / $targetRate;
        } else {
            // If ETB is not in the response, we need to calculate it differently
            // Most APIs use USD as base, so we need to get USD to ETB rate separately
            if (isset($data['rates']['ETB'])) {
                $usdToEtb = $data['rates']['ETB'];
                if ($currency === 'USD') {
                    $rate = $usdToEtb;
                } else if (isset($data['rates'][$currency])) {
                    // Convert USD to target currency, then to ETB
                    $usdToTarget = $data['rates'][$currency];
                    $rate = $usdToEtb / $usdToTarget;
                }
            }
        }
    }
} catch (Exception $e) {
    // Fail silently and return default
}

echo json_encode(['rate' => round($rate, 4), 'currency' => $currency]);
exit;
?>