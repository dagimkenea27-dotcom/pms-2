# Stock Management System - Database Schema

## Overview
This directory contains the complete database schema for the Stock Management System. The `complete_schema.sql` file includes all tables, relationships, indexes, and initial data needed to set up the database from scratch.

## Files

### `complete_schema.sql` (Recommended for new installations)
- Complete database schema in a single file
- Includes all tables with proper relationships
- Contains initial sample data
- Includes performance indexes
- Ready for deployment

### `setup.sql` (Legacy)
- Original database setup file
- Contains basic tables only

### `update_schema.sql` (Legacy)
- Database updates for categories, brands, and audit logs
- Adds columns to existing tables

### `update_schema_variants.sql` (Legacy)
- Database updates for product variants functionality
- Adds variant support to existing schema

## Deployment Instructions

### For New Installations (Recommended)
1. Use `complete_schema.sql` to create the entire database
2. This file contains everything needed for a fresh installation

### For Existing Databases
If you're updating an existing database that was created with the older files:
1. Run `setup.sql` (if not already run)
2. Run `update_schema.sql`
3. Run `update_schema_variants.sql`

## Database Structure

The complete schema includes the following tables:

- **users** - User accounts and roles
- **suppliers** - Product suppliers
- **categories** - Product categories
- **brands** - Product brands
- **products** - Main product catalog
- **product_variants** - Product variants (sizes, colors, etc.)
- **stock_movements** - Inventory transactions
- **audit_logs** - System audit trail
- **notifications** - User notifications
- **tax_fee_configs** - Tax and fee configurations
- **price_calculation_history** - Price calculation records
- **routes** - Delivery routes
- **route_stops** - Individual stops in routes
- **route_optimization_settings** - Route optimization parameters
- **attributes** - Custom product attributes
- **attribute_values** - Values for attributes
- **product_attributes** - Junction table for product-attribute relationships

## Sample Data
The schema includes sample data for:
- Admin user (username: admin, password: admin123)
- Sample suppliers
- Sample categories
- Sample brands
- Sample products

## Performance Indexes
The schema includes indexes on commonly queried columns for optimal performance.