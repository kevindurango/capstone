-- Update the crop_seasons table with more detailed descriptions
UPDATE crop_seasons 
SET description = 'November to April - Hot and dry period ideal for drought-resistant crops. Average temperature ranges from 26-33°C with minimal rainfall.'
WHERE season_id = 1;

UPDATE crop_seasons 
SET description = 'May to October - Rainy period with high humidity, suitable for moisture-loving crops. Average rainfall of 200-400mm per month.'
WHERE season_id = 2;

UPDATE crop_seasons 
SET description = 'November to February - Early planting season after the wet season, moderate temperatures ideal for leafy vegetables and cool-season crops.'
WHERE season_id = 3;

UPDATE crop_seasons 
SET description = 'March to June - Main growing season with increasing temperatures, ideal for heat-loving crops. Planting should be completed before peak summer.'
WHERE season_id = 4;

UPDATE crop_seasons 
SET description = 'July to October - Late season planting during and after heavy rains, good for crops that benefit from high soil moisture and moderate temperatures.'
WHERE season_id = 5;

-- Add new column for specific planting recommendations
ALTER TABLE crop_seasons ADD COLUMN planting_recommendations TEXT AFTER description;

-- Add planting recommendations for each season
UPDATE crop_seasons 
SET planting_recommendations = 'Best for: Rice (upland varieties), cassava, sweet potato, mung beans, peanuts, and drought-resistant vegetables.'
WHERE season_id = 1;

UPDATE crop_seasons 
SET planting_recommendations = 'Best for: Leafy vegetables, gourds, root crops, rice (lowland varieties), and tropical fruits.'
WHERE season_id = 2;

UPDATE crop_seasons 
SET planting_recommendations = 'Best for: Cabbage, carrots, radish, onions, garlic, and other cool-weather crops.'
WHERE season_id = 3;

UPDATE crop_seasons 
SET planting_recommendations = 'Best for: Tomatoes, eggplant, peppers, okra, corn, and heat-loving fruits.'
WHERE season_id = 4;

UPDATE crop_seasons 
SET planting_recommendations = 'Best for: Sweet potatoes, taro, squash, beans, and second rice crop.'
WHERE season_id = 5;