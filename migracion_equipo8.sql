-- =======================================================
-- Migración para quienes YA importaron kiosco_online.sql (versión de agosto)
-- Agrega a `ordenes` la modalidad de precio y la razón social para Factura A.
-- No borra productos ni órdenes. Ejecutar UNA sola vez desde phpMyAdmin.
-- =======================================================
USE `kiosco_online`;

ALTER TABLE `ordenes`
  ADD COLUMN `modalidad` ENUM('minorista', 'mayorista') NOT NULL DEFAULT 'minorista' AFTER `estado`,
  ADD COLUMN `razon_social` VARCHAR(150) NULL AFTER `cuit_cliente`;
