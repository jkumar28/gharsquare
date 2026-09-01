-- Store a user-specified property type when an Other master type is selected.
ALTER TABLE `property_basic`
    ADD COLUMN `custom_property_type` varchar(100) DEFAULT NULL AFTER `property_type_id`;
