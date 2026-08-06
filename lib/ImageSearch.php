<?php
// lib/ImageSearch.php

class ImageSearch {
    /**
     * Generate a Difference Hash (dHash) for an image.
     * Returns a 64-bit hex string.
     */
    public static function getDHash($filePath, $size = 8) {
        if (!file_exists($filePath)) return false;

        $type = @exif_imagetype($filePath);
        switch ($type) {
            case IMAGETYPE_JPEG: $img = @imagecreatefromjpeg($filePath); break;
            case IMAGETYPE_PNG:  $img = @imagecreatefrompng($filePath); break;
            case IMAGETYPE_GIF:  $img = @imagecreatefromgif($filePath); break;
            case IMAGETYPE_WEBP: $img = @imagecreatefromwebp($filePath); break;
            default: return false;
        }

        if (!$img) return false;

        // Resize to (size + 1) x size (e.g. 9x8)
        $width = $size + 1;
        $height = $size;
        $smallImg = imagecreatetruecolor($width, $height);
        imagecopyresampled($smallImg, $img, 0, 0, 0, 0, $width, $height, imagesx($img), imagesy($img));
        imagedestroy($img);

        $bits = "";
        for ($y = 0; $y < $height; $y++) {
            $prevG = self::getGray(imagecolorat($smallImg, 0, $y));
            for ($x = 1; $x < $width; $x++) {
                $currG = self::getGray(imagecolorat($smallImg, $x, $y));
                $bits .= ($prevG > $currG) ? "1" : "0";
                $prevG = $currG;
            }
        }

        imagedestroy($smallImg);
        
        // Convert 64 bits to 16 hex characters
        $hex = "";
        for ($i = 0; $i < 64; $i += 4) {
            $hex .= dechex(bindec(substr($bits, $i, 4)));
        }
        
        return $hex;
    }

    private static function getGray($rgb) {
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        return intval($r * 0.299 + $g * 0.587 + $b * 0.114);
    }

    /**
     * Calculate Hamming distance between two hex hashes.
     */
    public static function hammingDistance($hash1, $hash2) {
        if (strlen($hash1) !== strlen($hash2)) return 64;
        
        $distance = 0;
        for ($i = 0; $i < strlen($hash1); $i++) {
            $h1 = hexdec($hash1[$i]);
            $h2 = hexdec($hash2[$i]);
            // XOR and count bits
            $xor = $h1 ^ $h2;
            while ($xor > 0) {
                if ($xor & 1) $distance++;
                $xor >>= 1;
            }
        }
        return $distance;
    }
}
