-- Taller Genil · estructura de la base de datos (SIN datos)
-- Importar en una base vacía:  mysql -u USUARIO -p tallerapp_db < data/tallerapp_db.sql


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `accesos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accesos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  `evento` varchar(30) NOT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ip_fecha` (`ip`,`fecha`),
  KEY `email_fecha` (`email`,`fecha`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cliente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cliente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `apellidos` varchar(120) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `codigos_usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `codigos_usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('activar','restablecer','login','verificar') NOT NULL,
  `hash` char(64) NOT NULL,
  `expira` datetime NOT NULL,
  `intentos` tinyint(4) NOT NULL DEFAULT 0,
  `usado` datetime DEFAULT NULL,
  `creado` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_tipo` (`usuario_id`,`tipo`),
  KEY `hash` (`hash`),
  CONSTRAINT `codigos_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `facturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_reparacion` int(11) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `fecha` datetime NOT NULL,
  `base` decimal(10,2) NOT NULL,
  `tipo_iva` decimal(4,2) NOT NULL,
  `iva` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_reparacion` (`id_reparacion`),
  UNIQUE KEY `numero` (`numero`),
  CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`id_reparacion`) REFERENCES `reparaciones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mecanico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mecanico` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellidos` varchar(60) NOT NULL,
  `especialidad` enum('mecanica general','electromacanica','chapa y pintura','') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `piezas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `piezas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_pieza` varchar(255) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `numero_referencia` varchar(60) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `piezas_reparaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `piezas_reparaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_piezas` int(11) NOT NULL,
  `id_reparaciones` int(11) NOT NULL,
  `unidades` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_piezas` (`id_piezas`),
  KEY `id_reparaciones` (`id_reparaciones`),
  CONSTRAINT `piezas_reparaciones_ibfk_1` FOREIGN KEY (`id_piezas`) REFERENCES `piezas` (`id`),
  CONSTRAINT `piezas_reparaciones_ibfk_2` FOREIGN KEY (`id_reparaciones`) REFERENCES `reparaciones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reparaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reparaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_mecanico` int(11) NOT NULL,
  `id_vehiculo` int(11) NOT NULL,
  `fecha_entrada` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fecha_salida` timestamp NULL DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `mano_de_obra` decimal(7,2) NOT NULL,
  `horas` decimal(6,2) DEFAULT NULL,
  `precio_hora` decimal(7,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_vehiculo` (`id_vehiculo`),
  KEY `id_mecanico` (`id_mecanico`),
  CONSTRAINT `reparaciones_ibfk_1` FOREIGN KEY (`id_mecanico`) REFERENCES `mecanico` (`id`),
  CONSTRAINT `reparaciones_ibfk_2` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `apellidos` varchar(120) NOT NULL,
  `dni` char(9) NOT NULL,
  `email` varchar(190) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `rol` enum('admin','usuario') NOT NULL DEFAULT 'usuario',
  `cliente_id` int(11) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `debe_cambiar_password` tinyint(1) NOT NULL DEFAULT 0,
  `email_verificado` datetime DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_alta` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_baja` datetime DEFAULT NULL,
  `motivo_baja` varchar(255) DEFAULT NULL,
  `ultimo_acceso` datetime DEFAULT NULL,
  `creado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dni` (`dni`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `cliente_id` (`cliente_id`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `usuarios_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`),
  CONSTRAINT `usuarios_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vehiculo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehiculo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matricula` varchar(20) NOT NULL,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(60) NOT NULL,
  `anio_fabricacion` year(4) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `id_cliente` (`id_cliente`),
  CONSTRAINT `vehiculo_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

