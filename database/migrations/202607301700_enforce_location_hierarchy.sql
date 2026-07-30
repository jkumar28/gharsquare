-- Enforce complete and internally consistent country/state/city/locality chains.
-- Existing data must be checked for NULL, orphaned, or mismatched relationships
-- before this migration is applied.

ALTER TABLE `states`
    MODIFY `country_id` int(11) NOT NULL,
    MODIFY `name` varchar(100) NOT NULL,
    ADD UNIQUE KEY `uq_states_country_id_id` (`country_id`, `id`);

ALTER TABLE `cities`
    MODIFY `state_id` int(11) NOT NULL,
    MODIFY `name` varchar(100) NOT NULL,
    ADD UNIQUE KEY `uq_cities_state_id_id` (`state_id`, `id`);

ALTER TABLE `localities`
    MODIFY `city_id` int(11) NOT NULL,
    MODIFY `name` varchar(150) NOT NULL,
    ADD UNIQUE KEY `uq_localities_city_id_id` (`city_id`, `id`);

ALTER TABLE `property_location`
    DROP FOREIGN KEY `property_location_ibfk_1`,
    DROP FOREIGN KEY `fk_property_location_country`,
    DROP FOREIGN KEY `fk_property_location_state`,
    DROP FOREIGN KEY `fk_property_location_city`,
    DROP FOREIGN KEY `fk_property_location_locality`,
    MODIFY `draft_id` bigint(20) unsigned NOT NULL,
    MODIFY `country_id` int(11) NOT NULL,
    MODIFY `state_id` int(11) NOT NULL,
    MODIFY `city_id` int(11) NOT NULL,
    MODIFY `locality_id` int(11) NOT NULL,
    ADD CONSTRAINT `fk_property_location_draft_required`
        FOREIGN KEY (`draft_id`) REFERENCES `property_drafts` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_property_location_country_required`
        FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE RESTRICT,
    ADD CONSTRAINT `fk_property_location_state_required`
        FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE RESTRICT,
    ADD CONSTRAINT `fk_property_location_city_required`
        FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE RESTRICT,
    ADD CONSTRAINT `fk_property_location_locality_required`
        FOREIGN KEY (`locality_id`) REFERENCES `localities` (`id`) ON DELETE RESTRICT,
    ADD CONSTRAINT `fk_property_location_country_state`
        FOREIGN KEY (`country_id`, `state_id`)
        REFERENCES `states` (`country_id`, `id`) ON DELETE RESTRICT,
    ADD CONSTRAINT `fk_property_location_state_city`
        FOREIGN KEY (`state_id`, `city_id`)
        REFERENCES `cities` (`state_id`, `id`) ON DELETE RESTRICT,
    ADD CONSTRAINT `fk_property_location_city_locality`
        FOREIGN KEY (`city_id`, `locality_id`)
        REFERENCES `localities` (`city_id`, `id`) ON DELETE RESTRICT;
