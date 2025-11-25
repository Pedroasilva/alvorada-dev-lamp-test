-- Performance Improvements for Property Research System
-- Adds composite indexes and optimizes queries

-- 1. Composite index for name and address searches
-- Useful for searching properties by partial name or address
ALTER TABLE properties 
ADD INDEX idx_name_address (name(50), address(100));

-- 2. Composite index for notes lookup by property and date
-- Optimizes queries that fetch notes for a property ordered by date
ALTER TABLE notes 
ADD INDEX idx_property_created (property_id, created_at DESC);

-- 3. Index for geocode cache lookup
-- Already exists but verify it's optimal
-- geocode_cache already has idx_cache_key

-- 4. Add covering index for recent properties query
-- Includes all columns needed for recent_properties.php
ALTER TABLE properties
ADD INDEX idx_recent_props (created_at DESC, id, name(100), address(150));

-- 5. Analyze tables to update statistics
ANALYZE TABLE properties;
ANALYZE TABLE notes;
ANALYZE TABLE geocode_cache;
ANALYZE TABLE geocoding_logs;

-- 6. Optional: If geocoding_logs grows very large, partition by year
-- Uncomment and adjust years as needed
/*
ALTER TABLE geocoding_logs 
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
*/
