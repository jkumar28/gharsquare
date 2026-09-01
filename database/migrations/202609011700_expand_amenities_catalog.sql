ALTER TABLE `amenities_master`
  ADD COLUMN `applicable_categories` varchar(100) NOT NULL DEFAULT 'residential,commercial,land' AFTER `category`,
  ADD COLUMN `sort_order` int(11) NOT NULL DEFAULT 0 AFTER `icon`;

ALTER TABLE `property_profile`
  ADD COLUMN `flooring_type` varchar(40) DEFAULT NULL AFTER `furnishing`;

INSERT INTO `amenities_master` (`name`, `category`, `applicable_categories`, `sort_order`)
SELECT seed.name, seed.category, seed.applies, seed.sort_order
FROM (
  SELECT 'Water Storage' name, 'amenities' category, 'residential,commercial,land' applies, 10 sort_order UNION ALL
  SELECT 'Waste Disposal', 'amenities', 'residential,commercial', 20 UNION ALL
  SELECT 'Access to High Speed Internet', 'amenities', 'residential,commercial', 30 UNION ALL
  SELECT 'ATM', 'amenities', 'residential,commercial', 40 UNION ALL
  SELECT 'Bar / Lounge', 'amenities', 'residential,commercial', 50 UNION ALL
  SELECT 'Conference Room', 'amenities', 'commercial', 60 UNION ALL
  SELECT 'Security / Fire Alarm', 'amenities', 'residential,commercial', 70 UNION ALL
  SELECT 'Club House', 'amenities', 'residential', 80 UNION ALL
  SELECT 'Intercom Facility', 'amenities', 'residential,commercial', 90 UNION ALL
  SELECT 'Lift(s)', 'amenities', 'residential,commercial', 100 UNION ALL
  SELECT 'Private Garden', 'property_features', 'residential', 10 UNION ALL
  SELECT 'Covered Parking', 'property_features', 'residential,commercial', 20 UNION ALL
  SELECT 'Open Parking', 'property_features', 'residential,commercial', 30 UNION ALL
  SELECT 'Centrally Air Conditioned', 'property_features', 'residential,commercial', 40 UNION ALL
  SELECT 'Near Bank', 'property_features', 'residential,commercial,land', 50 UNION ALL
  SELECT 'Power Back-up', 'property_features', 'residential,commercial', 60 UNION ALL
  SELECT 'Reserved Parking', 'property_features', 'residential,commercial', 70 UNION ALL
  SELECT 'Vaastu Compliant', 'property_features', 'residential,commercial,land', 80 UNION ALL
  SELECT 'Maintenance Staff', 'society_building', 'residential,commercial', 10 UNION ALL
  SELECT 'Roof Rights', 'society_building', 'residential,commercial', 20 UNION ALL
  SELECT 'Gated Society', 'society_building', 'residential,commercial,land', 30 UNION ALL
  SELECT 'Shopping Centre', 'society_building', 'residential,commercial', 40 UNION ALL
  SELECT 'Gymnasium', 'society_building', 'residential,commercial', 50 UNION ALL
  SELECT 'Wheelchair Accessibility', 'society_building', 'residential,commercial', 60 UNION ALL
  SELECT 'DG Availability', 'society_building', 'residential,commercial', 70 UNION ALL
  SELECT 'CCTV Surveillance', 'society_building', 'residential,commercial', 80 UNION ALL
  SELECT 'Grade A Building', 'society_building', 'commercial', 90 UNION ALL
  SELECT 'Grocery Shop', 'society_building', 'residential,commercial', 100 UNION ALL
  SELECT 'Visitor Parking', 'society_building', 'residential,commercial', 110 UNION ALL
  SELECT 'Swimming Pool', 'society_building', 'residential', 120 UNION ALL
  SELECT 'Security Guard', 'society_building', 'residential,commercial,land', 130 UNION ALL
  SELECT 'Rain Water Harvesting', 'additional_features', 'residential,commercial,land', 10 UNION ALL
  SELECT 'Bank Attached Property', 'additional_features', 'residential,commercial,land', 20 UNION ALL
  SELECT 'Pet Friendly', 'other_features', 'residential', 10 UNION ALL
  SELECT 'Close to Metro Station', 'location_advantages', 'residential,commercial,land', 10 UNION ALL
  SELECT 'Close to School', 'location_advantages', 'residential,commercial,land', 20 UNION ALL
  SELECT 'Close to Hospital', 'location_advantages', 'residential,commercial,land', 30 UNION ALL
  SELECT 'Close to Market', 'location_advantages', 'residential,commercial,land', 40 UNION ALL
  SELECT 'Close to Railway Station', 'location_advantages', 'residential,commercial,land', 50 UNION ALL
  SELECT 'Close to Airport', 'location_advantages', 'residential,commercial,land', 60 UNION ALL
  SELECT 'Close to Mall', 'location_advantages', 'residential,commercial,land', 70 UNION ALL
  SELECT 'Close to Highway', 'location_advantages', 'residential,commercial,land', 80
) seed
WHERE NOT EXISTS (SELECT 1 FROM `amenities_master` existing WHERE LOWER(existing.name) = LOWER(seed.name));

UPDATE `amenities_master`
SET `category` = COALESCE(NULLIF(`category`, ''), 'amenities'),
    `applicable_categories` = COALESCE(NULLIF(`applicable_categories`, ''), 'residential,commercial,land');
