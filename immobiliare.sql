-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mag 20, 2025 alle 09:22
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `immobiliare`
--
CREATE DATABASE IF NOT EXISTS `immobiliare` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `immobiliare`;

-- --------------------------------------------------------

--
-- Struttura della tabella `acquisti`
--

DROP TABLE IF EXISTS `acquisti`;
CREATE TABLE `acquisti` (
  `id` int(11) NOT NULL,
  `id_immobile` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `acconto` decimal(10,2) DEFAULT NULL,
  `metodo_pagamento` varchar(50) DEFAULT NULL,
  `piano_rate` int(11) DEFAULT NULL,
  `importo_totale` decimal(10,2) DEFAULT NULL,
  `tipo_acquisto` varchar(50) DEFAULT NULL,
  `modalita_pagamento` varchar(50) DEFAULT NULL,
  `stato_pagamento` varchar(50) DEFAULT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `data_acquisto` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `acquisti`
--

INSERT INTO `acquisti` (`id`, `id_immobile`, `id_utente`, `acconto`, `metodo_pagamento`, `piano_rate`, `importo_totale`, `tipo_acquisto`, `modalita_pagamento`, `stato_pagamento`, `payment_id`, `note`, `data_acquisto`) VALUES
(8, 66, 3, 75000.00, '0', 48, 829054.56, 'acquisto', 'rate', 'in attesa', NULL, '', '2025-05-19 06:44:15'),
(9, 63, 3, 48000.00, '0', 12, 493099.08, 'acquisto', 'rate', 'in attesa', NULL, '', '2025-05-19 06:58:49');

-- --------------------------------------------------------

--
-- Struttura della tabella `agenti_immobiliari`
--

DROP TABLE IF EXISTS `agenti_immobiliari`;
CREATE TABLE `agenti_immobiliari` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cognome` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `id_agenzia` int(11) DEFAULT NULL,
  `data_assunzione` timestamp NOT NULL DEFAULT current_timestamp(),
  `ruolo` enum('senior','junior') DEFAULT 'junior',
  `Password` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `agenti_immobiliari`
--

INSERT INTO `agenti_immobiliari` (`id`, `nome`, `cognome`, `email`, `telefono`, `id_agenzia`, `data_assunzione`, `ruolo`, `Password`) VALUES
(1, 'Luca', 'Bianchi', 'luca.bianchi@example.com', '3331112222', NULL, '2025-04-17 08:08:16', 'senior', 'Bianchi'),
(2, 'Anna', 'Neri', 'anna.neri@example.com', '3334445555', NULL, '2025-04-17 08:08:16', 'junior', '');

-- --------------------------------------------------------

--
-- Struttura della tabella `categorie`
--

DROP TABLE IF EXISTS `categorie`;
CREATE TABLE `categorie` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `categorie`
--

INSERT INTO `categorie` (`id`, `nome`) VALUES
(1, 'Appartamenti'),
(2, 'Ville'),
(3, 'Monolocali');

-- --------------------------------------------------------

--
-- Struttura della tabella `contatti`
--

DROP TABLE IF EXISTS `contatti`;
CREATE TABLE `contatti` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `messaggio` text NOT NULL,
  `data_invio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `contatti`
--

INSERT INTO `contatti` (`id`, `nome`, `email`, `messaggio`, `data_invio`) VALUES
(1, 'Marco', 'marco@example.com', 'Vorrei maggiori informazioni sull\'appartamento in centro.', '2025-04-17 08:08:16');

-- --------------------------------------------------------

--
-- Struttura della tabella `immobili`
--

DROP TABLE IF EXISTS `immobili`;
CREATE TABLE `immobili` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descrizione` text DEFAULT NULL,
  `prezzo` decimal(10,2) DEFAULT NULL,
  `immagine` varchar(255) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `agente_id` int(11) DEFAULT NULL,
  `stato` enum('disponibile','venduto','affittato') DEFAULT 'disponibile',
  `data_inserimento` timestamp NOT NULL DEFAULT current_timestamp(),
  `metri_quadri` int(11) NOT NULL,
  `stanze` int(11) NOT NULL,
  `bagni` int(11) NOT NULL,
  `citta` varchar(50) NOT NULL,
  `provincia` varchar(50) NOT NULL,
  `latitudine` decimal(10,8) DEFAULT NULL,
  `longitudine` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `immobili`
--

INSERT INTO `immobili` (`id`, `nome`, `descrizione`, `prezzo`, `immagine`, `categoria_id`, `agente_id`, `stato`, `data_inserimento`, `metri_quadri`, `stanze`, `bagni`, `citta`, `provincia`, `latitudine`, `longitudine`) VALUES
(60, 'Villa con giardino', 'Villa spaziosa con giardino privato', 400000.00, 'casa2.jpg', 2, 2, '', '2025-04-18 19:27:21', 180, 6, 3, 'Milano', 'MI', 45.46421100, 9.19138300),
(61, 'Monolocale vista mare', 'Monolocale con vista mare e terrazzo', 120000.00, 'casa3.jpg', 3, 1, 'disponibile', '2025-04-18 19:27:21', 45, 1, 1, 'Genova', 'GE', 44.40565000, 8.94625600),
(62, 'Appartamento economico', 'Appartamento economico vicino ai servizi', 95000.00, 'casa4.jpg', 1, 2, '', '2025-04-18 19:27:21', 60, 2, 1, 'Napoli', 'NA', 40.85177500, 14.26812400),
(63, 'Villa bifamiliare', 'Villa grande adatta per due famiglie', 480000.00, 'casa5.jpg', 2, 1, 'disponibile', '2025-04-18 19:27:21', 210, 8, 4, 'Verona', 'VR', 45.43838400, 10.99162200),
(64, 'Monolocale centrale', 'Monolocale perfetto per studenti', 85000.00, 'casa6.jpg', 3, 2, 'disponibile', '2025-04-18 19:27:21', 35, 1, 1, 'Pisa', 'PI', 43.71654100, 10.39659700),
(65, 'Appartamento luminoso', 'Luminoso appartamento vicino al centro', 240000.00, 'casa7.jpg', 1, 1, 'disponibile', '2025-04-18 19:27:21', 85, 3, 1, 'Bologna', 'BO', 44.49488700, 11.34261600),
(66, 'Villa esclusiva', 'Villa esclusiva con piscina', 750000.00, 'casa8.jpg', 2, 2, 'venduto', '2025-04-18 19:27:21', 300, 7, 4, 'Cagliari', 'CA', 39.22384100, 9.12166100),
(67, 'Monolocale turistico', 'Monolocale adatto per turismo', 150000.00, 'casa9.jpg', 3, 1, 'disponibile', '2025-04-18 19:27:21', 40, 1, 1, 'Venezia', 'VE', 45.44084700, 12.31551500),
(68, 'Appartamento signorile', 'Elegante appartamento ristrutturato', 420000.00, 'casa10.jpg', 1, 2, 'disponibile', '2025-04-18 19:27:21', 160, 5, 3, 'Padova', 'PD', 45.40643500, 11.87676100),
(69, 'Villa panoramica', 'Villa con vista panoramica', 620000.00, 'casa11.jpg', 2, 1, 'disponibile', '2025-04-18 19:27:21', 250, 6, 3, 'Trieste', 'TS', 45.64952600, 13.77681800),
(70, 'Monolocale arredato', 'Monolocale arredato ideale per investimento', 90000.00, 'casa12.jpg', 3, 2, 'disponibile', '2025-04-18 19:27:21', 30, 1, 1, 'Firenze', 'FI', 43.76956200, 11.25581400),
(71, 'Appartamento spazioso', 'Appartamento spazioso e confortevole', 280000.00, 'casa13.jpg', 1, 1, 'disponibile', '2025-04-18 19:27:21', 110, 4, 2, 'Bari', 'BA', 41.11714400, 16.87187100),
(72, 'Villa con terreno', 'Villa indipendente con ampio terreno', 520000.00, 'casa14.jpg', 2, 2, 'disponibile', '2025-04-18 19:27:21', 270, 7, 3, 'Modena', 'MO', 44.64712800, 10.92522700),
(73, 'Monolocale centrale', 'Monolocale in posizione centrale', 95000.00, 'casa15.jpg', 3, 1, 'disponibile', '2025-04-18 19:27:21', 40, 1, 1, 'Ancona', 'AN', 43.61582900, 13.51891500);

-- --------------------------------------------------------

--
-- Struttura della tabella `preferiti`
--

DROP TABLE IF EXISTS `preferiti`;
CREATE TABLE `preferiti` (
  `id` int(11) NOT NULL,
  `id_utente` int(11) NOT NULL,
  `id_immobile` int(11) NOT NULL,
  `data_aggiunta` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `transazioni`
--

DROP TABLE IF EXISTS `transazioni`;
CREATE TABLE `transazioni` (
  `id` int(11) NOT NULL,
  `id_utente` int(11) DEFAULT NULL,
  `id_immobile` int(11) DEFAULT NULL,
  `data_transazione` timestamp NOT NULL DEFAULT current_timestamp(),
  `importo` decimal(10,2) DEFAULT NULL,
  `tipo` enum('acquisto','affitto') DEFAULT 'acquisto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

DROP TABLE IF EXISTS `utenti`;
CREATE TABLE `utenti` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cognome` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `data_registrazione` timestamp NOT NULL DEFAULT current_timestamp(),
  `telefono` varchar(15) DEFAULT NULL,
  `indirizzo` varchar(255) DEFAULT NULL,
  `stato` enum('attivo','disattivo') DEFAULT 'attivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`id`, `nome`, `cognome`, `email`, `password`, `data_registrazione`, `telefono`, `indirizzo`, `stato`) VALUES
(1, 'Mario', 'Rossi', 'mario.rossi@example.com', 'password123', '2025-04-17 08:08:16', '3331234567', 'Via Roma, 10, Milano', 'attivo'),
(2, 'Giulia', 'Verdi', 'giulia.verdi@example.com', 'password456', '2025-04-17 08:08:16', '3339876543', 'Via Garibaldi, 20, Roma', 'attivo'),
(3, 'Jacopo', 'Riccardi', 'jacopo.riccardi006@gmail.com', '$2y$10$drZbgnC96mYteAgPi74md.QMMOMvyzrix9m9quozskWrrORnsrfkm', '2025-04-17 08:19:11', '3518966972', 'Via Fermi 8', 'attivo'),
(4, 'Paolo', 'Merisio', 'mersio@gmail.com', '$2y$10$ogn.m7vh8bnB9B4pPvICD.JNoSkePTQ.89Lh/kLFYVRArhHtWx/3S', '2025-05-06 07:56:52', '325346457476567', NULL, 'attivo');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `acquisti`
--
ALTER TABLE `acquisti`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `agenti_immobiliari`
--
ALTER TABLE `agenti_immobiliari`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indici per le tabelle `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `contatti`
--
ALTER TABLE `contatti`
  ADD PRIMARY KEY (`id`);

--
-- Indici per le tabelle `immobili`
--
ALTER TABLE `immobili`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `agente_id` (`agente_id`);

--
-- Indici per le tabelle `preferiti`
--
ALTER TABLE `preferiti`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `utente_immobile` (`id_utente`,`id_immobile`),
  ADD KEY `fk_preferiti_immobili` (`id_immobile`);

--
-- Indici per le tabelle `transazioni`
--
ALTER TABLE `transazioni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utente` (`id_utente`),
  ADD KEY `id_immobile` (`id_immobile`);

--
-- Indici per le tabelle `utenti`
--
ALTER TABLE `utenti`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `acquisti`
--
ALTER TABLE `acquisti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT per la tabella `agenti_immobiliari`
--
ALTER TABLE `agenti_immobiliari`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT per la tabella `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `contatti`
--
ALTER TABLE `contatti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `immobili`
--
ALTER TABLE `immobili`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT per la tabella `preferiti`
--
ALTER TABLE `preferiti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT per la tabella `transazioni`
--
ALTER TABLE `transazioni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT per la tabella `utenti`
--
ALTER TABLE `utenti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `immobili`
--
ALTER TABLE `immobili`
  ADD CONSTRAINT `immobili_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorie` (`id`),
  ADD CONSTRAINT `immobili_ibfk_2` FOREIGN KEY (`agente_id`) REFERENCES `agenti_immobiliari` (`id`);

--
-- Limiti per la tabella `preferiti`
--
ALTER TABLE `preferiti`
  ADD CONSTRAINT `fk_preferiti_immobili` FOREIGN KEY (`id_immobile`) REFERENCES `immobili` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_preferiti_utenti` FOREIGN KEY (`id_utente`) REFERENCES `utenti` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
