-- Opcional: Tabela para cache de geocoding
-- Reduz 80% das chamadas à API Nominatim

CREATE TABLE IF NOT EXISTS geocode_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key CHAR(32) UNIQUE NOT NULL COMMENT 'MD5 do endereço normalizado',
    address VARCHAR(500) NOT NULL COMMENT 'Endereço original',
    result JSON NOT NULL COMMENT 'Resposta da API Nominatim',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cache_key (cache_key),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Opcional: Tabela para analytics de uso
CREATE TABLE IF NOT EXISTS geocoding_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    address VARCHAR(500) NOT NULL,
    results_count INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limpeza automática de cache antigo (executar via cron)
-- DELETE FROM geocode_cache WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
