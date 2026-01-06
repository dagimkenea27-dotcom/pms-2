<?php
/**
 * reports/process_expense.php
 * Handles creation of new business expenses
 */
require_once "../config/auth.php";
require_once "../config/database.php";

Auth::requireLogin();
if (!Auth::isAdmin() && !Auth::isManager()) {
    die("Access denied.");
}

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = intval($_POST['category_id']);
    $amount = floatval($_POST['amount']);
    $date = $_POST['expense_date'];
    $description = htmlspecialchars($_POST['description']);
    $userId = Auth::getCurrentUser()['id'];

    $query = "INSERT INTO business_expenses (category_id, amount, expense_date, description, created_by) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([$categoryId, $amount, $date, $description, $userId])) {
        header("Location: finance.php?success=1");
    } else {
        header("Location: finance.php?error=1");
    }
}
