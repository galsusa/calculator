-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-02-2025 a las 01:17:09
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `bruc_sustratos_aridos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulos`
--

CREATE TABLE `articulos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `articulos`
--

INSERT INTO `articulos` (`id`, `nombre`) VALUES
(12, 'Grava Volcánica 4/7'),
(13, 'Grava Volcánica 7/12'),
(14, 'Grava Volcánica 15/25'),
(15, 'Grava Volcánica 25/50'),
(16, 'Tierra Abonada'),
(17, 'Compost Vegetal'),
(18, 'Recebo'),
(19, 'Estiércol'),
(20, 'Corteza Decorativa 20/40'),
(21, 'Corteza Decorativa 15/25'),
(22, 'Mulching de Pino'),
(23, 'Bala Turba 250L'),
(24, 'Arena Lavada de rio'),
(25, 'Arena de Sílice');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_presentaciones`
--

CREATE TABLE `producto_presentaciones` (
  `producto_id` int(11) NOT NULL,
  `saco_litros` int(11) DEFAULT NULL,
  `peso_saco_litros` decimal(10,2) DEFAULT NULL,
  `volumen_saca_big` decimal(10,2) DEFAULT NULL,
  `peso_saca_big` decimal(10,2) DEFAULT NULL,
  `peso_mediasaca_big` decimal(10,2) DEFAULT NULL,
  `saco_kg` int(11) DEFAULT NULL,
  `rendimiento_kg_m3` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `producto_presentaciones`
--

INSERT INTO `producto_presentaciones` (`producto_id`, `saco_litros`, `peso_saco_litros`, `volumen_saca_big`, `peso_saca_big`, `peso_mediasaca_big`, `saco_kg`, `rendimiento_kg_m3`) VALUES
(12, 25, 24.00, NULL, NULL, NULL, NULL, 960.00),
(13, 25, 24.00, 790.00, 750.00, 375.00, NULL, 950.00),
(14, 25, 24.00, 790.00, 750.00, 375.00, NULL, 950.00),
(15, 25, 20.00, 790.00, 750.00, 375.00, NULL, 950.00),
(16, NULL, NULL, 750.00, 900.00, 450.00, NULL, 1200.00),
(17, 50, 20.00, 900.00, 550.00, 275.00, NULL, 800.00),
(18, 50, 20.00, 900.00, 900.00, 450.00, NULL, 1000.00),
(19, 50, 19.00, 900.00, 550.00, 225.00, NULL, 600.00),
(20, 50, 10.00, 800.00, 300.00, 150.00, NULL, 380.00),
(21, NULL, NULL, 800.00, 300.00, 150.00, NULL, 380.00),
(22, NULL, NULL, 800.00, 300.00, 150.00, NULL, 380.00),
(23, 250, 50.00, 800.00, NULL, NULL, NULL, 200.00),
(24, NULL, NULL, 700.00, 1000.00, 400.00, 20, 1500.00),
(25, NULL, NULL, 700.00, 1000.00, 500.00, 25, 1450.00);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `articulos`
--
ALTER TABLE `articulos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `producto_presentaciones`
--
ALTER TABLE `producto_presentaciones`
  ADD PRIMARY KEY (`producto_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `articulos`
--
ALTER TABLE `articulos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
