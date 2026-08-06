# Quick Setup Guide - Branch Activities Feature

## 🚀 Installation Steps (5 minutes)

### Step 1: Database Setup (Required)

**Option A - Using phpMyAdmin:**
1. Open phpMyAdmin in your browser
2. Select your `inventory_system` database
3. Click on the "SQL" tab
4. Copy and paste the contents of `sql/setup_branch_orders.sql`
5. Click "Go" to execute

**Option B - Using MySQL Command Line:**
```bash
mysql -u your_username -p inventory_system < sql/setup_branch_orders.sql
```

**Option C - Using MySQL Workbench:**
1. Open MySQL Workbench and connect to your database
2. Open `File` → `Open SQL Script`
3. Select `sql/setup_branch_orders.sql`
4. Execute the script (Lightning bolt icon)

### Step 2: Verify Installation ✅

The feature is already integrated! All files have been added:
- ✅ Model: `models/BranchOrder.php`
- ✅ Order Form: `products/branch_order_receive.php`
- ✅ Orders List: `products/branch_orders_list.php`
- ✅ Language strings: Added to `lang/en.php`
- ✅ Navigation: Added to sidebar menu

### Step 3: Test the Feature 🧪

1. **Login** to your stock management system
2. Look for **"Branch Activities"** in the sidebar menu
3. Click **"Branch Order Receiving"**
4. You should see the order form ready to use!

## 📋 What Was Added?

### Files Created:
```
/models/BranchOrder.php              - Database model
/products/branch_order_receive.php   - Order entry form
/products/branch_orders_list.php     - Orders management
/sql/branch_orders.sql               - Database schema
/sql/setup_branch_orders.sql         - Quick setup script
/BRANCH_ACTIVITIES_FEATURE.md        - Full documentation
```

### Files Modified:
```
/lang/en.php                         - Added language strings
/includes/header.php                 - Added menu items
```

## 🎯 How to Use

### Creating Your First Order:

1. Go to **Branch Activities** → **Branch Order Receiving**
2. Fill in the form:
   - Customer Name: "John Doe"
   - Phone: "+1234567890"
   - Product: Search or enter manually
   - Color: "Red"
   - Size: "M"
   - Quantity: 2
3. Click **"Receive Order"**
4. Success! Your order number will be generated (e.g., BO-202604030001)

### Managing Orders:

1. Go to **Branch Activities** → **View Orders**
2. See all orders with statistics
3. Filter by status, customer, or product
4. Change order status using the dropdown menu

## 🔧 Troubleshooting

### Error: "Table 'branch_orders' doesn't exist"
**Fix:** You didn't run the SQL script. Go back to Step 1.

### Error: "Branch Activities menu not showing"
**Fix:** 
- Clear your browser cache (Ctrl + F5)
- Make sure you're logged in
- Check that header.php was updated correctly

### Error: "Language strings not displaying"
**Fix:** 
- Restart your web server
- Clear PHP opcode cache if using one

### Error: "CSRF token invalid"
**Fix:** This is normal security. Just refresh the page and try again.

## 📊 Database Structure

The `branch_orders` table includes:
- Unique order numbers (auto-generated)
- Customer information
- Product details (with variant support)
- Delivery address
- Order status tracking
- Timestamps

## 🎨 Features Included

✅ Order receiving form with validation  
✅ Auto-generated order numbers  
✅ Product search with autocomplete  
✅ Variant selection support  
✅ Orders list with filters  
✅ Status management  
✅ Statistics dashboard  
✅ Responsive design  
✅ Security (CSRF protection)  
✅ Audit trail  

## 📝 Next Steps

After setup:
1. Train your staff on how to use the order receiving form
2. Customize any fields if needed (see BRANCH_ACTIVITIES_FEATURE.md)
3. Set up order status workflows for your business
4. Consider adding email notifications (future enhancement)

## 💡 Tips

- Order numbers are generated daily (BO-YYYYMMDD####)
- Required fields are marked with asterisk (*)
- You can leave optional fields empty
- Use the notes field for special instructions
- Recent orders show on the right side of the form

## 🆘 Need Help?

If you encounter issues:
1. Check PHP error logs
2. Check browser console for JavaScript errors
3. Verify database connection
4. Review the full documentation in BRANCH_ACTIVITIES_FEATURE.md

---

**Installation Time:** ~5 minutes  
**Difficulty:** Easy  
**Version:** 1.0  
**Date:** April 3, 2026
