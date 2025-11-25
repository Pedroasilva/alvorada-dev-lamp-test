<?php
/**
 * Simple in-memory cache helper
 * For production, use Redis or Memcached
 */

class SimpleCache {
    private static $cache = [];
    private static $ttl = [];
    
    /**
     * Get value from cache
     * @param string $key
     * @return mixed|null
     */
    public static function get($key) {
        // Check if exists and not expired
        if (isset(self::$cache[$key])) {
            if (!isset(self::$ttl[$key]) || self::$ttl[$key] > time()) {
                return self::$cache[$key];
            }
            // Expired, remove it
            self::delete($key);
        }
        return null;
    }
    
    /**
     * Set value in cache with TTL
     * @param string $key
     * @param mixed $value
     * @param int $ttl Time to live in seconds (default: 5 minutes)
     */
    public static function set($key, $value, $ttl = 300) {
        self::$cache[$key] = $value;
        self::$ttl[$key] = time() + $ttl;
    }
    
    /**
     * Delete value from cache
     * @param string $key
     */
    public static function delete($key) {
        unset(self::$cache[$key]);
        unset(self::$ttl[$key]);
    }
    
    /**
     * Clear all cache
     */
    public static function clear() {
        self::$cache = [];
        self::$ttl = [];
    }
    
    /**
     * Check if key exists and is valid
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        return self::get($key) !== null;
    }
}

/**
 * Cache wrapper for database queries
 */
class QueryCache {
    /**
     * Remember query result
     * @param string $key Cache key
     * @param callable $callback Function that returns data
     * @param int $ttl Time to live in seconds
     * @return mixed
     */
    public static function remember($key, $callback, $ttl = 300) {
        $cached = SimpleCache::get($key);
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $callback();
        SimpleCache::set($key, $result, $ttl);
        return $result;
    }
}
