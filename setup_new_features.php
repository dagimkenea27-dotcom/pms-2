<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$queries = [
    "CREATE TABLE IF NOT EXISTS stock_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        variant_id INT DEFAULT NULL,
        user_id INT NOT NULL,
        change_type ENUM('in', 'out', 'adjustment', 'correction') NOT NULL,
        quantity_before INT NOT NULL,
        quantity_after INT NOT NULL,
        quantity_change INT NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (product_id),
        INDEX (variant_id),
        INDEX (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    
    "CREATE TABLE IF NOT EXISTS purchase_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        supplier_id INT NOT NULL,
        order_number VARCHAR(50) UNIQUE NOT NULL,
        status ENUM('draft', 'ordered', 'received', 'cancelled') DEFAULT 'draft',
        total_amount DECIMAL(15, 2) DEFAULT 0.00,
        notes TEXT,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (supplier_id),
        INDEX (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS purchase_order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_order_id INT NOT NULL,
        product_id INT NOT NULL,
        variant_id INT DEFAULT NULL,
        quantity_ordered INT NOT NULL,
        quantity_received INT DEFAULT 0,
        unit_cost DECIMAL(15, 2) NOT NULL,
        FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
        INDEX (product_id),
        INDEX (variant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($queries as $query) {
    try {
        $db->exec($query);
        echo "Executed query successfully.\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "Setup completed.\n";
?>
