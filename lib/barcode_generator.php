<?php
/**
 * Simple Barcode Generator
 * Generates Code 128 barcodes
 */

class BarcodeGenerator {
    private $codeMap = [
        '0' => '11011001100', '1' => '11001101100', '2' => '11001100110',
        '3' => '10010011000', '4' => '10010001100', '5' => '10001001100',
        '6' => '10011001000', '7' => '10011000100', '8' => '10001100100',
        '9' => '11001001000', 'A' => '11001000100', 'B' => '11000100100',
        'C' => '10110011100', 'D' => '10011011100', 'E' => '10011001110',
        'F' => '10111001100', 'G' => '10011101100', 'H' => '10011100110',
        'I' => '11001110010', 'J' => '11001011100', 'K' => '11001001110',
        'L' => '11011100100', 'M' => '11001110100', 'N' => '11101101110',
        'O' => '11101001100', 'P' => '11100101100', 'Q' => '11100100110',
        'R' => '11101100100', 'S' => '11100110100', 'T' => '11100110010',
        'U' => '11011011000', 'V' => '11011000110', 'W' => '11000110110',
        'X' => '10100011000', 'Y' => '10001011000', 'Z' => '10001000110',
        ' ' => '10010110000', '$' => '10010001010', '%' => '10001010010',
        '*' => '10001001010', '+' => '10100100010', '-' => '10100010010',
        '.' => '10100100010', '/' => '10100010010'
    ];
    
    /**
     * Generate Code 128 barcode SVG
     */
    public function generateSVG($text, $width = 200, $height = 50) {
        // Add start character
        $barcode = '11010010000'; // Start character '*'
        
        // Convert text to barcode pattern
        for ($i = 0; $i < strlen($text); $i++) {
            $char = strtoupper($text[$i]);
            if (isset($this->codeMap[$char])) {
                $barcode .= $this->codeMap[$char];
            }
        }
        
        // Add stop character
        $barcode .= '1100011101011'; // Stop character
        
        // Generate SVG
        $svg = '<svg width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . (strlen($barcode) + 20) . ' ' . $height . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="100%" height="100%" fill="white"/>';
        
        $x = 10;
        $barWidth = 1;
        for ($i = 0; $i < strlen($barcode); $i++) {
            $bit = $barcode[$i];
            if ($bit == '1') {
                $svg .= '<rect x="' . $x . '" y="5" width="' . $barWidth . '" height="' . ($height - 10) . '" fill="black"/>';
            }
            $x += $barWidth;
        }
        
        // Add text below barcode
        $svg .= '<text x="' . (strlen($barcode) / 2 + 10) . '" y="' . ($height - 5) . '" font-family="Arial" font-size="8" text-anchor="middle">' . htmlspecialchars($text) . '</text>';
        $svg .= '</svg>';
        
        return $svg;
    }
    
    /**
     * Generate Code 128 barcode as HTML
     */
    public function generateHTML($text, $width = 200, $height = 50) {
        // Add start character
        $barcode = '11010010000'; // Start character '*'
        
        // Convert text to barcode pattern
        for ($i = 0; $i < strlen($text); $i++) {
            $char = strtoupper($text[$i]);
            if (isset($this->codeMap[$char])) {
                $barcode .= $this->codeMap[$char];
            }
        }
        
        // Add stop character
        $barcode .= '1100011101011'; // Stop character
        
        // Generate HTML
        $html = '<div style="display:inline-block; padding:10px; background:white;">';
        $html .= '<div style="font-family:monospace; letter-spacing:2px; font-size:10px; text-align:center; margin-bottom:5px;">' . htmlspecialchars($text) . '</div>';
        $html .= '<div style="display:flex; height:' . ($height - 20) . 'px; align-items:flex-end;">';
        
        $barWidth = max(1, floor($width / strlen($barcode)));
        for ($i = 0; $i < strlen($barcode); $i++) {
            $bit = $barcode[$i];
            if ($bit == '1') {
                $html .= '<div style="width:' . $barWidth . 'px; height:100%; background:black; margin-right:0;"></div>';
            } else {
                $html .= '<div style="width:' . $barWidth . 'px; height:100%; background:white; margin-right:0;"></div>';
            }
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
}
?>