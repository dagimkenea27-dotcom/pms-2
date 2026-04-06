# Branch Activities - Order Receiving Feature

## Overview
This feature allows you to receive and track customer orders at branch locations. Each order includes customer information, product details, delivery address, and contact information.

## Installation

### Step 1: Database Setup
Run the SQL migration file to create the necessary table:

```sql
-- Execute this in your database management tool (phpMyAdmin, MySQL Workbench, etc.)
source sql/branch_orders.sql;
```

Or manually execute the CREATE TABLE statement from `sql/branch_orders.sql`.

### Step 2: Access the Feature
Once installed, you can access the feature from the sidebar menu:
- **Branch Activities** → **Branch Order Receiving** (to create new orders)
- **Branch Activities** → **View Orders** (to manage existing orders)

## Features

### 1. Order Receiving Form (`products/branch_order_receive.php`)
Capture complete order information including:
- Customer name
- Product name, color, and size
- Quantity
- Special options (if available)
- Delivery address
- Phone number
- Additional notes

**Key Features:**
- Auto-generates unique order numbers (Format: BO-YYYYMMDD####)
- Product search with autocomplete
- Variant selection for products with variants
- Recent orders display
- Form validation

### 2. Orders Management List (`products/branch_orders_list.php`)
View and manage all branch orders with:
- Statistics dashboard (Total, Pending, Processing, Completed)
- Advanced filtering (status, customer, product, date range)
- Pagination support
- Status update capability
- Search functionality

**Order Statuses:**
- **Pending** - New order received, not yet processed
- **Processing** - Order is being prepared/fulfilled
- **Completed** - Order has been delivered/fulfilled
- **Cancelled** - Order was cancelled

## Usage Guide

### Creating a New Order

1. Navigate to **Branch Activities** → **Branch Order Receiving**
2. Fill in the customer information:
   - Customer Name (required)
   - Phone Number (required)
   - Address (optional but recommended)
3. Select or enter product details:
   - Use the product search to find existing products, OR
   - Manually enter the product name
   - Specify color and size if applicable
   - Select variant if the product has variants
4. Enter quantity and any special options
5. Add notes if needed
6. Click **"Receive Order"**

The system will:
- Generate a unique order number
- Save the order with "pending" status
- Display a success message
- Redirect back to the form for the next order

### Managing Orders

1. Navigate to **Branch Activities** → **View Orders**
2. Use filters to find specific orders:
   - Filter by status
   - Search by customer name or order number
   - Search by product name
3. To change order status:
   - Click the **Options** dropdown
   - Select **Change Status**
   - Choose the new status (it will auto-submit)

### Viewing Order Statistics

The dashboard shows:
- **Total Orders** - All orders in the system
- **Pending** - Orders awaiting processing
- **Processing** - Orders currently being fulfilled
- **Completed** - Successfully delivered orders

## Database Schema

### branch_orders Table

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| order_number | VARCHAR(50) | Unique order identifier |
| customer_name | VARCHAR(255) | Customer's full name |
| product_id | INT | Reference to products table |
| variant_id | INT | Reference to product_variants table |
| product_name | VARCHAR(255) | Product name |
| product_color | VARCHAR(50) | Product color |
| product_size | VARCHAR(50) | Product size |
| quantity | INT | Order quantity |
| option_available | VARCHAR(255) | Special options/requests |
| address | TEXT | Delivery address |
| phone_number | VARCHAR(50) | Contact phone |
| status | ENUM | Order status |
| notes | TEXT | Additional notes |
| created_by | INT | User who created the order |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

## API Endpoints (AJAX)

### Get Product Variants
```
GET /products/branch_order_receive.php?ajax=get_variants&product_id={id}
Response: {
    "success": true,
    "variants": [
        {"id": 1, "size": "M", "color": "Red", "sku": "PROD-001-M-R", "quantity": 50},
        ...
    ]
}
```

## Security Features

- CSRF token protection on all form submissions
- Authentication required (auth_check.php)
- Input validation and sanitization
- SQL injection prevention via prepared statements

## Customization

### Adding More Fields
To add additional fields to the order form:

1. Add the column to the `branch_orders` table
2. Update the `BranchOrder` model (create/update methods)
3. Add the field to the form in `branch_order_receive.php`
4. Add language translations

### Modifying Order Number Format
Edit the `generateOrderNumber()` method in `models/BranchOrder.php`:

```php
public function generateOrderNumber() {
    $prefix = 'BO-'; // Change this prefix
    $date = date('Ymd');
    // ... rest of the logic
}
```

## Troubleshooting

### Issue: Table doesn't exist
**Solution:** Run the SQL migration file: `sql/branch_orders.sql`

### Issue: Products not showing in search
**Solution:** Ensure you have products in the database. Check that the product query is correct.

### Issue: Can't update order status
**Solution:** Check that CSRF tokens are being generated and validated properly.

### Issue: Language strings not displaying
**Solution:** Clear any opcode cache and ensure lang/en.php is properly loaded.

## Future Enhancements

Potential features to add:
- Email notifications to customers
- PDF invoice generation
- Barcode scanning for order lookup
- Integration with inventory stock deduction
- Advanced reporting and analytics
- Bulk order import/export
- Order tracking page for customers

## Support

For issues or questions about this feature, please check:
1. Database table exists and has correct structure
2. All files are uploaded correctly
3. PHP error logs for any warnings
4. Browser console for JavaScript errors

---

**Version:** 1.0  
**Last Updated:** April 3, 2026
