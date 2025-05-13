-- Update planted area for records with zero values
-- This script identifies records in barangay_products with planted_area = 0
-- and updates them using values from corresponding entries with non-zero planted_area

-- First, let's create a temporary view to identify matching records
CREATE TEMPORARY TABLE IF NOT EXISTS tmp_area_mapping AS
SELECT 
    bp_zero.id AS zero_id,
    bp_nonzero.planted_area AS correct_planted_area
FROM 
    barangay_products bp_zero
JOIN 
    barangay_products bp_nonzero ON bp_zero.barangay_id = bp_nonzero.barangay_id 
    AND bp_zero.product_id = bp_nonzero.product_id
    AND bp_zero.season_id = bp_nonzero.season_id
    AND bp_nonzero.planted_area > 0
WHERE 
    bp_zero.planted_area = 0;

-- Now update the records with zero planted_area using the temporary table
UPDATE 
    barangay_products bp
JOIN 
    tmp_area_mapping tam ON bp.id = tam.zero_id
SET 
    bp.planted_area = tam.correct_planted_area
WHERE 
    bp.planted_area = 0;

-- If there are any remaining records with planted_area = 0 that don't have matches,
-- we'll update them with calculated values based on production estimates
-- Assuming a standard yield ratio of production:area
UPDATE 
    barangay_products 
SET 
    planted_area = CASE 
        WHEN product_id IN (8, 45, 48, 54, 58) THEN estimated_production * 0.002 -- Grains and large crops (1 hectare yields ~500kg)
        WHEN product_id IN (9, 10, 57) THEN estimated_production * 0.005 -- Medium-sized vegetables (1 hectare yields ~200kg)
        ELSE estimated_production * 0.01 -- Small vegetables and fruits (1 hectare yields ~100kg)
    END
WHERE 
    planted_area = 0;

-- Drop the temporary table
DROP TEMPORARY TABLE IF EXISTS tmp_area_mapping;