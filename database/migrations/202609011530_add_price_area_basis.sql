-- Persist the area field selected for the price-per-unit calculation.
ALTER TABLE `property_pricing`
    ADD COLUMN `price_area_basis` varchar(30) DEFAULT NULL AFTER `price_per_area_unit`;
