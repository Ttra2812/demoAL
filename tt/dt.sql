CREATE DATABASE IF NOT EXISTS `db_generative_ai` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_generative_ai`;

CREATE TABLE IF NOT EXISTS `api_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prompt` TEXT NOT NULL,
    `response` TEXT NOT NULL,
    `model_used` VARCHAR(50) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;