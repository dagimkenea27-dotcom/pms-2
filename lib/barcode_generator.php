<?php
/**
 * Hybrid Barcode Generator
 * Supports: 
 * - UPC-A (12-digit numeric) - Optimized for retail/fast scanning
 * - Code 128 (Alphanumeric) - Fallback for other formats
 */

class BarcodeGenerator {
    // Code 128 Patterns (B-S-B-S-B-S widths) converted to binary for simplicity
    // 0: Space, 1: Bar. Each character is 11 modules wide.
    // We use integer keys 0-106 (Value).
    private $code128Patterns = [
        0 => "212222", 1 => "222122", 2 => "222221", 3 => "121223", 4 => "121321",
        5 => "131221", 6 => "122213", 7 => "122312", 8 => "132212", 9 => "221213",
        10=> "221312", 11=> "231212", 12=> "112232", 13=> "122132", 14=> "122231",
        15=> "113222", 16=> "123122", 17=> "123221", 18=> "223211", 19=> "221132",
        20=> "221231", 21=> "213212", 22=> "223112", 23=> "312131", 24=> "311222",
        25=> "321122", 26=> "321221", 27=> "312212", 28=> "322112", 29=> "322211",
        30=> "212123", 31=> "212321", 32=> "232121", 33=> "111323", 34=> "131123",
        35=> "131321", 36=> "112313", 37=> "132113", 38=> "132311", 39=> "211313",
        40=> "231113", 41=> "231311", 42=> "112133", 43=> "112331", 44=> "132131",
        45=> "113123", 46=> "113321", 47=> "133121", 48=> "313121", 49=> "211331",
        50=> "231131", 51=> "213113", 52=> "213311", 53=> "213131", 54=> "311123",
        55=> "311321", 56=> "331121", 57=> "312113", 58=> "312311", 59=> "332111",
        60=> "314111", 61=> "221411", 62=> "431111", 63=> "111224", 64=> "111422",
        65=> "121124", 66=> "121421", 67=> "141122", 68=> "141221", 69=> "112214",
        70=> "112412", 71=> "122114", 72=> "122411", 73=> "142112", 74=> "142211",
        75=> "241211", 76=> "221114", 77=> "413111", 78=> "241112", 79=> "134111",
        80=> "111242", 81=> "121142", 82=> "121241", 83=> "114212", 84=> "124112",
        85=> "124211", 86=> "411212", 87=> "421112", 88=> "421211", 89=> "212141",
        90=> "214121", 91=> "412121", 92=> "111143", 93=> "111341", 94=> "131141",
        95=> "114113", 96=> "114311", 97=> "411113", 98=> "411311", 99=> "113141",
        100=>"114131", 101=>"311141", 102=>"411131",
        103=>"211412", // Start A
        104=>"211214", // Start B
        105=>"211232", // Start C
        106=>"2331112" // Stop (7 bars/spaces)
    ];

    // UPC-A Binary Patterns (L-code for Left 6 digits, R-code for Right 6 digits)
    // Left (Odd Parity)
    private $upcLPatterns = [
        '0' => '0001101', '1' => '0011001', '2' => '0010011', '3' => '0111101', 
        '4' => '0100011', '5' => '0110001', '6' => '0101111', '7' => '0111011', 
        '8' => '0110111', '9' => '0001011'
    ];
    // Right (Even Parity) - Inverse of L patterns
    private $upcRPatterns = [
        '0' => '1110010', '1' => '1100110', '2' => '1101100', '3' => '1000010', 
        '4' => '1011100', '5' => '1001110', '6' => '1010000', '7' => '1000100', 
        '8' => '1001000', '9' => '1110100'
    ];

    private $upcGuardStartEnd = '101';
    private $upcGuardMiddle = '01010';

    /**
     * Check if text is a candidate for UPC-A (Numeric, 11 or 12 digits)
     */
    private function isUPCACandidate($text) {
        return ctype_digit($text) && (strlen($text) == 11 || strlen($text) == 12);
    }

    /**
     * Calculate UPC-A Check Digit
     */
    private function calculateUPCCheckDigit($code) {
        $code = substr($code, 0, 11); // Ensure only first 11 used
        $sum = 0;
        // Sum odd positions (1st, 3rd, etc.) * 3
        // Sum even positions
        for ($i = 0; $i < 11; $i++) {
            if (($i + 1) % 2 != 0) { // Odd position (1-based index)
                $sum += (int)$code[$i] * 3;
            } else {
                $sum += (int)$code[$i];
            }
        }
        $mod = $sum % 10;
        return ($mod == 0) ? 0 : (10 - $mod);
    }

    /**
     * Generate SVG (Detects format automatically)
     */
    public function generateSVG($text, $width = 200, $height = 50) {
        if ($this->isUPCACandidate($text)) {
            return $this->generateUPCASVG($text, $width, $height);
        } else {
            return $this->generateCode128SVG($text, $width, $height);
        }
    }

    /**
     * Generate HTML (Detects format automatically) - Removed for brevity, use SVG
     */
    public function generateHTML($text, $width = 200, $height = 50) {
        // Fallback to SVG logic wrapped in container? Or just return SVG.
        // For compatibility with existing calls, let's keep it referring to SVG logic internally or deprecate.
        // HTML rendering of barcodes is notoriously flaky.
        return $this->generateSVG($text, $width, $height);
    }

    /**
     * Generate UPC-A SVG
     */
    private function generateUPCASVG($text, $width, $height) {
        // ... (Existing UPC-A Logic is fine) ...
        // Re-implementing strictly to preserve user's previous edits
        
         // Prepare valid 12-digit code
         if (strlen($text) == 11) {
            $text .= $this->calculateUPCCheckDigit($text);
        }

        $binary = $this->upcGuardStartEnd;
        // Left 6
        for ($i = 0; $i < 6; $i++) {
            $binary .= $this->upcLPatterns[$text[$i]];
        }
        $binary .= $this->upcGuardMiddle;
        // Right 6
        for ($i = 6; $i < 12; $i++) {
            $binary .= $this->upcRPatterns[$text[$i]];
        }
        $binary .= $this->upcGuardStartEnd;
        
        $moduleWidth = 2; // scale factor
        $totalModules = 113;
        $viewBoxWidth = $totalModules * $moduleWidth;
        
        $svg = '<svg width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $viewBoxWidth . ' ' . $height . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="100%" height="100%" fill="white"/>'; // Background
        
        $startX = 9 * $moduleWidth; // Start after left quiet zone
        
        // Font settings
        $fontSize = $height * 0.22; 
        $fontStack = 'OCR-B, monospace, sans-serif';
        
        // Draw Bars
        for ($i = 0; $i < strlen($binary); $i++) {
            $isGuard = ($i < 3) || ($i >= 45 && $i < 50) || ($i >= 92);
            
            $guardHeight = $height * 0.95;
            $dataHeight = $height * 0.70; // Shortened data bars
            $finalHeight = $isGuard ? $guardHeight : $dataHeight;
            
            if ($binary[$i] == '1') {
                $svg .= '<rect x="' . ($startX + ($i * $moduleWidth)) . '" y="5" width="' . $moduleWidth . '" height="' . $finalHeight . '" fill="black"/>';
            }
        }
        
        // Draw Text
        $textY = $height * 0.92;
        $svg .= '<text x="' . ($startX - 6) . '" y="' . $textY . '" font-family="'.$fontStack.'" font-size="'.$fontSize.'" text-anchor="middle">' . $text[0] . '</text>';
        
        $leftGroupCenter = $startX + (3 + 21) * $moduleWidth;
        $svg .= '<text x="' . $leftGroupCenter . '" y="' . $textY . '" font-family="'.$fontStack.'" font-size="'.$fontSize.'" text-anchor="middle" letter-spacing="3">' . substr($text, 1, 5) . '</text>';

        $rightGroupCenter = $startX + (50 + 21) * $moduleWidth;
        $svg .= '<text x="' . $rightGroupCenter . '" y="' . $textY . '" font-family="'.$fontStack.'" font-size="'.$fontSize.'" text-anchor="middle" letter-spacing="3">' . substr($text, 6, 5) . '</text>';

        $checkDigitX = $startX + (95 * $moduleWidth) + 6;
        $svg .= '<text x="' . $checkDigitX . '" y="' . $textY . '" font-family="'.$fontStack.'" font-size="'.$fontSize.'" text-anchor="middle">' . $text[11] . '</text>';
        
        $svg .= '</svg>';
        return $svg;
    }

    /**
     * Generate Code 128 SVG (Standard B Set)
     */
    private function generateCode128SVG($text, $width, $height) {
        // Convert text to values (Set B: ASCII - 32)
        $values = [];
        $values[] = 104; // Start Code B
        
        $sum = 104;
        
        for ($i = 0; $i < strlen($text); $i++) {
            $ascii = ord($text[$i]);
            // Map ASCII to Code 128 Value (Set B)
            if ($ascii >= 32 && $ascii <= 127) {
                $val = $ascii - 32;
            } else {
                $val = 0; // Space as fallback
            }
            $values[] = $val;
            $sum += $val * ($i + 1);
        }
        
        // Calculate Checksum
        $checksum = $sum % 103;
        $values[] = $checksum;
        $values[] = 106; // Stop Code
        
        // Generate Bars
        $modules = [];
        $modules[] = 0; $modules[] = 0; $modules[] = 0; $modules[] = 0; $modules[] = 0; // Quiet Zone
        
        foreach ($values as $val) {
            $pattern = (string)($this->code128Patterns[$val]);
            // Convert widths string (e.g. "212222") to bars/spaces
            // Even indices are bars, Odd are spaces? No, starts with Bar.
            // "212222" -> Bar(2), Space(1), Bar(2), Space(2), Bar(2), Space(2)
            
            for ($k = 0; $k < strlen($pattern); $k++) {
                $width_units = (int)$pattern[$k];
                $isBar = ($k % 2 == 0);
                for ($w = 0; $w < $width_units; $w++) {
                    $modules[] = $isBar ? 1 : 0;
                }
            }
        }
        
        $modules[] = 0; $modules[] = 0; $modules[] = 0; $modules[] = 0; $modules[] = 0; // Quiet Zone
        
        // Draw SVG
        $totalModules = count($modules);
        $moduleWidth = 2;
        $svgWidth = $totalModules * $moduleWidth;
        
        $svg = '<svg width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $svgWidth . ' ' . $height . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="100%" height="100%" fill="white"/>';
        
        for ($i = 0; $i < $totalModules; $i++) {
            if ($modules[$i] == 1) {
                $svg .= '<rect x="' . ($i * $moduleWidth) . '" y="5" width="' . $moduleWidth . '" height="' . ($height - 25) . '" fill="black"/>';
            }
        }
        
        $svg .= '<text x="' . ($svgWidth / 2) . '" y="' . ($height - 5) . '" font-family="monospace" font-weight="bold" font-size="' . ($height * 0.25) . '" text-anchor="middle">' . htmlspecialchars($text) . '</text>';
        $svg .= '</svg>';
        
        return $svg;
    }
}
?>