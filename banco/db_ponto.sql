CREATE DATABASE IF NOT EXISTS `db_ponto`
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE `db_ponto`;

CREATE TABLE `registros_ponto` (
    `id` INT NOT NULL AUTO_INCREMENT,

    `email` VARCHAR(255) NOT NULL,

    `data` DATE NOT NULL,

    `entrada` TIME DEFAULT NULL,

    `saida_almoco` TIME DEFAULT NULL,

    `retorno_almoco` TIME DEFAULT NULL,

    `saida` TIME DEFAULT NULL,

    `criado_em` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    INDEX `idx_registros_email` (`email`),

    INDEX `idx_registros_data` (`data`),

    INDEX `idx_registros_email_data` (`email`, `data`)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;