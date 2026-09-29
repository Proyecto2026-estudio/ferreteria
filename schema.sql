-- =======================================================
-- Ferretería y Materiales de Construcción — Equipo 8 (7mo Info B, 2026)
-- Cotizador Minorista / Mayorista + Factura A / B con Mercado Pago
-- Base de datos: kiosco_online (se mantiene el nombre del proyecto base
-- para no tener que tocar env.php / .env)
--
-- ATENCIÓN: este script BORRA y vuelve a crear las tablas.
-- Si ya tienen órdenes que quieren conservar, usen db/migracion_equipo8.sql
-- =======================================================

CREATE DATABASE IF NOT EXISTS `kiosco_online` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kiosco_online`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `orden_items`;
DROP TABLE IF EXISTS `ordenes`;
DROP TABLE IF EXISTS `productos`;
DROP TABLE IF EXISTS `usuarios`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Tabla: usuarios (módulo de administración)
-- --------------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: productos
--   precio            = precio de lista MINORISTA (IVA incluido)
--   precio_mayorista  = precio MAYORISTA (IVA incluido)
--   unidad            = unidad de venta (bolsa 50kg, m³, varilla, caja x100...)
-- --------------------------------------------------------
CREATE TABLE `productos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` TEXT NULL,
  `precio` DECIMAL(10, 2) NOT NULL,
  `precio_mayorista` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `categoria` VARCHAR(50) NOT NULL,
  `unidad` VARCHAR(50) NOT NULL DEFAULT 'unidad',
  `imagen_url` VARCHAR(500) NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `destacado` TINYINT(1) DEFAULT 0,
  `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: ordenes
--   monto_total   = lo que paga el cliente (IVA incluido, igual en A y B)
--   iva_monto     = IVA discriminado (solo Factura A; en B queda en 0)
-- --------------------------------------------------------
CREATE TABLE `ordenes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `external_reference` VARCHAR(64) NOT NULL UNIQUE,
  `monto_total` DECIMAL(10, 2) NOT NULL,
  `estado` ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
  `modalidad` ENUM('minorista', 'mayorista') NOT NULL DEFAULT 'minorista',
  `tipo_factura` ENUM('A', 'B') NOT NULL DEFAULT 'B',
  `cuit_cliente` VARCHAR(13) NULL,
  `razon_social` VARCHAR(150) NULL,
  `iva_monto` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `mp_payment_id` VARCHAR(100) NULL,
  `mp_merchant_order_id` VARCHAR(100) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabla: orden_items
-- --------------------------------------------------------
CREATE TABLE `orden_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `precio_unitario` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`orden_id`) REFERENCES `ordenes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Usuario administrador (admin / admin123)
-- --------------------------------------------------------
INSERT INTO `usuarios` (`username`, `password_hash`, `nombre`) VALUES
('admin', '$2y$10$.a1UotQxRFgqiQeW5wZOY.deNxa1DC/qS3gqwuSgw6TqyCjPMa9kq', 'Administrador Ferretería');

-- --------------------------------------------------------
-- Catálogo inicial de la ferretería
-- Precios de referencia de corralones y ferreterías de Argentina (septiembre 2026), IVA incluido.
-- Mayorista: alrededor de 12-15% menos que el minorista.
-- --------------------------------------------------------
INSERT INTO `productos` (`nombre`, `descripcion`, `precio`, `precio_mayorista`, `categoria`, `unidad`, `imagen_url`, `stock`, `destacado`) VALUES
('Cemento Portland', 'Bolsa de cemento Portland de uso general.', 9800.00, 8500.00, 'Cemento y Áridos', 'bolsa 50kg', 'public/img/cemento.svg', 100, 1),
('Arena gruesa', 'Arena gruesa para mezclas y contrapisos.', 45000.00, 39000.00, 'Cemento y Áridos', 'm³', 'public/img/arena.svg', 50, 0),
('Cal hidratada', 'Cal hidratada para morteros y revoques.', 7900.00, 6800.00, 'Cemento y Áridos', 'bolsa 25kg', 'public/img/cal.svg', 80, 0),
('Hierro Ø8mm x 12m', 'Varilla de hierro para hormigón armado.', 12300.00, 10900.00, 'Hierros y Perfiles', 'varilla', 'public/img/hierro.svg', 60, 1),
('Hierro Ø10mm x 12m', 'Varilla de hierro para hormigón armado.', 19000.00, 16800.00, 'Hierros y Perfiles', 'varilla', 'public/img/hierro.svg', 60, 0),
('Malla soldada 15x15', 'Panel de malla soldada para losas.', 72700.00, 64000.00, 'Hierros y Perfiles', 'panel 6x2.15m', 'public/img/malla.svg', 25, 0),
('Ladrillo hueco 8x18x33', 'Ladrillo hueco cerámico para muros.', 760.00, 620.00, 'Ladrillos y Bloques', 'unidad', 'public/img/ladrillo.svg', 2000, 1),
('Bloque de hormigón 20x20x40', 'Bloque de hormigón para mampostería.', 1300.00, 1100.00, 'Ladrillos y Bloques', 'unidad', 'public/img/bloque.svg', 800, 0),
('Látex interior premium', 'Pintura látex interior de alta cobertura.', 46000.00, 40000.00, 'Pinturas', 'balde 20L', 'public/img/latex.svg', 30, 1),
('Antióxido convertidor', 'Convertidor de óxido para metales.', 24000.00, 21000.00, 'Pinturas', 'lata 1L', 'public/img/antioxido.svg', 45, 0),
('Tornillo autoperforante', 'Caja de tornillos autoperforantes.', 9200.00, 8000.00, 'Tornillería y Fijaciones', 'caja x100', 'public/img/tornillo.svg', 90, 0),
('Taco fischer S8', 'Caja de tacos de nylon fischer S8.', 5950.00, 5100.00, 'Tornillería y Fijaciones', 'caja x100', 'public/img/taco.svg', 90, 0),
('Caño PVC 110mm x4m', 'Caño de PVC para desagües.', 25400.00, 22000.00, 'Caños y Membranas', 'unidad', 'public/img/cano.svg', 40, 1),
('Membrana asfáltica', 'Rollo de membrana asfáltica para techos.', 74500.00, 65000.00, 'Caños y Membranas', 'rollo 10m²', 'public/img/membrana.svg', 20, 0);
