-- Performance Improvements for Property Research System
-- Adds composite indexes and optimizes queries

-- 1. Composite index for name and address searches
-- Useful for searching properties by partial name or address
CREATE INDEX IF NOT EXISTS idx_name_address ON properties (name(50), address(100));

-- 2. Composite index for notes lookup by property and date
-- Optimizes queries that fetch notes for a property ordered by date
CREATE INDEX IF NOT EXISTS idx_property_created ON notes (property_id, created_at DESC);

-- 3. Add covering index for recent properties query
-- Includes all columns needed for recent_properties.php
CREATE INDEX IF NOT EXISTS idx_recent_props ON properties (created_at DESC, id, name(100), address(150));

-- 4. Analyze tables to update statistics
ANALYZE TABLE properties;
ANALYZE TABLE notes;
ANALYZE TABLE geocode_cache;
ANALYZE TABLE geocoding_logs;
