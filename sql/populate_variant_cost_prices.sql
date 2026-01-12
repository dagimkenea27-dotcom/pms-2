-- Populate variant cost_price from parent product cost_price
-- This script updates all variants that have NULL cost_price
-- by copying the cost_price from their parent product

USE inventory_system;

-- Show what will be updated (preview)
SELECT 
    pv.id AS variant_id,
    pv.sku AS variant_sku,
    pv.size,
    pv.color,
    pv.cost_price AS current_variant_cost,
    p.cost_price AS parent_product_cost,
    p.name AS product_name
FROM product_variants pv
INNER JOIN products p ON pv.product_id = p.id
WHERE pv.cost_price IS NULL
ORDER BY p.name, pv.size, pv.color;

-- Count how many variants will be updated
SELECT COUNT(*) AS variants_to_update
FROM product_variants pv
INNER JOIN products p ON pv.product_id = p.id
WHERE pv.cost_price IS NULL;

-- Perform the update
UPDATE product_variants pv
INNER JOIN products p ON pv.product_id = p.id
SET pv.cost_price = p.cost_price
WHERE pv.cost_price IS NULL;

-- Show confirmation
SELECT 'Migration completed: Variant cost prices populated from parent products' AS status;

-- Show updated records
SELECT 
    pv.id AS variant_id,
    pv.sku AS variant_sku,
    pv.size,
    pv.color,
    pv.cost_price AS updated_cost_price,
    p.name AS product_name
FROM product_variants pv
INNER JOIN products p ON pv.product_id = p.id
WHERE pv.cost_price IS NOT NULL
ORDER BY p.name, pv.size, pv.color;
