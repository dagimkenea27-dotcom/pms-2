<?php
// services/PriceCalculator.php
require_once "config/database.php";
require_once "models/TaxFeeConfig.php";

class PriceCalculator {
    private const VALUE_TAX_RATE = 0.15;
    private const SHIPMENT_FEE_RATE = 0.10;
    private const PROCESSING_FEE_RATE = 0.05;
    private const ADDITIONAL_FEE = 200.00;
    
    // Supported currencies with their default exchange rates to ETB
    private const SUPPORTED_CURRENCIES = [
        'USD' => 1.0,    // Base currency
        'EUR' => 130.0,  // Approximate EUR to ETB
        'GBP' => 150.0,  // Approximate GBP to ETB
        'JPY' => 0.8,    // Approximate JPY to ETB (per 100 JPY)
        'CAD' => 90.0,   // Approximate CAD to ETB
        'AUD' => 85.0    // Approximate AUD to ETB
    ];

    /**
     * Calculate total cost including taxes and fees
     * 
     * @param float $amount Amount in source currency
     * @param float $exchangeRate Exchange rate (source currency to ETB)
     * @param string $sourceCurrency Source currency code (USD, EUR, etc.)
     * @param int $userId User ID for history tracking
     * @return array breakdown of costs
     */
    public function calculate($amount, $exchangeRate, $sourceCurrency = 'USD', $userId = null) {
        $amount = floatval($amount);
        $exchangeRate = floatval($exchangeRate);
        
        // Convert to ETB
        $etbAmount = $amount * $exchangeRate;
        
        // Get tax/fee configurations from database
        $taxFees = $this->getTaxFeeConfigurations();
        
        // Calculate fees and taxes based on configuration
        $valueTax = 0;
        $shipmentFee = 0;
        $processingFee = 0;
        $additionalFee = 0;
        
        foreach ($taxFees as $config) {
            switch ($config['name']) {
                case 'Value Tax':
                    if ($config['rate_type'] === 'percentage') {
                        $valueTax = $etbAmount * $config['rate_value'];
                    } else {
                        $valueTax = $config['rate_value'];
                    }
                    break;
                case 'Shipment Fee':
                    if ($config['rate_type'] === 'percentage') {
                        $shipmentFee = $etbAmount * $config['rate_value'];
                    } else {
                        $shipmentFee = $config['rate_value'];
                    }
                    break;
                case 'Processing Fee':
                    if ($config['rate_type'] === 'percentage') {
                        $processingFee = $etbAmount * $config['rate_value'];
                    } else {
                        $processingFee = $config['rate_value'];
                    }
                    break;
                case 'Delivery Fee':
                    if ($config['rate_type'] === 'percentage') {
                        $additionalFee = $etbAmount * $config['rate_value'];
                    } else {
                        $additionalFee = $config['rate_value'];
                    }
                    break;
            }
        }
        
        $totalFees = $valueTax + $shipmentFee + $processingFee + $additionalFee;
        $totalCost = $etbAmount + $totalFees;
        
        $result = [
            'sourceAmount' => $amount,
            'sourceCurrency' => $sourceCurrency,
            'etbAmount' => $etbAmount,
            'valueTax' => $valueTax,
            'shipmentFee' => $shipmentFee,
            'processingFee' => $processingFee,
            'additionalFee' => $additionalFee,
            'totalFees' => $totalFees,
            'totalCost' => $totalCost,
            'exchangeRate' => $exchangeRate,
            'taxFeeDetails' => $taxFees
        ];
        
        // Save to history if user ID is provided
        if ($userId) {
            $this->saveCalculationHistory($result, $userId);
        }
        
        return $result;
    }
    
    /**
     * Save calculation to history
     * 
     * @param array $result Calculation result
     * @param int $userId User ID
     */
    private function saveCalculationHistory($result, $userId) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $sql = "INSERT INTO price_calculation_history 
                    (user_id, source_currency, source_amount, exchange_rate, etb_amount, total_cost, calculation_data) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId,
                $result['sourceCurrency'],
                $result['sourceAmount'],
                $result['exchangeRate'],
                $result['etbAmount'],
                $result['totalCost'],
                json_encode($result)
            ]);
        } catch (Exception $e) {
            // Silently fail if history saving fails
        }
    }
    
    /**
     * Get tax/fee configurations from database
     * 
     * @return array Tax/fee configurations
     */
    private function getTaxFeeConfigurations() {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $taxFeeConfig = new TaxFeeConfig($db);
            $stmt = $taxFeeConfig->getAllActive();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Fallback to default values if database is not available
            return [
                ['name' => 'Value Tax', 'rate_type' => 'percentage', 'rate_value' => self::VALUE_TAX_RATE],
                ['name' => 'Shipment Fee', 'rate_type' => 'percentage', 'rate_value' => self::SHIPMENT_FEE_RATE],
                ['name' => 'Processing Fee', 'rate_type' => 'percentage', 'rate_value' => self::PROCESSING_FEE_RATE],
                ['name' => 'Delivery Fee', 'rate_type' => 'fixed', 'rate_value' => self::ADDITIONAL_FEE]
            ];
        }
    }
    
    /**
     * Get supported currencies
     * 
     * @return array List of supported currencies
     */
    public function getSupportedCurrencies() {
        return array_keys(self::SUPPORTED_CURRENCIES);
    }
    
    /**
     * Get default exchange rate for a currency
     * 
     * @param string $currency Currency code
     * @return float Default exchange rate
     */
    public function getDefaultExchangeRate($currency) {
        $currency = strtoupper($currency);
        return isset(self::SUPPORTED_CURRENCIES[$currency]) ? self::SUPPORTED_CURRENCIES[$currency] : self::SUPPORTED_CURRENCIES['USD'];
    }
    
    /**
     * Convert between two currencies using ETB as base
     * 
     * @param float $amount Amount to convert
     * @param string $fromCurrency Source currency
     * @param string $toCurrency Target currency
     * @return float Converted amount
     */
    public function convertCurrency($amount, $fromCurrency, $toCurrency) {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);
        
        // If same currency, no conversion needed
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }
        
        // Get exchange rates to ETB
        $fromRate = $this->getDefaultExchangeRate($fromCurrency);
        $toRate = $this->getDefaultExchangeRate($toCurrency);
        
        // Convert to ETB first, then to target currency
        $etbAmount = $amount * $fromRate;
        $convertedAmount = $etbAmount / $toRate;
        
        return $convertedAmount;
    }
}