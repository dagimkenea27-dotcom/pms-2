<?php
/**
 * Route Optimizer Enhancement Installation Script
 * Run this script once to set up all enhancements
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=================================================\n";
echo "Route Optimizer Enhancement Installation\n";
echo "=================================================\n\n";

// Check if running from command line
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

// Load database configuration
require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "✓ Database connection successful\n\n";
    
    // Step 1: Check if schema file exists
    echo "Step 1: Checking schema file...\n";
    $schemaFile = __DIR__ . '/../sql/schema_route_enhancements.sql';
    
    if (!file_exists($schemaFile)) {
        die("✗ Schema file not found: $schemaFile\n");
    }
    echo "✓ Schema file found\n\n";
    
    // Step 2: Read and execute schema
    echo "Step 2: Installing database schema...\n";
    $schema = file_get_contents($schemaFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        try {
            $conn->exec($statement);
            $successCount++;
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "  Warning: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }
    
    echo "✓ Executed $successCount SQL statements\n";
    if ($errorCount > 0) {
        echo "  ($errorCount warnings - likely tables already exist)\n";
    }
    echo "\n";
    
    // Step 3: Create directories
    echo "Step 3: Creating required directories...\n";
    $directories = [
        __DIR__ . '/../uploads/exports',
        __DIR__ . '/../uploads/signatures',
        __DIR__ . '/../uploads/proof_of_delivery'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "✓ Created: $dir\n";
            } else {
                echo "✗ Failed to create: $dir\n";
            }
        } else {
            echo "✓ Already exists: $dir\n";
        }
    }
    echo "\n";
    
    // Step 4: Check environment configuration
    echo "Step 4: Checking environment configuration...\n";
    $envFile = __DIR__ . '/../.env';
    
    if (!file_exists($envFile)) {
        echo "  Creating .env file...\n";
        $envContent = "# Route Optimizer Configuration\n";
        $envContent .= "GOOGLE_MAPS_API_KEY=\n";
        $envContent .= "MAPBOX_API_KEY=\n";
        file_put_contents($envFile, $envContent);
        echo "✓ Created .env file (please add your API keys)\n";
    } else {
        echo "✓ .env file exists\n";
        
        // Check for required keys
        $envContent = file_get_contents($envFile);
        if (strpos($envContent, 'GOOGLE_MAPS_API_KEY') === false) {
            file_put_contents($envFile, "\nGOOGLE_MAPS_API_KEY=\n", FILE_APPEND);
            echo "  Added GOOGLE_MAPS_API_KEY to .env\n";
        }
        if (strpos($envContent, 'MAPBOX_API_KEY') === false) {
            file_put_contents($envFile, "MAPBOX_API_KEY=\n", FILE_APPEND);
            echo "  Added MAPBOX_API_KEY to .env\n";
        }
    }
    echo "\n";
    
    // Step 5: Insert default settings
    echo "Step 5: Inserting default settings...\n";
    $defaultSettings = [
        ['setting_key' => 'default_fuel_price_per_liter', 'setting_value' => '1.50', 'setting_type' => 'number'],
        ['setting_key' => 'default_driver_hourly_rate', 'setting_value' => '15.00', 'setting_type' => 'number'],
        ['setting_key' => 'electricity_price_per_kwh', 'setting_value' => '0.15', 'setting_type' => 'number'],
        ['setting_key' => 'traffic_api_provider', 'setting_value' => 'google', 'setting_type' => 'string'],
        ['setting_key' => 'enable_traffic_optimization', 'setting_value' => 'true', 'setting_type' => 'boolean'],
        ['setting_key' => 'max_route_duration_hours', 'setting_value' => '8', 'setting_type' => 'number'],
        ['setting_key' => 'default_vehicle_speed_kmh', 'setting_value' => '50', 'setting_type' => 'number'],
        ['setting_key' => 'traffic_cache_duration_seconds', 'setting_value' => '900', 'setting_type' => 'number']
    ];
    
    // Get first user ID for settings
    $stmt = $conn->query("SELECT id FROM users LIMIT 1");
    $userId = $stmt->fetchColumn();
    
    if ($userId) {
        foreach ($defaultSettings as $setting) {
            try {
                $sql = "INSERT INTO route_settings (user_id, setting_key, setting_value, setting_type) 
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $userId,
                    $setting['setting_key'],
                    $setting['setting_value'],
                    $setting['setting_type']
                ]);
                echo "✓ Set: {$setting['setting_key']} = {$setting['setting_value']}\n";
            } catch (PDOException $e) {
                echo "  Warning: Could not set {$setting['setting_key']}\n";
            }
        }
    } else {
        echo "  Warning: No users found, skipping default settings\n";
    }
    echo "\n";
    
    // Step 6: Verify installation
    echo "Step 6: Verifying installation...\n";
    $tables = [
        'route_drivers',
        'route_vehicles',
        'route_performance',
        'route_templates',
        'route_costs',
        'route_traffic_cache'
    ];
    
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✓ Table '$table' exists ($count rows)\n";
        } catch (PDOException $e) {
            echo "✗ Table '$table' not found\n";
        }
    }
    echo "\n";
    
    // Installation complete
    echo "=================================================\n";
    echo "✓ Installation Complete!\n";
    echo "=================================================\n\n";
    
    echo "Next Steps:\n";
    echo "1. Add your API keys to .env file:\n";
    echo "   - GOOGLE_MAPS_API_KEY\n";
    echo "   - MAPBOX_API_KEY\n\n";
    echo "2. Visit the analytics dashboard:\n";
    echo "   http://yourdomain.com/routes/analytics.php\n\n";
    echo "3. Read the documentation:\n";
    echo "   - routes/README_ENHANCEMENTS.md\n";
    echo "   - routes/IMPLEMENTATION_SUMMARY.md\n\n";
    echo "4. Test the API:\n";
    echo "   http://yourdomain.com/api/routes_api.php/routes\n\n";
    
    echo "Happy routing! 🚀\n\n";
    
} catch (PDOException $e) {
    echo "\n✗ Database Error: " . $e->getMessage() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
