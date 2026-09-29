-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 18, 2026 at 10:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kiosco_online`
--

-- --------------------------------------------------------

--
-- Table structure for table `ordenes`
--

CREATE TABLE `ordenes` (
  `id` int(11) NOT NULL,
  `external_reference` varchar(64) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `estado` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `tipo_factura` enum('A','B') NOT NULL DEFAULT 'B',
  `cuit_cliente` varchar(13) DEFAULT NULL,
  `iva_monto` decimal(10,2) NOT NULL DEFAULT 0.00,
  `mp_payment_id` varchar(100) DEFAULT NULL,
  `mp_merchant_order_id` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ordenes`
--

INSERT INTO `ordenes` (`id`, `external_reference`, `monto_total`, `estado`, `tipo_factura`, `cuit_cliente`, `iva_monto`, `mp_payment_id`, `mp_merchant_order_id`, `created_at`, `updated_at`) VALUES
(1, 'KIOSCO-1787083381-790931', 1100.00, 'pending', 'B', NULL, 0.00, NULL, NULL, '2026-08-18 17:03:01', '2026-08-18 17:03:01');

-- --------------------------------------------------------

--
-- Table structure for table `orden_items`
--

CREATE TABLE `orden_items` (
  `id` int(11) NOT NULL,
  `orden_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `precio_mayorista` decimal(10,2) NOT NULL DEFAULT 0.00,
  `categoria` varchar(50) NOT NULL,
  `unidad` varchar(50) NOT NULL DEFAULT 'unidad',
  `imagen_url` varchar(500) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `destacado` tinyint(1) DEFAULT 0,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `productos`
--

INSERT INTO `productos` (`id`, `nombre`, `descripcion`, `precio`, `precio_mayorista`, `categoria`, `unidad`, `imagen_url`, `stock`, `destacado`, `fecha_creacion`) VALUES
(1, 'Cemento Portland', 'Bolsa de cemento Portland de uso general.', 9800.00, 8200.00, 'Cemento y Áridos', 'bolsa 50kg', 'https://loremflickr.com/500/400/cementbag', 100, 1, '2026-08-18 17:11:35'),
(2, 'Arena gruesa', 'Arena gruesa para mezclas y contrapisos.', 45000.00, 38000.00, 'Cemento y Áridos', 'm³', 'https://loremflickr.com/500/400/sandpile', 50, 0, '2026-08-18 17:11:35'),
(3, 'Cal hidratada', 'Cal hidratada para morteros y revoques.', 4200.00, 3500.00, 'Cemento y Áridos', 'bolsa 25kg', 'https://loremflickr.com/500/400/cementbag', 80, 0, '2026-08-18 17:11:35'),
(4, 'Hierro Ø8mm x 12m', 'Varilla de hierro para hormigón armado.', 12500.00, 10800.00, 'Hierros y Perfiles', 'varilla', 'https://loremflickr.com/500/400/rebar', 60, 1, '2026-08-18 17:11:35'),
(5, 'Hierro Ø10mm x 12m', 'Varilla de hierro para hormigón armado.', 18200.00, 15600.00, 'Hierros y Perfiles', 'varilla', 'https://loremflickr.com/500/400/rebar', 60, 0, '2026-08-18 17:11:35'),
(6, 'Malla soldada 15x15', 'Panel de malla soldada para losas.', 31000.00, 27000.00, 'Hierros y Perfiles', 'panel 2.15x5m', 'https://loremflickr.com/500/400/rebarmesh', 25, 0, '2026-08-18 17:11:35'),
(7, 'Ladrillo hueco 8x18x33', 'Ladrillo hueco cerámico para muros.', 350.00, 280.00, 'Ladrillos y Bloques', 'unidad', 'https://loremflickr.com/500/400/redbrick', 2000, 1, '2026-08-18 17:11:35'),
(8, 'Bloque de hormigón 20x20x40', 'Bloque de hormigón para mampostería.', 890.00, 740.00, 'Ladrillos y Bloques', 'unidad', 'https://loremflickr.com/500/400/cinderblock', 800, 0, '2026-08-18 17:11:35'),
(9, 'Látex interior premium', 'Pintura látex interior de alta cobertura.', 68000.00, 57000.00, 'Pinturas', 'balde 20L', 'https://loremflickr.com/500/400/paintroller', 30, 1, '2026-08-18 17:11:35'),
(10, 'Antióxido convertidor', 'Convertidor de óxido para metales.', 8900.00, 7400.00, 'Pinturas', 'lata 1L', 'https://loremflickr.com/500/400/paintcan', 45, 0, '2026-08-18 17:11:35'),
(11, 'Tornillo autoperforante', 'Caja de tornillos autoperforantes.', 4200.00, 3400.00, 'Tornillería y Fijaciones', 'caja x100', 'https://loremflickr.com/500/400/screws', 90, 0, '2026-08-18 17:11:35'),
(12, 'Taco fischer S8', 'Caja de tacos fischer S8.', 3100.00, 2500.00, 'Tornillería y Fijaciones', 'caja x50', 'https://loremflickr.com/500/400/screws', 90, 0, '2026-08-18 17:11:35'),
(13, 'Caño PVC 110mm x4m', 'Caño de PVC para desagües.', 15800.00, 13200.00, 'Caños y Membranas', 'unidad', 'https://loremflickr.com/500/400/pvcpipe', 40, 1, '2026-08-18 17:11:35'),
(14, 'Membrana asfáltica', 'Rollo de membrana asfáltica para techos.', 42000.00, 36500.00, 'Caños y Membranas', 'rollo 10m²', 'https://loremflickr.com/500/400/asphaltroof', 20, 0, '2026-08-18 17:11:35'),
(15, 'Martillo de uña 27mm', 'Martillo de carpintero con mango de fibra de vidrio.', 8500.00, 7100.00, 'Herramientas Manuales', 'unidad', 'https://loremflickr.com/500/400/hammer', 40, 1, '2026-08-18 17:29:24'),
(16, 'Maza de goma 500g', 'Maza de goma para trabajos de precisión sin marcar.', 6200.00, 5100.00, 'Herramientas Manuales', 'unidad', 'https://loremflickr.com/500/400/rubbermallet', 30, 0, '2026-08-18 17:29:24'),
(17, 'Pinza universal 8\"', 'Pinza universal de acero forjado.', 5400.00, 4500.00, 'Herramientas Manuales', 'unidad', 'https://loremflickr.com/500/400/pliers', 45, 0, '2026-08-18 17:29:24'),
(18, 'Set destornilladores x6', 'Juego de destornilladores planos y phillips.', 9800.00, 8200.00, 'Herramientas Manuales', 'set x6', 'https://loremflickr.com/500/400/screwdriverset', 35, 1, '2026-08-18 17:29:24'),
(19, 'Juego de llaves combinadas', 'Set de llaves combinadas 8-19mm, acero al cromo vanadio.', 24000.00, 20500.00, 'Herramientas Manuales', 'set x10', 'https://loremflickr.com/500/400/wrenchset', 20, 1, '2026-08-18 17:29:24'),
(20, 'Llave ajustable 10\"', 'Llave francesa ajustable de 10 pulgadas.', 7800.00, 6500.00, 'Herramientas Manuales', 'unidad', 'https://loremflickr.com/500/400/wrench', 30, 0, '2026-08-18 17:29:24'),
(21, 'Serrucho para madera 20\"', 'Serrucho de dientes templados para madera.', 11500.00, 9600.00, 'Herramientas Manuales', 'unidad', 'https://loremflickr.com/500/400/handsaw', 25, 0, '2026-08-18 17:29:24'),
(22, 'Cinta métrica 5m', 'Cinta métrica retráctil con traba.', 4200.00, 3400.00, 'Herramientas Manuales', 'unidad', 'https://loremflickr.com/500/400/tapemeasure', 60, 1, '2026-08-18 17:29:24'),
(23, 'Nivel de burbuja 60cm', 'Nivel de aluminio con 3 burbujas de precisión.', 9200.00, 7700.00, 'Herramientas Manuales', 'unidad', 'https://loremflickr.com/500/400/spiritlevel', 28, 0, '2026-08-18 17:29:24'),
(24, 'Set de mechas para metal x10', 'Juego de mechas HSS para taladro, 1-10mm.', 13500.00, 11200.00, 'Herramientas Manuales', 'set x10', 'https://loremflickr.com/500/400/drillbits', 22, 0, '2026-08-18 17:29:24');

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password_hash`, `nombre`, `created_at`) VALUES
(1, 'admin', '$2y$10$.a1UotQxRFgqiQeW5wZOY.deNxa1DC/qS3gqwuSgw6TqyCjPMa9kq', 'Administrador Kiosco', '2026-08-18 16:39:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ordenes`
--
ALTER TABLE `ordenes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `external_reference` (`external_reference`);

--
-- Indexes for table `orden_items`
--
ALTER TABLE `orden_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orden_id` (`orden_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indexes for table `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ordenes`
--
ALTER TABLE `ordenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orden_items`
--
ALTER TABLE `orden_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orden_items`
--
ALTER TABLE `orden_items`
  ADD CONSTRAINT `orden_items_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orden_items_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
