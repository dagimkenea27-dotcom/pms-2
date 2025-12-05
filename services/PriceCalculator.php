<?php
// services/PriceCalculator.php

class PriceCalculator {
    private const VALUE_TAX_RATE = 0.15;
    private const SHIPMENT_FEE_RATE = 0.10;
    private const PROCESSING_FEE_RATE = 0.05;
    private const ADDITIONAL_FEE = 50.00;

    /**
     * Calculate total cost including taxes and fees
     * 
     * @param float $usdAmount Amount in USD
     * @param float $exchangeRate Exchange rate (USD to ETB)
     * @return array breakdown of costs
     */
    public function calculate($usdAmount, $exchangeRate) {
        $usdAmount = floatval($usdAmount);
        $exchangeRate = floatval($exchangeRate);
        
        // Calculate base ETB amount
        $etbAmount = $usdAmount * $exchangeRate;
        
        // Calculate fees and taxes
        $valueTax = $etbAmount * self::VALUE_TAX_RATE;
        $shipmentFee = $etbAmount * self::SHIPMENT_FEE_RATE;
        $processingFee = $etbAmount * self::PROCESSING_FEE_RATE;
        
        $totalFees = $valueTax + $shipmentFee + $processingFee + self::ADDITIONAL_FEE;
        $totalCost = $etbAmount + $totalFees;
        
        return [
            'etbAmount' => $etbAmount,
            'valueTax' => $valueTax,
            'shipmentFee' => $shipmentFee,
            'processingFee' => $processingFee,
            'additionalFee' => self::ADDITIONAL_FEE,
            'totalFees' => $totalFees,
            'totalCost' => $totalCost
        ];
    }
}
