-- ============================================================
-- Datos ficticios para `tallerapp_db`
-- 4 mecánicos · 30 piezas · 30 clientes · 30 vehículos
-- 30 reparaciones · 69 líneas de piezas usadas
--
-- Importar con la base de datos `tallerapp_db` seleccionada
-- y con las tablas vacías (se usan ids del 1 en adelante).
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = "+00:00";
START TRANSACTION;

-- ------------------------------------------------------------
-- MECÁNICOS (4)
-- ------------------------------------------------------------
INSERT INTO `mecanico` (`id`, `nombre`, `apellidos`, `especialidad`) VALUES
(1, 'Antonio', 'Ruiz Moreno', 'Mecánica general y mantenimiento'),
(2, 'Francisco Javier', 'Ortega Molina', 'Electricidad y electrónica'),
(3, 'Lucía', 'Fernández Castro', 'Motor y diagnosis'),
(4, 'Manuel', 'Heredia Santiago', 'Frenos, suspensión y neumáticos');

-- ------------------------------------------------------------
-- PIEZAS (30)
-- ------------------------------------------------------------
INSERT INTO `piezas` (`id`, `nombre_pieza`, `precio`, `numero_referencia`) VALUES
(1,  'Filtro de aceite', 9, 'FIL-AC-1001'),
(2,  'Filtro de aire', 14, 'FIL-AI-1002'),
(3,  'Filtro de combustible', 18, 'FIL-CO-1003'),
(4,  'Filtro de habitáculo', 12, 'FIL-HA-1004'),
(5,  'Aceite de motor 5W30 (5 L)', 42, 'LUB-5W30-2001'),
(6,  'Juego de pastillas de freno delanteras', 38, 'FRE-PD-3001'),
(7,  'Juego de pastillas de freno traseras', 32, 'FRE-PT-3002'),
(8,  'Disco de freno delantero', 45, 'FRE-DD-3003'),
(9,  'Disco de freno trasero', 39, 'FRE-DT-3004'),
(10, 'Líquido de frenos DOT4 (1 L)', 9, 'FRE-LQ-3005'),
(11, 'Batería 70 Ah', 95, 'ELE-BAT-4001'),
(12, 'Alternador', 210, 'ELE-ALT-4002'),
(13, 'Motor de arranque', 165, 'ELE-ARR-4003'),
(14, 'Bujía de encendido', 8, 'ELE-BUJ-4004'),
(15, 'Calentador (bujía de incandescencia)', 14, 'ELE-CAL-4005'),
(16, 'Bobina de encendido', 36, 'ELE-BOB-4006'),
(17, 'Kit de distribución', 135, 'MOT-DIS-5001'),
(18, 'Bomba de agua', 58, 'MOT-BOM-5002'),
(19, 'Correa auxiliar', 22, 'MOT-COR-5003'),
(20, 'Kit de embrague', 240, 'MOT-EMB-5004'),
(21, 'Amortiguador delantero', 72, 'SUS-AD-6001'),
(22, 'Amortiguador trasero', 61, 'SUS-AT-6002'),
(23, 'Rótula de dirección', 24, 'SUS-ROT-6003'),
(24, 'Neumático 205/55 R16', 78, 'NEU-2055516-7001'),
(25, 'Juego de escobillas limpiaparabrisas', 19, 'ACC-ESC-8001'),
(26, 'Lámpara H7 12V 55W', 7, 'ACC-H7-8002'),
(27, 'Termostato', 27, 'REF-TER-9001'),
(28, 'Radiador', 120, 'REF-RAD-9002'),
(29, 'Anticongelante (5 L)', 16, 'REF-ANT-9003'),
(30, 'Sonda lambda', 68, 'ESC-LAM-9501');

-- ------------------------------------------------------------
-- CLIENTES (30)
-- ------------------------------------------------------------
INSERT INTO `cliente` (`id`, `nombre`, `apellidos`, `telefono`) VALUES
(1,  'María', 'García López', '612345101'),
(2,  'José', 'Martínez Sánchez', '623456102'),
(3,  'Carmen', 'Rodríguez Pérez', '634567103'),
(4,  'Juan', 'Fernández Gómez', '645678104'),
(5,  'Ana', 'López Martín', '656789105'),
(6,  'David', 'Sánchez Jiménez', '667890106'),
(7,  'Laura', 'Pérez Ruiz', '678901107'),
(8,  'Miguel Ángel', 'Gómez Hernández', '689012108'),
(9,  'Isabel', 'Martín Díaz', '690123109'),
(10, 'Carlos', 'Jiménez Moreno', '611234110'),
(11, 'Elena', 'Ruiz Muñoz', '622345111'),
(12, 'Javier', 'Hernández Álvarez', '633456112'),
(13, 'Marta', 'Díaz Romero', '644567113'),
(14, 'Daniel', 'Moreno Alonso', '655678114'),
(15, 'Cristina', 'Muñoz Gutiérrez', '666789115'),
(16, 'Alejandro', 'Álvarez Navarro', '677890116'),
(17, 'Pilar', 'Romero Torres', '688901117'),
(18, 'Sergio', 'Alonso Domínguez', '699012118'),
(19, 'Rocío', 'Gutiérrez Vázquez', '610123119'),
(20, 'Pablo', 'Navarro Ramos', '621234120'),
(21, 'Beatriz', 'Torres Gil', '632345121'),
(22, 'Rafael', 'Domínguez Ramírez', '643456122'),
(23, 'Silvia', 'Vázquez Serrano', '654567123'),
(24, 'Fernando', 'Ramos Blanco', '665678124'),
(25, 'Patricia', 'Gil Molina', '676789125'),
(26, 'Alberto', 'Ramírez Morales', '687890126'),
(27, 'Nuria', 'Serrano Suárez', '698901127'),
(28, 'Francisco', 'Blanco Ortega', '619012128'),
(29, 'Inmaculada', 'Molina Delgado', '620123129'),
(30, 'Raúl', 'Morales Castro', '631234130');

-- ------------------------------------------------------------
-- VEHÍCULOS (30) — matrícula española coherente con el año
-- ------------------------------------------------------------
INSERT INTO `vehiculo` (`id`, `matricula`, `marca`, `modelo`, `anio_fabricacion`, `id_cliente`) VALUES
(1,  '4821 KNX', 'Seat', 'León', 2018, 1),
(2,  '7305 HBD', 'Renault', 'Clio', 2011, 2),
(3,  '1947 LTR', 'Volkswagen', 'Golf', 2021, 3),
(4,  '6630 FZK', 'Ford', 'Focus', 2008, 4),
(5,  '2284 JNP', 'Peugeot', '308', 2016, 5),
(6,  '9012 GWC', 'Citroën', 'C4', 2010, 6),
(7,  '3571 MDF', 'Toyota', 'Corolla', 2023, 7),
(8,  '8046 DKT', 'Opel', 'Astra', 2005, 8),
(9,  '5519 KGS', 'Nissan', 'Qashqai', 2018, 9),
(10, '0763 LHV', 'Hyundai', 'Tucson', 2020, 10),
(11, '4398 CRB', 'Seat', 'Ibiza', 2003, 11),
(12, '6127 JZY', 'Kia', 'Sportage', 2017, 12),
(13, '2850 HMN', 'Volkswagen', 'Polo', 2012, 13),
(14, '7714 LBG', 'Dacia', 'Sandero', 2019, 14),
(15, '1036 FPW', 'BMW', 'Serie 3', 2007, 15),
(16, '9465 KLC', 'Audi', 'A3', 2018, 16),
(17, '3209 GJH', 'Mercedes-Benz', 'Clase A', 2009, 17),
(18, '5882 MKR', 'Renault', 'Captur', 2023, 18),
(19, '6741 JDT', 'Fiat', '500', 2015, 19),
(20, '0298 HVX', 'Skoda', 'Octavia', 2013, 20),
(21, '8153 LWN', 'Peugeot', '2008', 2021, 21),
(22, '4470 DSF', 'Citroën', 'Xsara Picasso', 2006, 22),
(23, '2916 KTB', 'Toyota', 'Yaris', 2019, 23),
(24, '7039 BXG', 'Ford', 'Fiesta', 2002, 24),
(25, '1624 JSM', 'Mazda', '3', 2016, 25),
(26, '9387 MBP', 'Seat', 'Arona', 2022, 26),
(27, '5560 GCZ', 'Volkswagen', 'Passat', 2008, 27),
(28, '3842 LMK', 'Hyundai', 'i30', 2020, 28),
(29, '6095 HHJ', 'Opel', 'Corsa', 2011, 29),
(30, '8271 CGL', 'Renault', 'Mégane', 2003, 30);

-- ------------------------------------------------------------
-- REPARACIONES (30) — las dos últimas siguen en el taller
-- ------------------------------------------------------------
INSERT INTO `reparaciones` (`id`, `id_mecanico`, `id_vehiculo`, `fecha_de_entrada`, `fecha_salida`, `descripcion_averia`, `mano_de_obra`) VALUES
(1,  1, 1,  '2026-01-08 09:15:00', '2026-01-08 13:30:00', 'Mantenimiento periódico: cambio de aceite, filtro de aceite y filtro de aire', 45.00),
(2,  4, 2,  '2026-01-14 08:40:00', '2026-01-15 12:00:00', 'Pastillas y discos de freno delanteros desgastados, vibración al frenar', 80.00),
(3,  2, 3,  '2026-01-21 10:05:00', '2026-01-21 11:20:00', 'El vehículo no arranca, batería agotada', 25.00),
(4,  3, 4,  '2026-02-02 09:00:00', '2026-02-04 17:30:00', 'Sustitución del kit de distribución, bomba de agua y correa auxiliar', 320.00),
(5,  1, 5,  '2026-02-10 08:30:00', '2026-02-10 14:00:00', 'Revisión general previa a la ITV, lámparas de cruce fundidas', 60.00),
(6,  2, 6,  '2026-02-18 11:15:00', '2026-02-19 13:45:00', 'Testigo de batería encendido, el alternador no carga', 110.00),
(7,  1, 7,  '2026-02-25 09:30:00', '2026-02-25 12:15:00', 'Primera revisión de mantenimiento', 40.00),
(8,  3, 8,  '2026-03-05 08:45:00', '2026-03-09 18:00:00', 'El embrague patina y el pedal está duro', 380.00),
(9,  4, 9,  '2026-03-12 10:20:00', '2026-03-13 11:30:00', 'Ruidos en la suspensión trasera, amortiguadores con fugas', 120.00),
(10, 2, 10, '2026-03-19 09:10:00', '2026-03-19 16:40:00', 'Tirones al acelerar y testigo de motor encendido, fallo de encendido', 55.00),
(11, 3, 11, '2026-03-26 12:00:00', '2026-03-27 13:00:00', 'Sobrecalentamiento del motor, termostato bloqueado', 95.00),
(12, 1, 12, '2026-04-07 08:35:00', '2026-04-07 15:20:00', 'Mantenimiento de los 90.000 km con todos los filtros', 65.00),
(13, 4, 13, '2026-04-15 09:50:00', '2026-04-15 13:10:00', 'Pastillas de freno traseras gastadas y cambio de líquido de frenos', 60.00),
(14, 1, 14, '2026-04-22 10:30:00', '2026-04-22 12:00:00', 'Cambio de aceite y filtro de aceite', 35.00),
(15, 3, 15, '2026-05-04 08:50:00', '2026-05-06 12:30:00', 'Dificultad de arranque en frío, calentadores defectuosos', 90.00),
(16, 4, 16, '2026-05-13 11:00:00', '2026-05-13 12:45:00', 'Cambio de neumáticos delanteros y equilibrado', 30.00),
(17, 2, 17, '2026-05-20 09:25:00', '2026-05-21 10:50:00', 'No gira el motor al dar al contacto, motor de arranque defectuoso', 100.00),
(18, 1, 18, '2026-06-03 16:10:00', '2026-06-03 17:00:00', 'Sustitución de escobillas limpiaparabrisas y lámparas de cruce', 15.00),
(19, 3, 19, '2026-06-11 09:40:00', '2026-06-11 14:30:00', 'Testigo de motor encendido y consumo elevado, sonda lambda averiada', 70.00),
(20, 4, 20, '2026-06-24 08:55:00', '2026-06-24 15:15:00', 'Holgura en la dirección, rótulas en mal estado y alineación', 85.00),
(21, 1, 21, '2026-07-06 09:05:00', '2026-07-06 13:20:00', 'Mantenimiento anual antes de viaje de verano', 45.00),
(22, 3, 22, '2026-07-15 10:45:00', '2026-07-17 12:00:00', 'Fuga de refrigerante, radiador picado', 140.00),
(23, 2, 23, '2026-07-28 12:30:00', '2026-07-28 13:40:00', 'Batería descargada tras varios arranques fallidos, comprobación de carga', 30.00),
(24, 3, 24, '2026-09-02 08:40:00', '2026-09-04 17:00:00', 'Sustitución preventiva de distribución y bomba de agua', 290.00),
(25, 4, 25, '2026-09-10 09:15:00', '2026-09-11 11:00:00', 'Rebotes en el tren delantero, amortiguadores delanteros agotados', 130.00),
(26, 1, 26, '2026-09-23 15:30:00', '2026-09-23 18:00:00', 'Mantenimiento periódico', 40.00),
(27, 4, 27, '2026-10-06 08:30:00', '2026-10-07 13:30:00', 'Renovación completa de frenos: discos, pastillas y líquido', 150.00),
(28, 2, 28, '2026-10-20 10:00:00', '2026-10-20 14:15:00', 'Fallo de bobina en un cilindro, se sustituyen también las bujías', 45.00),
(29, 3, 29, '2026-11-10 09:20:00', NULL, 'Embrague desgastado, ruido al pisar el pedal', 360.00),
(30, 1, 30, '2026-11-12 11:45:00', NULL, 'Mantenimiento completo y correa auxiliar agrietada', 75.00);

-- ------------------------------------------------------------
-- PIEZAS USADAS EN CADA REPARACIÓN
-- ------------------------------------------------------------
INSERT INTO `piezas_reparaciones` (`id_piezas`, `id_reparaciones`, `unidades`) VALUES
-- Reparación 1: mantenimiento
(1, 1, 1), (5, 1, 1), (2, 1, 1),
-- Reparación 2: frenos delanteros
(6, 2, 1), (8, 2, 2),
-- Reparación 3: batería
(11, 3, 1),
-- Reparación 4: distribución
(17, 4, 1), (18, 4, 1), (19, 4, 1), (29, 4, 1),
-- Reparación 5: pre-ITV
(1, 5, 1), (5, 5, 1), (4, 5, 1), (26, 5, 2),
-- Reparación 6: alternador
(12, 6, 1), (19, 6, 1),
-- Reparación 7: primera revisión
(1, 7, 1), (5, 7, 1), (4, 7, 1),
-- Reparación 8: embrague
(20, 8, 1),
-- Reparación 9: amortiguadores traseros
(22, 9, 2),
-- Reparación 10: encendido
(16, 10, 1), (14, 10, 4),
-- Reparación 11: sobrecalentamiento
(27, 11, 1), (29, 11, 1),
-- Reparación 12: mantenimiento 90.000 km
(1, 12, 1), (2, 12, 1), (3, 12, 1), (4, 12, 1), (5, 12, 1),
-- Reparación 13: frenos traseros
(7, 13, 1), (10, 13, 1),
-- Reparación 14: aceite
(1, 14, 1), (5, 14, 1),
-- Reparación 15: calentadores
(15, 15, 4),
-- Reparación 16: neumáticos
(24, 16, 2),
-- Reparación 17: motor de arranque
(13, 17, 1),
-- Reparación 18: escobillas y lámparas
(25, 18, 1), (26, 18, 2),
-- Reparación 19: sonda lambda
(30, 19, 1),
-- Reparación 20: rótulas
(23, 20, 2),
-- Reparación 21: mantenimiento anual
(1, 21, 1), (5, 21, 1), (2, 21, 1), (4, 21, 1),
-- Reparación 22: radiador
(28, 22, 1), (29, 22, 2),
-- Reparación 23: batería
(11, 23, 1),
-- Reparación 24: distribución
(17, 24, 1), (18, 24, 1), (29, 24, 1),
-- Reparación 25: amortiguadores delanteros
(21, 25, 2),
-- Reparación 26: mantenimiento
(1, 26, 1), (5, 26, 1), (4, 26, 1),
-- Reparación 27: frenos completos
(6, 27, 1), (7, 27, 1), (8, 27, 2), (9, 27, 2), (10, 27, 1),
-- Reparación 28: bobina y bujías
(16, 28, 1), (14, 28, 4),
-- Reparación 29: embrague (en curso)
(20, 29, 1),
-- Reparación 30: mantenimiento y correa (en curso)
(1, 30, 1), (5, 30, 1), (2, 30, 1), (19, 30, 1);

COMMIT;
