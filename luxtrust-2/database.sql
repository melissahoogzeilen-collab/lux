-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 18, 2026 at 08:57 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `luxtrust`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$QL1NoifFhQefWrgKNCBeDu/4pQlhVRBT0eXEiCLifaaYsu0yLkvyy', '2025-01-19 15:00:08');

-- --------------------------------------------------------

--
-- Table structure for table `allowed_countries`
--

DROP TABLE IF EXISTS `allowed_countries`;
CREATE TABLE IF NOT EXISTS `allowed_countries` (
  `country_code` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`country_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asn_risk_rules`
--

DROP TABLE IF EXISTS `asn_risk_rules`;
CREATE TABLE IF NOT EXISTS `asn_risk_rules` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `asn_number` int UNSIGNED DEFAULT NULL,
  `org_pattern` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `action` enum('score','block','allow') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'score',
  `risk_score` int UNSIGNED NOT NULL DEFAULT '25',
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_asn_rule` (`asn_number`,`org_pattern`),
  KEY `idx_asn_rule_enabled` (`enabled`),
  KEY `idx_asn_rule_number` (`asn_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `behavioral_patterns`
--

DROP TABLE IF EXISTS `behavioral_patterns`;
CREATE TABLE IF NOT EXISTS `behavioral_patterns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `session_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `mouse_velocity_avg` float DEFAULT '0',
  `mouse_acceleration_avg` float DEFAULT '0',
  `page_dwell_time` int DEFAULT '0',
  `human_score` int DEFAULT '50',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_session` (`ip_address`,`session_id`(100))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_ips`
--

DROP TABLE IF EXISTS `blocked_ips`;
CREATE TABLE IF NOT EXISTS `blocked_ips` (
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `blocked_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `block_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_patterns`
--

DROP TABLE IF EXISTS `blocked_patterns`;
CREATE TABLE IF NOT EXISTS `blocked_patterns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pattern_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pattern_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pattern_regex` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `severity` int DEFAULT '5',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type_active` (`pattern_type`,`is_active`),
  KEY `idx_blocked_patterns_active` (`is_active`,`pattern_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_user_agents`
--

DROP TABLE IF EXISTS `blocked_user_agents`;
CREATE TABLE IF NOT EXISTS `blocked_user_agents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pattern` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1300 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blocked_user_agents`
--

INSERT INTO `blocked_user_agents` (`id`, `pattern`, `created_at`) VALUES
(1, 'Macintosh', '2025-09-09 13:22:23'),
(2, 'Googlebot', '2025-09-09 13:22:23'),
(3, 'Bingbot', '2025-09-09 13:22:23'),
(4, 'DuckDuckBot', '2025-09-09 13:22:23'),
(5, 'Baiduspider', '2025-09-09 13:22:23'),
(6, 'YandexBot', '2025-09-09 13:22:23'),
(7, 'Sogou', '2025-09-09 13:22:23'),
(8, 'Exabot', '2025-09-09 13:22:23'),
(9, 'ia_archiver', '2025-09-09 13:22:23'),
(10, 'SemrushBot', '2025-09-09 13:22:23'),
(11, 'Serpstatbot', '2025-09-09 13:22:23'),
(12, 'SEOdiver', '2025-09-09 13:22:23'),
(13, '007ac9', '2025-09-09 13:22:23'),
(14, 'facebookexternalhit', '2025-09-09 13:22:23'),
(15, 'Facebot', '2025-09-09 13:22:23'),
(16, 'Twitterbot', '2025-09-09 13:22:23'),
(17, 'Pinterestbot', '2025-09-09 13:22:23'),
(18, 'LinkedInBot', '2025-09-09 13:22:23'),
(19, '/teoma', '2025-09-09 13:22:23'),
(20, 'AhrefsBot', '2025-09-09 13:22:23'),
(21, 'MJ12bot', '2025-09-09 13:22:23'),
(22, 'DotBot', '2025-09-09 13:22:23'),
(23, 'ZoominfoBot', '2025-09-09 13:22:23'),
(24, 'Scrapy', '2025-09-09 13:22:23'),
(25, 'SiteAnalyzerBot', '2025-09-09 13:22:23'),
(26, 'DataForSeoBot', '2025-09-09 13:22:23'),
(27, 'HTTrack', '2025-09-09 13:22:23'),
(28, 'wget', '2025-09-09 13:22:23'),
(29, 'curl', '2025-09-09 13:22:23'),
(30, 'Go-http-client', '2025-09-09 13:22:23'),
(31, 'python-requests', '2025-09-09 13:22:23'),
(32, 'libwww-perl', '2025-09-09 13:22:23'),
(33, 'LWP::Simple', '2025-09-09 13:22:23'),
(34, 'HttpClient', '2025-09-09 13:22:23'),
(35, 'okhttp', '2025-09-09 13:22:23'),
(36, 'HttpRequest', '2025-09-09 13:22:23'),
(37, 'axios', '2025-09-09 13:22:23'),
(38, 'node-fetch', '2025-09-09 13:22:23'),
(39, 'Java', '2025-09-09 13:22:23'),
(40, 'Jakarta Commons-HttpClient', '2025-09-09 13:22:23'),
(41, 'Apache-HttpClient', '2025-09-09 13:22:23'),
(42, 'aiohttp', '2025-09-09 13:22:23'),
(43, 'ApacheBench', '2025-09-09 13:22:23'),
(44, 'httprobe', '2025-09-09 13:22:23'),
(45, 'urlgrabber', '2025-09-09 13:22:23'),
(46, 'Zyte Smart Proxy', '2025-09-09 13:22:23'),
(47, '50.nu', '2025-09-09 13:22:23'),
(48, 'Nmap', '2025-09-09 13:22:23'),
(49, 'Nikto', '2025-09-09 13:22:23'),
(50, 'sqlmap', '2025-09-09 13:22:23'),
(51, 'wpscan', '2025-09-09 13:22:23'),
(52, 'acunetix', '2025-09-09 13:22:23'),
(53, 'netsparker', '2025-09-09 13:22:23'),
(54, 'AppSpider', '2025-09-09 13:22:23'),
(55, 'WebInspect', '2025-09-09 13:22:23'),
(56, 'OpenVAS', '2025-09-09 13:22:23'),
(57, 'masscan', '2025-09-09 13:22:23'),
(58, 'dirbuster', '2025-09-09 13:22:23'),
(59, 'dirb', '2025-09-09 13:22:23'),
(60, 'BurpSuite', '2025-09-09 13:22:23'),
(61, 'ZAP', '2025-09-09 13:22:23'),
(62, 'owaspzap', '2025-09-09 13:22:23'),
(63, 'F-Scraper', '2025-09-09 13:22:23'),
(64, '4seohuntbot', '2025-09-09 13:22:23'),
(65, 'Tor', '2025-09-09 13:22:23'),
(66, 'Privoxy', '2025-09-09 13:22:23'),
(67, 'socks', '2025-09-09 13:22:23'),
(68, 'ShadowServer', '2025-09-09 13:22:23'),
(69, '360spider', '2025-09-09 13:22:23'),
(70, 'GoogleImageProxy', '2025-09-09 13:22:23'),
(71, 'CocCocbot', '2025-09-09 13:22:23'),
(72, 'Bytespider', '2025-09-09 13:22:23'),
(73, 'ImgBot', '2025-09-09 13:22:23'),
(74, '200pleasebot', '2025-09-09 13:22:23'),
(75, 'ArchiveBot', '2025-09-09 13:22:23'),
(76, 'spbot', '2025-09-09 13:22:23'),
(77, 'BLEXBot', '2025-09-09 13:22:23'),
(78, 'rogerbot', '2025-09-09 13:22:23'),
(79, '2ip', '2025-09-09 13:22:23'),
(80, 'LinkpadBot', '2025-09-09 13:22:23'),
(81, 'CensysInspect', '2025-09-09 13:22:23'),
(82, 'LivelapBot', '2025-09-09 13:22:23'),
(83, '192.comagent', '2025-09-09 13:22:23'),
(84, 'HeadlessChrome', '2025-09-09 13:22:23'),
(85, 'PhantomJS', '2025-09-09 13:22:23'),
(86, 'Puppeteer', '2025-09-09 13:22:23'),
(87, 'selenium', '2025-09-09 13:22:23'),
(88, 'SlimerJS', '2025-09-09 13:22:23'),
(89, 'Playwright', '2025-09-09 13:22:23'),
(90, 'WebDriver', '2025-09-09 13:22:23'),
(91, '008', '2025-09-09 13:22:23'),
(92, 'PetalBot', '2025-09-09 13:22:23'),
(93, 'TelegramBot', '2025-09-09 13:22:23'),
(94, 'Amazon CloudFront', '2025-09-09 13:22:23'),
(95, 'Cloudflare-AlwaysOnline', '2025-09-09 13:22:23'),
(96, 'MSIECrawler', '2025-09-09 13:22:23'),
(97, 'ltx71', '2025-09-09 13:22:23'),
(98, 'Dataprovider', '2025-09-09 13:22:23'),
(99, 'expanseinc.com', '2025-09-09 13:22:23'),
(100, 'python-urllib', '2025-09-09 13:22:23'),
(101, 'Trident', '2025-09-09 13:22:23'),
(102, 'X11', '2025-09-09 13:22:23'),
(103, 'a6-indexer', '2025-09-09 13:22:23'),
(104, 'aboundexbot', '2025-09-09 13:22:23'),
(105, 'aboutusbot', '2025-09-09 13:22:23'),
(106, 'above', '2025-09-09 13:22:23'),
(107, 'abrave spider', '2025-09-09 13:22:23'),
(108, 'accelobot', '2025-09-09 13:22:23'),
(109, 'accoona', '2025-09-09 13:22:23'),
(110, 'acoon', '2025-09-09 13:22:23'),
(111, 'acoonbot', '2025-09-09 13:22:23'),
(112, 'addthis.com', '2025-09-09 13:22:23'),
(113, 'admantx', '2025-09-09 13:22:23'),
(114, 'adressendeutschland', '2025-09-09 13:22:23'),
(115, 'adsbot-google', '2025-09-09 13:22:23'),
(116, 'ah-ha.com', '2025-09-09 13:22:23'),
(117, 'ahoy', '2025-09-09 13:22:23'),
(118, '200pleasebot', '2025-09-09 13:22:23'),
(119, 'alexabot', '2025-09-09 13:22:23'),
(120, 'amagit.com', '2025-09-09 13:22:23'),
(121, 'amazonaws', '2025-09-09 13:22:23'),
(122, 'amznkassocbot', '2025-09-09 13:22:23'),
(123, 'analytics', '2025-09-09 13:22:23'),
(124, 'ananzi', '2025-09-09 13:22:23'),
(125, 'antbot', '2025-09-09 13:22:23'),
(126, 'anthill', '2025-09-09 13:22:23'),
(127, 'apercite', '2025-09-09 13:22:23'),
(128, 'aportworm', '2025-09-09 13:22:23'),
(129, 'appie', '2025-09-09 13:22:23'),
(130, 'arabot', '2025-09-09 13:22:23'),
(131, 'arachnophilia', '2025-09-09 13:22:23'),
(132, 'arale', '2025-09-09 13:22:23'),
(133, 'araneo', '2025-09-09 13:22:23'),
(134, 'aranha', '2025-09-09 13:22:23'),
(135, 'architext', '2025-09-09 13:22:23'),
(136, 'aretha', '2025-09-09 13:22:23'),
(137, 'arks', '2025-09-09 13:22:23'),
(138, 'asterias', '2025-09-09 13:22:23'),
(139, 'atlocal', '2025-09-09 13:22:23'),
(140, 'atn', '2025-09-09 13:22:23'),
(141, 'atomz', '2025-09-09 13:22:23'),
(142, 'augurfind', '2025-09-09 13:22:23'),
(143, 'backrub', '2025-09-09 13:22:23'),
(144, 'bannana_bot', '2025-09-09 13:22:23'),
(145, 'baypup', '2025-09-09 13:22:23'),
(146, 'bdfetch', '2025-09-09 13:22:23'),
(147, 'big brother', '2025-09-09 13:22:23'),
(148, 'biglotron', '2025-09-09 13:22:23'),
(149, 'bjaaland', '2025-09-09 13:22:23'),
(150, 'blackwidow', '2025-09-09 13:22:23'),
(151, 'blaiz', '2025-09-09 13:22:23'),
(152, 'blo.', '2025-09-09 13:22:23'),
(153, 'blog', '2025-09-09 13:22:23'),
(154, 'bloodhound', '2025-09-09 13:22:23'),
(155, 'boitho', '2025-09-09 13:22:23'),
(156, 'booch', '2025-09-09 13:22:23'),
(157, 'bot', '2025-09-09 13:22:23'),
(158, 'bradley', '2025-09-09 13:22:23'),
(159, 'butterfly', '2025-09-09 13:22:23'),
(160, 'calif', '2025-09-09 13:22:23'),
(161, 'calyxinstitute', '2025-09-09 13:22:23'),
(162, 'cassandra', '2025-09-09 13:22:23'),
(163, 'ccubee', '2025-09-09 13:22:23'),
(164, 'cfetch', '2025-09-09 13:22:23'),
(165, 'charlotte', '2025-09-09 13:22:23'),
(166, 'churl', '2025-09-09 13:22:23'),
(167, 'cienciaficcion', '2025-09-09 13:22:23'),
(168, 'cmc', '2025-09-09 13:22:23'),
(169, 'collective', '2025-09-09 13:22:23'),
(170, 'comagent', '2025-09-09 13:22:23'),
(171, 'combine', '2025-09-09 13:22:23'),
(172, 'compatible', '2025-09-09 13:22:23'),
(173, 'computingsite', '2025-09-09 13:22:23'),
(174, 'crawl', '2025-09-09 13:22:23'),
(175, 'crawler', '2025-09-09 13:22:23'),
(176, 'csci', '2025-09-09 13:22:23'),
(177, '192.comagent', '2025-09-09 13:22:23'),
(178, 'cusco', '2025-09-09 13:22:23'),
(179, 'cyveillance', '2025-09-09 13:22:23'),
(180, 'daumoa', '2025-09-09 13:22:23'),
(181, 'deepindex', '2025-09-09 13:22:23'),
(182, 'delorie', '2025-09-09 13:22:23'),
(183, 'depspid', '2025-09-09 13:22:23'),
(184, 'deweb', '2025-09-09 13:22:23'),
(185, 'die blinde kuh', '2025-09-09 13:22:23'),
(186, 'digger', '2025-09-09 13:22:23'),
(187, 'ditto', '2025-09-09 13:22:23'),
(188, 'dmoz', '2025-09-09 13:22:23'),
(189, 'docomo', '2025-09-09 13:22:23'),
(190, 'download express', '2025-09-09 13:22:23'),
(191, 'dreamhost', '2025-09-09 13:22:23'),
(192, 'dtaagent', '2025-09-09 13:22:23'),
(193, 'dwcp', '2025-09-09 13:22:23'),
(194, 'e-collector', '2025-09-09 13:22:23'),
(195, 'ebiness', '2025-09-09 13:22:23'),
(196, 'ebingbong', '2025-09-09 13:22:23'),
(197, 'ejupiter', '2025-09-09 13:22:23'),
(198, 'emacs-w3 search engine', '2025-09-09 13:22:23'),
(199, 'esther', '2025-09-09 13:22:23'),
(200, 'evliya celebi', '2025-09-09 13:22:23'),
(201, 'ezresult', '2025-09-09 13:22:23'),
(202, 'facebook', '2025-09-09 13:22:23'),
(203, 'falcon', '2025-09-09 13:22:23'),
(204, 'felix ide', '2025-09-09 13:22:23'),
(205, 'ferret', '2025-09-09 13:22:23'),
(206, 'fetchrover', '2025-09-09 13:22:23'),
(207, 'fido', '2025-09-09 13:22:23'),
(208, 'findlinks', '2025-09-09 13:22:23'),
(209, 'fireball', '2025-09-09 13:22:23'),
(210, 'fish search', '2025-09-09 13:22:23'),
(211, 'fouineur', '2025-09-09 13:22:23'),
(212, 'funnelweb', '2025-09-09 13:22:23'),
(213, 'gazz', '2025-09-09 13:22:23'),
(214, 'gcreep', '2025-09-09 13:22:23'),
(215, 'genieknows', '2025-09-09 13:22:23'),
(216, 'getterroboplus', '2025-09-09 13:22:23'),
(217, 'geturl', '2025-09-09 13:22:23'),
(218, 'glx', '2025-09-09 13:22:23'),
(219, 'goforit', '2025-09-09 13:22:23'),
(220, 'golem', '2025-09-09 13:22:23'),
(221, 'google', '2025-09-09 13:22:23'),
(222, 'grabber', '2025-09-09 13:22:23'),
(223, 'gralon', '2025-09-09 13:22:23'),
(224, 'grapnel', '2025-09-09 13:22:23'),
(225, 'griffon', '2025-09-09 13:22:23'),
(226, 'gromit', '2025-09-09 13:22:23'),
(227, 'grub', '2025-09-09 13:22:23'),
(228, 'gulliver', '2025-09-09 13:22:23'),
(229, 'hamahakki', '2025-09-09 13:22:23'),
(230, 'harvest', '2025-09-09 13:22:23'),
(231, 'havindex', '2025-09-09 13:22:23'),
(232, 'helix', '2025-09-09 13:22:23'),
(233, 'heritrix', '2025-09-09 13:22:23'),
(234, 'hku www octopus', '2025-09-09 13:22:23'),
(235, 'homerweb', '2025-09-09 13:22:23'),
(236, 'htdig', '2025-09-09 13:22:23'),
(237, 'html index', '2025-09-09 13:22:23'),
(238, 'html_analyzer', '2025-09-09 13:22:23'),
(239, 'htmlgobble', '2025-09-09 13:22:23'),
(240, 'hubater', '2025-09-09 13:22:23'),
(241, 'hyper-decontextualizer', '2025-09-09 13:22:23'),
(242, '008', '2025-09-09 13:22:23'),
(243, 'ibm_planetwide', '2025-09-09 13:22:23'),
(244, 'ichiro', '2025-09-09 13:22:23'),
(245, 'iconsurf', '2025-09-09 13:22:23'),
(246, 'iltrovatore', '2025-09-09 13:22:23'),
(247, 'image.kapsi.net', '2025-09-09 13:22:23'),
(248, 'imagelock', '2025-09-09 13:22:23'),
(249, 'incywincy', '2025-09-09 13:22:23'),
(250, 'indexer', '2025-09-09 13:22:23'),
(251, 'infobee', '2025-09-09 13:22:23'),
(252, 'informant', '2025-09-09 13:22:23'),
(253, 'ingrid', '2025-09-09 13:22:23'),
(254, 'inktomisearch.com', '2025-09-09 13:22:23'),
(255, 'inspector web', '2025-09-09 13:22:23'),
(256, 'intelliagent', '2025-09-09 13:22:23'),
(257, 'internet shinchakubin', '2025-09-09 13:22:23'),
(258, 'ip3000', '2025-09-09 13:22:23'),
(259, 'iron33', '2025-09-09 13:22:23'),
(260, 'israeli-search', '2025-09-09 13:22:23'),
(261, 'ivia', '2025-09-09 13:22:23'),
(262, 'jack', '2025-09-09 13:22:23'),
(263, 'jakarta', '2025-09-09 13:22:23'),
(264, 'javabee', '2025-09-09 13:22:23'),
(265, 'jetbot', '2025-09-09 13:22:23'),
(266, 'jetbrains', '2025-09-09 13:22:23'),
(267, 'jumpstation', '2025-09-09 13:22:23'),
(268, 'katipo', '2025-09-09 13:22:23'),
(269, 'kdd-explorer', '2025-09-09 13:22:23'),
(270, 'kilroy', '2025-09-09 13:22:23'),
(271, 'knowledge', '2025-09-09 13:22:23'),
(272, 'kototoi', '2025-09-09 13:22:23'),
(273, 'kretrieve', '2025-09-09 13:22:23'),
(274, 'labelgrabber', '2025-09-09 13:22:23'),
(275, 'lachesis', '2025-09-09 13:22:23'),
(276, 'larbin', '2025-09-09 13:22:23'),
(277, 'legs', '2025-09-09 13:22:23'),
(278, 'libwww', '2025-09-09 13:22:23'),
(279, 'link validator', '2025-09-09 13:22:23'),
(280, 'linkalarm', '2025-09-09 13:22:23'),
(281, 'linkscan', '2025-09-09 13:22:23'),
(282, 'lockon', '2025-09-09 13:22:23'),
(283, 'lssrocketcrawler', '2025-09-09 13:22:23'),
(284, 'lwp', '2025-09-09 13:22:23'),
(285, 'lycos', '2025-09-09 13:22:23'),
(286, 'magpie', '2025-09-09 13:22:23'),
(287, 'mantraagent', '2025-09-09 13:22:23'),
(288, 'mapoftheinternet', '2025-09-09 13:22:23'),
(289, 'marvin/', '2025-09-09 13:22:23'),
(290, 'mattie', '2025-09-09 13:22:23'),
(291, 'mediafox', '2025-09-09 13:22:23'),
(292, 'mediapartners', '2025-09-09 13:22:23'),
(293, 'mercator', '2025-09-09 13:22:23'),
(294, 'merzscope', '2025-09-09 13:22:23'),
(295, 'microsoft url control', '2025-09-09 13:22:23'),
(296, 'minirank', '2025-09-09 13:22:23'),
(297, 'miva', '2025-09-09 13:22:23'),
(298, 'mj12', '2025-09-09 13:22:23'),
(299, 'mnogosearch', '2025-09-09 13:22:23'),
(300, 'moget', '2025-09-09 13:22:23'),
(301, 'monster', '2025-09-09 13:22:23'),
(302, 'moose', '2025-09-09 13:22:23'),
(303, 'motor', '2025-09-09 13:22:23'),
(304, 'multitext', '2025-09-09 13:22:23'),
(305, 'muncher', '2025-09-09 13:22:23'),
(306, 'muscatferret', '2025-09-09 13:22:23'),
(307, 'mwd.search', '2025-09-09 13:22:23'),
(308, 'myweb', '2025-09-09 13:22:23'),
(309, 'najdi', '2025-09-09 13:22:23'),
(310, 'nameprotect', '2025-09-09 13:22:23'),
(311, 'nationaldirectory', '2025-09-09 13:22:23'),
(312, 'nazilla', '2025-09-09 13:22:23'),
(313, 'ncsa beta', '2025-09-09 13:22:23'),
(314, 'nec-meshexplorer', '2025-09-09 13:22:23'),
(315, 'nederland.zoek', '2025-09-09 13:22:23'),
(316, 'netcarta webmap engine', '2025-09-09 13:22:23'),
(317, 'netmechanic', '2025-09-09 13:22:23'),
(318, 'netpilot', '2025-09-09 13:22:23'),
(319, 'netresearchserver', '2025-09-09 13:22:23'),
(320, 'netscoop', '2025-09-09 13:22:23'),
(321, '007ac9', '2025-09-09 13:22:23'),
(322, 'newscan-online', '2025-09-09 13:22:23'),
(323, 'nhse', '2025-09-09 13:22:23'),
(324, 'nokia6682/', '2025-09-09 13:22:23'),
(325, 'nomad', '2025-09-09 13:22:23'),
(326, 'noyona', '2025-09-09 13:22:23'),
(327, 'nutch', '2025-09-09 13:22:23'),
(328, 'nzexplorer', '2025-09-09 13:22:23'),
(329, 'objectssearch', '2025-09-09 13:22:23'),
(330, 'occam', '2025-09-09 13:22:23'),
(331, 'omni', '2025-09-09 13:22:23'),
(332, 'open text', '2025-09-09 13:22:23'),
(333, 'openfind', '2025-09-09 13:22:23'),
(334, 'openintelligencedata', '2025-09-09 13:22:23'),
(335, 'orb search', '2025-09-09 13:22:23'),
(336, 'osis-project', '2025-09-09 13:22:23'),
(337, 'pack rat', '2025-09-09 13:22:23'),
(338, 'page_verifier', '2025-09-09 13:22:23'),
(339, 'pageboy', '2025-09-09 13:22:23'),
(340, 'pagebull', '2025-09-09 13:22:23'),
(341, 'panscient', '2025-09-09 13:22:23'),
(342, 'parasite', '2025-09-09 13:22:23'),
(343, 'partnersite', '2025-09-09 13:22:23'),
(344, 'patric', '2025-09-09 13:22:23'),
(345, 'pear.', '2025-09-09 13:22:23'),
(346, 'pegasus', '2025-09-09 13:22:23'),
(347, 'peregrinator', '2025-09-09 13:22:23'),
(348, 'pgp key agent', '2025-09-09 13:22:23'),
(349, 'phantom', '2025-09-09 13:22:23'),
(350, 'phishtank', '2025-09-09 13:22:23'),
(351, 'phpdig', '2025-09-09 13:22:23'),
(352, 'picosearch', '2025-09-09 13:22:23'),
(353, 'piltdownman', '2025-09-09 13:22:23'),
(354, 'pimptrain', '2025-09-09 13:22:23'),
(355, 'pinpoint', '2025-09-09 13:22:23'),
(356, 'pioneer', '2025-09-09 13:22:23'),
(357, 'piranha', '2025-09-09 13:22:23'),
(358, 'plumtreewebaccessor', '2025-09-09 13:22:23'),
(359, 'pogodak', '2025-09-09 13:22:23'),
(360, 'poirot', '2025-09-09 13:22:23'),
(361, 'pompos', '2025-09-09 13:22:23'),
(362, 'poppelsdorf', '2025-09-09 13:22:23'),
(363, 'poppi', '2025-09-09 13:22:23'),
(364, 'popular iconoclast', '2025-09-09 13:22:23'),
(365, 'psycheclone', '2025-09-09 13:22:23'),
(366, 'publisher', '2025-09-09 13:22:23'),
(367, 'python', '2025-09-09 13:22:23'),
(368, 'rambler', '2025-09-09 13:22:23'),
(369, 'raven search', '2025-09-09 13:22:23'),
(370, 'roach', '2025-09-09 13:22:23'),
(371, 'road runner', '2025-09-09 13:22:23'),
(372, 'roadhouse', '2025-09-09 13:22:23'),
(373, 'robbie', '2025-09-09 13:22:23'),
(374, 'robofox', '2025-09-09 13:22:23'),
(375, 'robozilla', '2025-09-09 13:22:23'),
(376, 'rules', '2025-09-09 13:22:23'),
(377, 'salty', '2025-09-09 13:22:23'),
(378, 'sbider', '2025-09-09 13:22:23'),
(379, 'scooter', '2025-09-09 13:22:23'),
(380, 'scoutjet', '2025-09-09 13:22:23'),
(381, 'scrubby', '2025-09-09 13:22:23'),
(382, 'search.', '2025-09-09 13:22:23'),
(383, 'searchprocess', '2025-09-09 13:22:23'),
(384, 'seek', '2025-09-09 13:22:23'),
(385, 'semanticdiscovery', '2025-09-09 13:22:23'),
(386, 'senrigan', '2025-09-09 13:22:23'),
(387, 'sg-scout', '2025-09-09 13:22:23'),
(388, 'shai\\\'hulud', '2025-09-09 13:22:23'),
(389, 'shark', '2025-09-09 13:22:23'),
(390, 'shopwiki', '2025-09-09 13:22:23'),
(391, 'sidewinder', '2025-09-09 13:22:23'),
(392, 'sift', '2025-09-09 13:22:23'),
(393, 'silk', '2025-09-09 13:22:23'),
(394, 'simmany', '2025-09-09 13:22:23'),
(395, 'site searcher', '2025-09-09 13:22:23'),
(396, 'site valet', '2025-09-09 13:22:23'),
(397, 'sitetech-rover', '2025-09-09 13:22:23'),
(398, 'skymob.com', '2025-09-09 13:22:23'),
(399, 'sleek', '2025-09-09 13:22:23'),
(400, 'slurp', '2025-09-09 13:22:23'),
(401, 'smartwit', '2025-09-09 13:22:23'),
(402, 'sna-', '2025-09-09 13:22:23'),
(403, 'snappy', '2025-09-09 13:22:23'),
(404, 'snooper', '2025-09-09 13:22:23'),
(405, 'softlayer', '2025-09-09 13:22:23'),
(406, 'sohu', '2025-09-09 13:22:23'),
(407, 'spam', '2025-09-09 13:22:23'),
(408, 'speedfind', '2025-09-09 13:22:23'),
(409, 'sphere', '2025-09-09 13:22:23'),
(410, 'sphider', '2025-09-09 13:22:23'),
(411, 'spider', '2025-09-09 13:22:23'),
(412, 'spinner', '2025-09-09 13:22:23'),
(413, 'spyder', '2025-09-09 13:22:23'),
(414, 'steeler/', '2025-09-09 13:22:23'),
(415, 'suke', '2025-09-09 13:22:23'),
(416, 'suntek', '2025-09-09 13:22:23'),
(417, 'supersnooper', '2025-09-09 13:22:23'),
(418, 'surfnomore', '2025-09-09 13:22:23'),
(419, 'sven', '2025-09-09 13:22:23'),
(420, 'sygol', '2025-09-09 13:22:23'),
(421, 'szukacz', '2025-09-09 13:22:23'),
(422, 't-h-u-n-d-e-r-s-t-o-n-e', '2025-09-09 13:22:23'),
(423, 't-rex', '2025-09-09 13:22:23'),
(424, 'tach black widow', '2025-09-09 13:22:23'),
(425, 'tarantula', '2025-09-09 13:22:23'),
(426, 'templeton', '2025-09-09 13:22:23'),
(427, 'theophrastus', '2025-09-09 13:22:23'),
(428, 'titan', '2025-09-09 13:22:23'),
(429, 'titin', '2025-09-09 13:22:23'),
(430, 'tkwww', '2025-09-09 13:22:23'),
(431, 'tor-exit', '2025-09-09 13:22:23'),
(432, 'toutatis', '2025-09-09 13:22:23'),
(433, 'tutorgig', '2025-09-09 13:22:23'),
(434, 'twiceler', '2025-09-09 13:22:23'),
(435, 'twisted', '2025-09-09 13:22:23'),
(436, 'ucsd', '2025-09-09 13:22:23'),
(437, 'udmsearch', '2025-09-09 13:22:23'),
(438, 'updated', '2025-09-09 13:22:23'),
(439, 'url check', '2025-09-09 13:22:23'),
(440, 'urlredirectresolver', '2025-09-09 13:22:23'),
(441, 'vagabondo', '2025-09-09 13:22:23'),
(442, 'valkyrie', '2025-09-09 13:22:23'),
(443, 'verticrawl', '2025-09-09 13:22:23'),
(444, 'victoria', '2025-09-09 13:22:23'),
(445, 'vision-search', '2025-09-09 13:22:23'),
(446, 'volcano', '2025-09-09 13:22:23'),
(447, 'voyager-hc', '2025-09-09 13:22:23'),
(448, 'voyager/', '2025-09-09 13:22:23'),
(449, 'w3c_validator', '2025-09-09 13:22:23'),
(450, 'w3m2', '2025-09-09 13:22:23'),
(451, 'w3mir', '2025-09-09 13:22:23'),
(452, 'walker', '2025-09-09 13:22:23'),
(453, 'wallpaper', '2025-09-09 13:22:23'),
(454, 'wanderer', '2025-09-09 13:22:23'),
(455, 'wauuu', '2025-09-09 13:22:23'),
(456, 'wavefire', '2025-09-09 13:22:23'),
(457, 'web core', '2025-09-09 13:22:23'),
(458, 'web hopper', '2025-09-09 13:22:23'),
(459, 'web wombat', '2025-09-09 13:22:23'),
(460, 'webbandit', '2025-09-09 13:22:23'),
(461, 'webcatcher', '2025-09-09 13:22:23'),
(462, 'webcopy', '2025-09-09 13:22:23'),
(463, 'webfoot', '2025-09-09 13:22:23'),
(464, 'weblayers', '2025-09-09 13:22:23'),
(465, 'weblinker', '2025-09-09 13:22:23'),
(466, 'weblog monitor', '2025-09-09 13:22:23'),
(467, 'webmirror', '2025-09-09 13:22:23'),
(468, 'webmonkey', '2025-09-09 13:22:23'),
(469, 'webquest', '2025-09-09 13:22:23'),
(470, 'webreaper', '2025-09-09 13:22:23'),
(471, 'websitepulse', '2025-09-09 13:22:23'),
(472, 'websnarf', '2025-09-09 13:22:23'),
(473, 'webstolperer', '2025-09-09 13:22:23'),
(474, 'webvac', '2025-09-09 13:22:23'),
(475, 'webwalk', '2025-09-09 13:22:23'),
(476, 'webwatch', '2025-09-09 13:22:23'),
(477, 'webwombat', '2025-09-09 13:22:23'),
(478, 'webzinger', '2025-09-09 13:22:23'),
(479, '/teoma', '2025-09-09 13:22:23'),
(480, 'whizbang', '2025-09-09 13:22:23'),
(481, 'whowhere', '2025-09-09 13:22:23'),
(482, 'wild ferret', '2025-09-09 13:22:23'),
(483, 'windows 95', '2025-09-09 13:22:23'),
(484, 'windows 98', '2025-09-09 13:22:23'),
(485, 'worldlight', '2025-09-09 13:22:23'),
(486, 'wwwc', '2025-09-09 13:22:23'),
(487, 'wwwster', '2025-09-09 13:22:23'),
(488, 'xenu', '2025-09-09 13:22:23'),
(489, 'xift', '2025-09-09 13:22:23'),
(490, 'xirq', '2025-09-09 13:22:23'),
(491, 'yahoo!', '2025-09-09 13:22:23'),
(492, 'yandex', '2025-09-09 13:22:23'),
(493, 'yanga', '2025-09-09 13:22:23'),
(494, 'yeti', '2025-09-09 13:22:23'),
(495, '360Spider', '2025-09-09 13:22:23'),
(496, '360spider', '2025-09-09 13:22:23'),
(497, '4seohuntbot', '2025-09-09 13:22:23'),
(498, '50.nu', '2025-09-09 13:22:23'),
(499, 'ADMantX', '2025-09-09 13:22:23'),
(500, 'ADmantXServiceFetcher', '2025-09-09 13:22:23'),
(501, 'Abonti', '2025-09-09 13:22:23'),
(502, 'Aboundexbot', '2025-09-09 13:22:23'),
(503, 'Acoon', '2025-09-09 13:22:23'),
(504, 'AdAuth', '2025-09-09 13:22:23'),
(505, 'Adbeat', '2025-09-09 13:22:23'),
(506, 'AddThis.com', '2025-09-09 13:22:23'),
(507, 'Adsbot', '2025-09-09 13:22:23'),
(508, 'AhrefsSiteAudit', '2025-09-09 13:22:23'),
(509, 'AlexaCrawler', '2025-09-09 13:22:23'),
(510, 'AlexaSiteAudit', '2025-09-09 13:22:23'),
(511, 'AllloadinFaviconBot', '2025-09-09 13:22:23'),
(512, 'AmazonBot', '2025-09-09 13:22:23'),
(513, 'AmazonELB', '2025-09-09 13:22:23'),
(514, 'AmazonRoute53HealthCheck', '2025-09-09 13:22:23'),
(515, 'AnalyticsSEOCrawler', '2025-09-09 13:22:23'),
(516, 'ApacheBench', '2025-09-09 13:22:23'),
(517, 'AppSignalBot', '2025-09-09 13:22:23'),
(518, 'Applebot', '2025-09-09 13:22:23'),
(519, 'Arachni', '2025-09-09 13:22:23'),
(520, 'ArchiveBox', '2025-09-09 13:22:23'),
(521, 'Asana', '2025-09-09 13:22:23'),
(522, 'AskJeeves', '2025-09-09 13:22:23'),
(523, 'AspiegelBot', '2025-09-09 13:22:23'),
(524, 'BDCbot', '2025-09-09 13:22:23'),
(525, 'BLEXBotCrawler', '2025-09-09 13:22:23'),
(526, 'BUbiNG', '2025-09-09 13:22:23'),
(527, 'Backlink-Check.de', '2025-09-09 13:22:23'),
(528, 'BacklinkCrawler', '2025-09-09 13:22:23'),
(529, 'BaiduSpider', '2025-09-09 13:22:23'),
(530, 'Barkrowler', '2025-09-09 13:22:23'),
(531, 'BetterUptimeBot', '2025-09-09 13:22:23'),
(532, 'Bing', '2025-09-09 13:22:23'),
(533, 'BingBot', '2025-09-09 13:22:23'),
(534, 'Birdcrawlerbot', '2025-09-09 13:22:23'),
(535, 'BitlyBot', '2025-09-09 13:22:23'),
(536, 'Blekkobot', '2025-09-09 13:22:23'),
(537, 'Bloglovin', '2025-09-09 13:22:23'),
(538, 'Blogtrottr', '2025-09-09 13:22:23'),
(539, 'BoardReaderBlogIndexer', '2025-09-09 13:22:23'),
(540, 'BrandVerity', '2025-09-09 13:22:23'),
(541, 'Browsershots', '2025-09-09 13:22:23'),
(542, 'Buck', '2025-09-09 13:22:23'),
(543, 'BuiltWith', '2025-09-09 13:22:23'),
(544, 'Bytespider', '2025-09-09 13:22:23'),
(545, 'CATExplorador', '2025-09-09 13:22:23'),
(546, 'CISPAWebAnalyzer', '2025-09-09 13:22:23'),
(547, 'COMODODCV', '2025-09-09 13:22:23'),
(548, 'Castro2', '2025-09-09 13:22:23'),
(549, 'Catchpoint', '2025-09-09 13:22:23'),
(550, 'CensysInspect', '2025-09-09 13:22:23'),
(551, 'Charlotte', '2025-09-09 13:22:23'),
(552, 'ChromePrivacyPreservingPrefetchProxy', '2025-09-09 13:22:23'),
(553, 'Cincraw', '2025-09-09 13:22:23'),
(554, 'Cliqzbot', '2025-09-09 13:22:23'),
(555, 'CloudFlareAMPFetcher', '2025-09-09 13:22:23'),
(556, 'CloudFlareAlwaysOnline', '2025-09-09 13:22:23'),
(557, 'CloudflareDiagnostics', '2025-09-09 13:22:23'),
(558, 'Cocolyzebot', '2025-09-09 13:22:23'),
(559, 'Collectd', '2025-09-09 13:22:23'),
(560, 'CommaFeed', '2025-09-09 13:22:23'),
(561, 'Comscore', '2025-09-09 13:22:23'),
(562, 'ContentKing', '2025-09-09 13:22:23'),
(563, 'Cookiebot', '2025-09-09 13:22:23'),
(564, 'Crawldad', '2025-09-09 13:22:23'),
(565, 'CriteoBot', '2025-09-09 13:22:23'),
(566, 'CrowdTangle', '2025-09-09 13:22:23'),
(567, 'CốcCốcBot', '2025-09-09 13:22:23'),
(568, 'DataForSeoBot', '2025-09-09 13:22:23'),
(569, 'DatadogAgent', '2025-09-09 13:22:23'),
(570, 'Datanyze', '2025-09-09 13:22:23'),
(571, 'Dataprovider', '2025-09-09 13:22:23'),
(572, 'Daum', '2025-09-09 13:22:23'),
(573, 'Diffbot', '2025-09-09 13:22:23'),
(574, 'DiscordBot', '2025-09-09 13:22:23'),
(575, 'Disqus', '2025-09-09 13:22:23'),
(576, 'DomainAppender', '2025-09-09 13:22:23'),
(577, 'DomainCrawler', '2025-09-09 13:22:23'),
(578, 'DomainStatsBot', '2025-09-09 13:22:23'),
(579, 'DomainsProject', '2025-09-09 13:22:23'),
(580, 'DotBot', '2025-09-09 13:22:23'),
(581, 'DotcomMonitor', '2025-09-09 13:22:23'),
(582, 'Dr.Web', '2025-09-09 13:22:23'),
(583, 'DuckDuckBot', '2025-09-09 13:22:23'),
(584, 'DuckDuckGoBot', '2025-09-09 13:22:23'),
(585, 'EMailExractor', '2025-09-09 13:22:23'),
(586, 'EasouSpider', '2025-09-09 13:22:23'),
(587, 'EmailWolf', '2025-09-09 13:22:23'),
(588, 'Embedly', '2025-09-09 13:22:23'),
(589, 'Everyfeed', '2025-09-09 13:22:23'),
(590, 'ExaBot', '2025-09-09 13:22:23'),
(591, 'ExactSeekCrawler', '2025-09-09 13:22:23'),
(592, 'Exchangecheck', '2025-09-09 13:22:23'),
(593, 'FacebookExternalHit', '2025-09-09 13:22:23'),
(594, 'Faveeo', '2025-09-09 13:22:23'),
(595, 'FeedBurner', '2025-09-09 13:22:23'),
(596, 'Feedbin', '2025-09-09 13:22:23'),
(597, 'Feedly', '2025-09-09 13:22:23'),
(598, 'Feedspot', '2025-09-09 13:22:23'),
(599, 'Fever', '2025-09-09 13:22:23'),
(600, 'Flipboard', '2025-09-09 13:22:23'),
(601, 'FreeWebMonitoring', '2025-09-09 13:22:23'),
(602, 'FreshRSS', '2025-09-09 13:22:23'),
(603, 'GDNP', '2025-09-09 13:22:23'),
(604, 'GTmetrix', '2025-09-09 13:22:23'),
(605, 'GenericBot', '2025-09-09 13:22:23'),
(606, 'GenieoWebfilter', '2025-09-09 13:22:23'),
(607, 'Gigablast', '2025-09-09 13:22:23'),
(608, 'Gigabot', '2025-09-09 13:22:23'),
(609, 'GlutenFreeCrawler', '2025-09-09 13:22:23'),
(610, 'GmailImageProxy', '2025-09-09 13:22:23'),
(611, 'Gobuster', '2025-09-09 13:22:23'),
(612, 'Goo', '2025-09-09 13:22:23'),
(613, 'GoogleFavicon', '2025-09-09 13:22:23'),
(614, 'GooglePageSpeedInsights', '2025-09-09 13:22:23'),
(615, 'GooglePartnerMonitoring', '2025-09-09 13:22:23'),
(616, 'GoogleSearchConsole', '2025-09-09 13:22:23'),
(617, 'GoogleStackdriverMonitoring', '2025-09-09 13:22:23'),
(618, 'GoogleStoreBot', '2025-09-09 13:22:23'),
(619, 'GoogleStructuredDataTestingTool', '2025-09-09 13:22:23'),
(620, 'Googlebot', '2025-09-09 13:22:23'),
(621, 'Gowikibot', '2025-09-09 13:22:23'),
(622, 'Grammarly', '2025-09-09 13:22:23'),
(623, 'Grapeshot', '2025-09-09 13:22:23'),
(624, 'Gregarius', '2025-09-09 13:22:23'),
(625, 'HTTPMon', '2025-09-09 13:22:23'),
(626, 'HatenaFavicon', '2025-09-09 13:22:23'),
(627, 'Headline', '2025-09-09 13:22:23'),
(628, 'HeartRailsCapture', '2025-09-09 13:22:23'),
(629, 'Heritrix', '2025-09-09 13:22:23'),
(630, 'HeurekaFeed', '2025-09-09 13:22:23'),
(631, 'HuaweiWebCatBot', '2025-09-09 13:22:23'),
(632, 'HubPages', '2025-09-09 13:22:23'),
(633, 'HubSpot', '2025-09-09 13:22:23'),
(634, 'ICC-Crawler', '2025-09-09 13:22:23'),
(635, 'IDG/IT', '2025-09-09 13:22:23'),
(636, 'IISSiteAnalysis', '2025-09-09 13:22:23'),
(637, 'ING', '2025-09-09 13:22:23'),
(638, 'IONOSCrawler', '2025-09-09 13:22:23'),
(639, 'IP-GuideCrawler', '2025-09-09 13:22:23'),
(640, 'IPIP', '2025-09-09 13:22:23'),
(641, 'IPSAgent', '2025-09-09 13:22:23'),
(642, 'Iframely', '2025-09-09 13:22:23'),
(643, 'InetdexBot', '2025-09-09 13:22:23'),
(644, 'InfoTigerBot', '2025-09-09 13:22:23'),
(645, 'InktomiSlurp', '2025-09-09 13:22:23'),
(646, 'InternetMeasurement', '2025-09-09 13:22:23'),
(647, 'JobboerseBot', '2025-09-09 13:22:23'),
(648, 'K6', '2025-09-09 13:22:23'),
(649, 'KlarnaBot', '2025-09-09 13:22:23'),
(650, 'KomodiaBot', '2025-09-09 13:22:23'),
(651, 'Kouio', '2025-09-09 13:22:23'),
(652, 'Kozmonavt', '2025-09-09 13:22:23'),
(653, 'LCC', '2025-09-09 13:22:23'),
(654, 'LTX71', '2025-09-09 13:22:23'),
(655, 'Lighthouse', '2025-09-09 13:22:23'),
(656, 'Linespider', '2025-09-09 13:22:23'),
(657, 'LinkPreview', '2025-09-09 13:22:23'),
(658, 'LinkWalker', '2025-09-09 13:22:23'),
(659, 'LinkdexBot', '2025-09-09 13:22:23'),
(660, 'LinkedInBot', '2025-09-09 13:22:23'),
(661, 'LinkpadBot', '2025-09-09 13:22:23'),
(662, 'MJ12Bot', '2025-09-09 13:22:23'),
(663, 'MaCoCu', '2025-09-09 13:22:23'),
(664, 'Magpie-Crawler', '2025-09-09 13:22:23'),
(665, 'MagpieRSS', '2025-09-09 13:22:23'),
(666, 'Mail.RuBot', '2025-09-09 13:22:23'),
(667, 'MastodonBot', '2025-09-09 13:22:23'),
(668, 'MeanpathBot', '2025-09-09 13:22:23'),
(669, 'MegaIndex', '2025-09-09 13:22:23'),
(670, 'Meta', '2025-09-09 13:22:23'),
(671, 'MetaInspector', '2025-09-09 13:22:23'),
(672, 'MetaJobBot', '2025-09-09 13:22:23'),
(673, 'MicroAdBot', '2025-09-09 13:22:23'),
(674, 'Mnogosearch', '2025-09-09 13:22:23'),
(675, 'MojeekBot', '2025-09-09 13:22:23'),
(676, 'Monitor.Us', '2025-09-09 13:22:23'),
(677, 'Munin', '2025-09-09 13:22:23'),
(678, 'MuscatFerret', '2025-09-09 13:22:23'),
(679, 'NETZZAPPEN', '2025-09-09 13:22:23'),
(680, 'NLCrawler', '2025-09-09 13:22:23'),
(681, 'Nagioscheck_http', '2025-09-09 13:22:23'),
(682, 'NalezenCzBot', '2025-09-09 13:22:23'),
(683, 'Neevabot', '2025-09-09 13:22:23'),
(684, 'NetLyzerFastProbe', '2025-09-09 13:22:23'),
(685, 'NetSystemsResearch', '2025-09-09 13:22:23'),
(686, 'NetcraftSurveyBot', '2025-09-09 13:22:23'),
(687, 'Netvibes', '2025-09-09 13:22:23'),
(688, 'NewsBlur', '2025-09-09 13:22:23'),
(689, 'NewsGator', '2025-09-09 13:22:23'),
(690, 'Newslitbot', '2025-09-09 13:22:23'),
(691, 'NiceCrawler', '2025-09-09 13:22:23'),
(692, 'NimbostratusBot', '2025-09-09 13:22:23'),
(693, 'Nmap', '2025-09-09 13:22:23'),
(694, 'NotifyNinja', '2025-09-09 13:22:23'),
(695, 'Nutch-basedBot', '2025-09-09 13:22:23'),
(696, 'Nuzzel', '2025-09-09 13:22:23'),
(697, 'Octopus', '2025-09-09 13:22:23'),
(698, 'Omgilibot', '2025-09-09 13:22:23'),
(699, 'Onalytica', '2025-09-09 13:22:23'),
(700, 'OpenLinkProfiler', '2025-09-09 13:22:23'),
(701, 'OpenWebSpider', '2025-09-09 13:22:23'),
(702, 'OpenindexSpider', '2025-09-09 13:22:23'),
(703, 'OrangeBot', '2025-09-09 13:22:23'),
(704, 'Outbrain', '2025-09-09 13:22:23'),
(705, 'PATHspider', '2025-09-09 13:22:23'),
(706, 'PRTGNetworkMonitor', '2025-09-09 13:22:23'),
(707, 'PageModifiedPinger', '2025-09-09 13:22:23'),
(708, 'PagePeeker', '2025-09-09 13:22:23'),
(709, 'PageThing', '2025-09-09 13:22:23'),
(710, 'Pageburst', '2025-09-09 13:22:23'),
(711, 'Panscient', '2025-09-09 13:22:23'),
(712, 'PaperLiBot', '2025-09-09 13:22:23'),
(713, 'PayPalIPN', '2025-09-09 13:22:23'),
(714, 'PetalBot', '2025-09-09 13:22:23'),
(715, 'Phantomas', '2025-09-09 13:22:23'),
(716, 'Picsearchbot', '2025-09-09 13:22:23'),
(717, 'PingAdmin.Ru', '2025-09-09 13:22:23'),
(718, 'PingdomBot', '2025-09-09 13:22:23'),
(719, 'Pinterest', '2025-09-09 13:22:23'),
(720, 'PiplBot', '2025-09-09 13:22:23'),
(721, 'Plukkie', '2025-09-09 13:22:23'),
(722, 'Pocket', '2025-09-09 13:22:23'),
(723, 'PocketParser', '2025-09-09 13:22:23'),
(724, 'Pompos', '2025-09-09 13:22:23'),
(725, 'PritTorrent', '2025-09-09 13:22:23'),
(726, 'ProjectPatchwatch', '2025-09-09 13:22:23'),
(727, 'ProjectResonance', '2025-09-09 13:22:23'),
(728, 'QuerySeekerSpider', '2025-09-09 13:22:23'),
(729, 'QuoraBot', '2025-09-09 13:22:23'),
(730, 'QuoraLinkPreview', '2025-09-09 13:22:23'),
(731, 'Qwantify', '2025-09-09 13:22:23'),
(732, 'ROIHunter', '2025-09-09 13:22:23'),
(733, 'Rainmeter', '2025-09-09 13:22:23'),
(734, 'RamblerMailImageProxy', '2025-09-09 13:22:23'),
(735, 'RedditBot', '2025-09-09 13:22:23'),
(736, 'RenovateBot', '2025-09-09 13:22:23'),
(737, 'ReqBin', '2025-09-09 13:22:23'),
(738, 'Riddler', '2025-09-09 13:22:23'),
(739, 'Robozilla', '2025-09-09 13:22:23'),
(740, 'RocketMonitorBot', '2025-09-09 13:22:23'),
(741, 'Rogerbot', '2025-09-09 13:22:23'),
(742, 'SEMrushReputationManagement', '2025-09-09 13:22:23'),
(743, 'SEOENGBot', '2025-09-09 13:22:23'),
(744, 'SEOkicks', '2025-09-09 13:22:23'),
(745, 'SEOkicks-Robot', '2025-09-09 13:22:23'),
(746, 'SISTRIXCrawler', '2025-09-09 13:22:23'),
(747, 'SISTRIXOptimizer', '2025-09-09 13:22:23'),
(748, 'SMTBot', '2025-09-09 13:22:23'),
(749, 'SSLLabs', '2025-09-09 13:22:23'),
(750, 'SabsimBot', '2025-09-09 13:22:23'),
(751, 'SafeDNSBot', '2025-09-09 13:22:23'),
(752, 'ScamadviserExternalHit', '2025-09-09 13:22:23'),
(753, 'ScoutJet', '2025-09-09 13:22:23'),
(754, 'Scrapy', '2025-09-09 13:22:23'),
(755, 'ScreamingFrogSEOSpider', '2025-09-09 13:22:23'),
(756, 'ScreenerBot', '2025-09-09 13:22:23'),
(757, 'SectigoDCV', '2025-09-09 13:22:23'),
(758, 'Seekport', '2025-09-09 13:22:23'),
(759, 'Sellers.Guide', '2025-09-09 13:22:23'),
(760, 'SemanticScholarBot', '2025-09-09 13:22:23'),
(761, 'SemrushBot', '2025-09-09 13:22:23'),
(762, 'SensikaBot', '2025-09-09 13:22:23'),
(763, 'SentryBot', '2025-09-09 13:22:23'),
(764, 'Seobility', '2025-09-09 13:22:23'),
(765, 'SerendeputyBot', '2025-09-09 13:22:23'),
(766, 'ServerDensity', '2025-09-09 13:22:23'),
(767, 'SeznamBot', '2025-09-09 13:22:23'),
(768, 'SeznamEmailProxy', '2025-09-09 13:22:23'),
(769, 'SeznamZbozi.cz', '2025-09-09 13:22:23'),
(770, 'ShopAlike', '2025-09-09 13:22:23'),
(771, 'ShopifyPartner', '2025-09-09 13:22:23'),
(772, 'SilverReader', '2025-09-09 13:22:23'),
(773, 'SimplePie', '2025-09-09 13:22:23'),
(774, 'Site24x7WebsiteMonitoring', '2025-09-09 13:22:23'),
(775, 'SiteSucker', '2025-09-09 13:22:23'),
(776, 'Siteimprove', '2025-09-09 13:22:23'),
(777, 'SitemapParser-VIPnytt', '2025-09-09 13:22:23'),
(778, 'Sixy.ch', '2025-09-09 13:22:23'),
(779, 'SkypeURIPreview', '2025-09-09 13:22:23'),
(780, 'Slackbot', '2025-09-09 13:22:23'),
(781, 'SnapURLPreviewService', '2025-09-09 13:22:23'),
(782, 'SogouSpider', '2025-09-09 13:22:23'),
(783, 'SosoSpider', '2025-09-09 13:22:23'),
(784, 'Sparkler', '2025-09-09 13:22:23'),
(785, 'Speedy', '2025-09-09 13:22:23'),
(786, 'Spinn3r', '2025-09-09 13:22:23'),
(787, 'Spotify', '2025-09-09 13:22:23'),
(788, 'Sprinklr', '2025-09-09 13:22:23'),
(789, 'SputnikBot', '2025-09-09 13:22:23'),
(790, 'SputnikImageBot', '2025-09-09 13:22:23'),
(791, 'StatusCake', '2025-09-09 13:22:23'),
(792, 'Sublinq', '2025-09-09 13:22:23'),
(793, 'SurdotlyBot', '2025-09-09 13:22:23'),
(794, 'SurveyBot', '2025-09-09 13:22:23'),
(795, 'TLSProbe', '2025-09-09 13:22:23'),
(796, 'TagInspector', '2025-09-09 13:22:23'),
(797, 'Telegram', '2025-09-09 13:22:23'),
(798, 'TelegramBot', '2025-09-09 13:22:23'),
(799, 'TheKnowledgeAI', '2025-09-09 13:22:23'),
(800, 'ThinkChaos', '2025-09-09 13:22:23'),
(801, 'TigerBot', '2025-09-09 13:22:23'),
(802, 'TinEyeCrawler', '2025-09-09 13:22:23'),
(803, 'TinyTinyRSS', '2025-09-09 13:22:23'),
(804, 'TraceMyFile', '2025-09-09 13:22:23'),
(805, 'TrendictionBot', '2025-09-09 13:22:23'),
(806, 'Trident', '2025-09-09 13:22:23'),
(807, 'Turnitin', '2025-09-09 13:22:23'),
(808, 'TurnitinBot', '2025-09-09 13:22:23'),
(809, 'TweetedTimesBot', '2025-09-09 13:22:23'),
(810, 'TweetmemeBot', '2025-09-09 13:22:23'),
(811, 'TwinglyRecon', '2025-09-09 13:22:23'),
(812, 'TwitterBot', '2025-09-09 13:22:23'),
(813, 'Twitterbot', '2025-09-09 13:22:23'),
(814, 'URLAppendBot', '2025-09-09 13:22:23'),
(815, 'UkrNetMailProxy', '2025-09-09 13:22:23'),
(816, 'UniversalFeedParser', '2025-09-09 13:22:23'),
(817, 'UptimeRobot', '2025-09-09 13:22:23'),
(818, 'Uptimebot', '2025-09-09 13:22:23'),
(819, 'VKShareButton', '2025-09-09 13:22:23'),
(820, 'VelenPublicWebCrawler', '2025-09-09 13:22:23'),
(821, 'VercelBot', '2025-09-09 13:22:23'),
(822, 'VeryHip', '2025-09-09 13:22:23'),
(823, 'VisualSiteMapperCrawler', '2025-09-09 13:22:23'),
(824, 'W3CCSSValidator', '2025-09-09 13:22:23'),
(825, 'W3CI18NChecker', '2025-09-09 13:22:23'),
(826, 'W3CLinkChecker', '2025-09-09 13:22:23'),
(827, 'W3CMarkupValidationService', '2025-09-09 13:22:23'),
(828, 'W3CMobileOKChecker', '2025-09-09 13:22:23'),
(829, 'W3CUnifiedValidator', '2025-09-09 13:22:23'),
(830, 'Wappalyzer', '2025-09-09 13:22:23'),
(831, 'WeSEE:Search', '2025-09-09 13:22:23'),
(832, 'WebDataStats', '2025-09-09 13:22:23'),
(833, 'WebPageTest', '2025-09-09 13:22:23'),
(834, 'WebSitePulse', '2025-09-09 13:22:23'),
(835, 'WebbCrawler', '2025-09-09 13:22:23'),
(836, 'Weborama', '2025-09-09 13:22:23'),
(837, 'WellKnownBot', '2025-09-09 13:22:23'),
(838, 'WhatCMS', '2025-09-09 13:22:23'),
(839, 'WhereGoes', '2025-09-09 13:22:23'),
(840, 'WillowInternetCrawler', '2025-09-09 13:22:23'),
(841, 'WooRank', '2025-09-09 13:22:23'),
(842, 'WordPress', '2025-09-09 13:22:23'),
(843, 'Wotbox', '2025-09-09 13:22:23'),
(844, 'X11', '2025-09-09 13:22:23'),
(845, 'XenForo', '2025-09-09 13:22:23'),
(846, 'YaCy', '2025-09-09 13:22:23'),
(847, 'Yahoo! Slurp', '2025-09-09 13:22:23'),
(848, 'Yahoo!CacheSystem', '2025-09-09 13:22:23'),
(849, 'Yahoo!LinkPreview', '2025-09-09 13:22:23'),
(850, 'Yahoo!MailProxy', '2025-09-09 13:22:23'),
(851, 'YahooGemini', '2025-09-09 13:22:23'),
(852, 'YandexBot', '2025-09-09 13:22:23'),
(853, 'Yeti/Naverbot', '2025-09-09 13:22:23'),
(854, 'YoudaoBot', '2025-09-09 13:22:23'),
(855, 'Yourls', '2025-09-09 13:22:23'),
(856, 'Zaldamo', '2025-09-09 13:22:23'),
(857, 'Zao', '2025-09-09 13:22:23'),
(858, 'ZeList', '2025-09-09 13:22:23'),
(859, 'ZoominfoBot', '2025-09-09 13:22:23'),
(860, 'ZumBot', '2025-09-09 13:22:23'),
(861, 'a6-indexer', '2025-09-09 13:22:23'),
(862, 'aHrefsBot', '2025-09-09 13:22:23'),
(863, 'aboundexbot', '2025-09-09 13:22:23'),
(864, 'aboutusbot', '2025-09-09 13:22:23'),
(865, 'above', '2025-09-09 13:22:23'),
(866, 'abravespider', '2025-09-09 13:22:23'),
(867, 'accelobot', '2025-09-09 13:22:23'),
(868, 'accoona', '2025-09-09 13:22:23'),
(869, 'acoon', '2025-09-09 13:22:23'),
(870, 'acoonbot', '2025-09-09 13:22:23'),
(871, 'acunetix', '2025-09-09 13:22:23'),
(872, 'addthis.com', '2025-09-09 13:22:23'),
(873, 'admantx', '2025-09-09 13:22:23'),
(874, 'admantx-testex', '2025-09-09 13:22:23'),
(875, 'adressendeutschland', '2025-09-09 13:22:23'),
(876, 'adsbot-google', '2025-09-09 13:22:23'),
(877, 'adstxtlab.com', '2025-09-09 13:22:23'),
(878, 'ah-ha.com', '2025-09-09 13:22:23'),
(879, 'ahoy', '2025-09-09 13:22:23'),
(880, 'ahrefsbot', '2025-09-09 13:22:23'),
(881, 'aiHitBot', '2025-09-09 13:22:23'),
(882, 'alexabot', '2025-09-09 13:22:23'),
(883, 'alibaba', '2025-09-09 13:22:23'),
(884, 'altavista', '2025-09-09 13:22:23'),
(885, 'amagit.com', '2025-09-09 13:22:23'),
(886, 'amazonaws', '2025-09-09 13:22:23'),
(887, 'amznkassocbot', '2025-09-09 13:22:23'),
(888, 'analytics', '2025-09-09 13:22:23'),
(889, 'ananzi', '2025-09-09 13:22:23'),
(890, 'antbot', '2025-09-09 13:22:23'),
(891, 'anthill', '2025-09-09 13:22:23'),
(892, 'apache-httpclient', '2025-09-09 13:22:23'),
(893, 'apercite', '2025-09-09 13:22:23'),
(894, 'aportworm', '2025-09-09 13:22:23'),
(895, 'appie', '2025-09-09 13:22:23'),
(896, 'arabot', '2025-09-09 13:22:23'),
(897, 'arachnophilia', '2025-09-09 13:22:23'),
(898, 'arale', '2025-09-09 13:22:23'),
(899, 'araneo', '2025-09-09 13:22:23'),
(900, 'aranha', '2025-09-09 13:22:23'),
(901, 'architext', '2025-09-09 13:22:23'),
(902, 'archive.orgbot', '2025-09-09 13:22:23'),
(903, 'aretha', '2025-09-09 13:22:23'),
(904, 'arks', '2025-09-09 13:22:23'),
(905, 'asterias', '2025-09-09 13:22:23'),
(906, 'atlocal', '2025-09-09 13:22:23'),
(907, 'atn', '2025-09-09 13:22:23'),
(908, 'atomz', '2025-09-09 13:22:23'),
(909, 'augurfind', '2025-09-09 13:22:23'),
(910, 'backrub', '2025-09-09 13:22:23'),
(911, 'bannana_bot', '2025-09-09 13:22:23'),
(912, 'baypup', '2025-09-09 13:22:23'),
(913, 'bdfetch', '2025-09-09 13:22:23'),
(914, 'bigbrother', '2025-09-09 13:22:23'),
(915, 'biglotron', '2025-09-09 13:22:23'),
(916, 'bjaaland', '2025-09-09 13:22:23'),
(917, 'blackwidow', '2025-09-09 13:22:23'),
(918, 'blaiz', '2025-09-09 13:22:23'),
(919, 'blo.', '2025-09-09 13:22:23'),
(920, 'blog', '2025-09-09 13:22:23'),
(921, 'bloodhound', '2025-09-09 13:22:23'),
(922, 'boitho', '2025-09-09 13:22:23'),
(923, 'booch', '2025-09-09 13:22:23'),
(924, 'bot', '2025-09-09 13:22:23'),
(925, 'bradley', '2025-09-09 13:22:23'),
(926, 'butterfly', '2025-09-09 13:22:23'),
(927, 'calif', '2025-09-09 13:22:23'),
(928, 'calyxinstitute', '2025-09-09 13:22:23'),
(929, 'cassandra', '2025-09-09 13:22:23'),
(930, 'ccBotcrawler', '2025-09-09 13:22:23'),
(931, 'ccubee', '2025-09-09 13:22:23'),
(932, 'cfetch', '2025-09-09 13:22:23'),
(933, 'charlotte', '2025-09-09 13:22:23'),
(934, 'churl', '2025-09-09 13:22:23'),
(935, 'cienciaficcion', '2025-09-09 13:22:23'),
(936, 'cmc', '2025-09-09 13:22:23'),
(937, 'collective', '2025-09-09 13:22:23'),
(938, 'colly', '2025-09-09 13:22:23'),
(939, 'comagent', '2025-09-09 13:22:23'),
(940, 'combine', '2025-09-09 13:22:23'),
(941, 'compatible', '2025-09-09 13:22:23'),
(942, 'computingsite', '2025-09-09 13:22:23'),
(943, 'crawl', '2025-09-09 13:22:23'),
(944, 'crawler', '2025-09-09 13:22:23'),
(945, 'csci', '2025-09-09 13:22:23'),
(946, 'curl', '2025-09-09 13:22:23'),
(947, 'cusco', '2025-09-09 13:22:23'),
(948, 'cyveillance', '2025-09-09 13:22:23'),
(949, 'datagnionbot', '2025-09-09 13:22:23'),
(950, 'daumoa', '2025-09-09 13:22:23'),
(951, 'deepindex', '2025-09-09 13:22:23'),
(952, 'deepnoc', '2025-09-09 13:22:23'),
(953, 'delorie', '2025-09-09 13:22:23'),
(954, 'depspid', '2025-09-09 13:22:23'),
(955, 'deweb', '2025-09-09 13:22:23'),
(956, 'dieblindekuh', '2025-09-09 13:22:23'),
(957, 'digger', '2025-09-09 13:22:23'),
(958, 'ditto', '2025-09-09 13:22:23'),
(959, 'dmoz', '2025-09-09 13:22:23'),
(960, 'docomo', '2025-09-09 13:22:23'),
(961, 'downloadexpress', '2025-09-09 13:22:23'),
(962, 'dreamhost', '2025-09-09 13:22:23'),
(963, 'drweb', '2025-09-09 13:22:23'),
(964, 'dtaagent', '2025-09-09 13:22:23'),
(965, 'dwcp', '2025-09-09 13:22:23'),
(966, 'e-collector', '2025-09-09 13:22:23'),
(967, 'eCairn-Grabber', '2025-09-09 13:22:23'),
(968, 'eZPublishLinkValidator', '2025-09-09 13:22:23'),
(969, 'ebay', '2025-09-09 13:22:23'),
(970, 'ebiness', '2025-09-09 13:22:23'),
(971, 'ebingbong', '2025-09-09 13:22:23'),
(972, 'ejupiter', '2025-09-09 13:22:23'),
(973, 'emacs-w3searchengine', '2025-09-09 13:22:23'),
(974, 'esther', '2025-09-09 13:22:23'),
(975, 'evc-batch', '2025-09-09 13:22:23'),
(976, 'evliyacelebi', '2025-09-09 13:22:23'),
(977, 'ezresult', '2025-09-09 13:22:23'),
(978, 'facebook', '2025-09-09 13:22:23'),
(979, 'falcon', '2025-09-09 13:22:23'),
(980, 'felixide', '2025-09-09 13:22:23'),
(981, 'ferret', '2025-09-09 13:22:23'),
(982, 'fetchrover', '2025-09-09 13:22:23'),
(983, 'fido', '2025-09-09 13:22:23'),
(984, 'findlinks', '2025-09-09 13:22:23'),
(985, 'fireball', '2025-09-09 13:22:23'),
(986, 'fishsearch', '2025-09-09 13:22:23'),
(987, 'fouineur', '2025-09-09 13:22:23'),
(988, 'funnelweb', '2025-09-09 13:22:23'),
(989, 'gazz', '2025-09-09 13:22:23'),
(990, 'gcreep', '2025-09-09 13:22:23'),
(991, 'genieknows', '2025-09-09 13:22:23'),
(992, 'getterroboplus', '2025-09-09 13:22:23'),
(993, 'geturl', '2025-09-09 13:22:23'),
(994, 'glx', '2025-09-09 13:22:23'),
(995, 'goforit', '2025-09-09 13:22:23'),
(996, 'golem', '2025-09-09 13:22:23'),
(997, 'google', '2025-09-09 13:22:23'),
(998, 'grabber', '2025-09-09 13:22:23'),
(999, 'gralon', '2025-09-09 13:22:23'),
(1000, 'grapnel', '2025-09-09 13:22:23'),
(1001, 'griffon', '2025-09-09 13:22:23'),
(1002, 'gromit', '2025-09-09 13:22:23'),
(1003, 'grub', '2025-09-09 13:22:23'),
(1004, 'gulliver', '2025-09-09 13:22:23'),
(1005, 'hamahakki', '2025-09-09 13:22:23'),
(1006, 'harvest', '2025-09-09 13:22:23'),
(1007, 'havindex', '2025-09-09 13:22:23'),
(1008, 'helix', '2025-09-09 13:22:23'),
(1009, 'heritrix', '2025-09-09 13:22:23'),
(1010, 'hkuwwwoctopus', '2025-09-09 13:22:23'),
(1011, 'homerweb', '2025-09-09 13:22:23'),
(1012, 'hostinger', '2025-09-09 13:22:23'),
(1013, 'htdig', '2025-09-09 13:22:23'),
(1014, 'html_analyzer', '2025-09-09 13:22:23'),
(1015, 'htmlgobble', '2025-09-09 13:22:23'),
(1016, 'htmlindex', '2025-09-09 13:22:23'),
(1017, 'httpx', '2025-09-09 13:22:23'),
(1018, 'hubater', '2025-09-09 13:22:23'),
(1019, 'hyper-decontextualizer', '2025-09-09 13:22:23'),
(1020, 'ia_archiver', '2025-09-09 13:22:23'),
(1021, 'ibm_planetwide', '2025-09-09 13:22:23'),
(1022, 'ichiro', '2025-09-09 13:22:23'),
(1023, 'iconsurf', '2025-09-09 13:22:23'),
(1024, 'iltrovatore', '2025-09-09 13:22:23'),
(1025, 'image.kapsi.net', '2025-09-09 13:22:23'),
(1026, 'imagelock', '2025-09-09 13:22:23'),
(1027, 'incywincy', '2025-09-09 13:22:23'),
(1028, 'indexer', '2025-09-09 13:22:23'),
(1029, 'infobee', '2025-09-09 13:22:23'),
(1030, 'informant', '2025-09-09 13:22:23'),
(1031, 'ingrid', '2025-09-09 13:22:23'),
(1032, 'inktomisearch.com', '2025-09-09 13:22:23'),
(1033, 'inoreader', '2025-09-09 13:22:23'),
(1034, 'inspectorweb', '2025-09-09 13:22:23'),
(1035, 'intelliagent', '2025-09-09 13:22:23'),
(1036, 'internetshinchakubin', '2025-09-09 13:22:23'),
(1037, 'ip3000', '2025-09-09 13:22:23'),
(1038, 'iron33', '2025-09-09 13:22:23'),
(1039, 'israeli-search', '2025-09-09 13:22:23'),
(1040, 'ivia', '2025-09-09 13:22:23'),
(1041, 'jack', '2025-09-09 13:22:23'),
(1042, 'jakarta', '2025-09-09 13:22:23'),
(1043, 'javabee', '2025-09-09 13:22:23'),
(1044, 'jetbot', '2025-09-09 13:22:23'),
(1045, 'jetbrains', '2025-09-09 13:22:23'),
(1046, 'jumpstation', '2025-09-09 13:22:23'),
(1047, 'katipo', '2025-09-09 13:22:23'),
(1048, 'kdd-explorer', '2025-09-09 13:22:23'),
(1049, 'kilroy', '2025-09-09 13:22:23'),
(1050, 'knowledge', '2025-09-09 13:22:23'),
(1051, 'kototoi', '2025-09-09 13:22:23'),
(1052, 'kretrieve', '2025-09-09 13:22:23'),
(1053, 'l9explore', '2025-09-09 13:22:23'),
(1054, 'l9tcpid', '2025-09-09 13:22:23'),
(1055, 'labelgrabber', '2025-09-09 13:22:23'),
(1056, 'lachesis', '2025-09-09 13:22:23'),
(1057, 'larbin', '2025-09-09 13:22:23'),
(1058, 'legs', '2025-09-09 13:22:23'),
(1059, 'libwww', '2025-09-09 13:22:23'),
(1060, 'linkalarm', '2025-09-09 13:22:23'),
(1061, 'linkscan', '2025-09-09 13:22:23'),
(1062, 'linkvalidator', '2025-09-09 13:22:23'),
(1063, 'lockon', '2025-09-09 13:22:23'),
(1064, 'lssrocketcrawler', '2025-09-09 13:22:23'),
(1065, 'lwp', '2025-09-09 13:22:23'),
(1066, 'lycos', '2025-09-09 13:22:23'),
(1067, 'magpie', '2025-09-09 13:22:23'),
(1068, 'mantraagent', '2025-09-09 13:22:23'),
(1069, 'mapoftheinternet', '2025-09-09 13:22:23'),
(1070, 'marvin/', '2025-09-09 13:22:23'),
(1071, 'masscan', '2025-09-09 13:22:23'),
(1072, 'masscan-ng', '2025-09-09 13:22:23'),
(1073, 'mattie', '2025-09-09 13:22:23'),
(1074, 'mediafox', '2025-09-09 13:22:23'),
(1075, 'mediapartners', '2025-09-09 13:22:23'),
(1076, 'mercator', '2025-09-09 13:22:23'),
(1077, 'merzscope', '2025-09-09 13:22:23'),
(1078, 'messagelabs', '2025-09-09 13:22:23'),
(1079, 'microsofturlcontrol', '2025-09-09 13:22:23'),
(1080, 'minirank', '2025-09-09 13:22:23'),
(1081, 'miva', '2025-09-09 13:22:23'),
(1082, 'mj12', '2025-09-09 13:22:23'),
(1083, 'mnogosearch', '2025-09-09 13:22:23'),
(1084, 'moget', '2025-09-09 13:22:23'),
(1085, 'monster', '2025-09-09 13:22:23'),
(1086, 'moose', '2025-09-09 13:22:23'),
(1087, 'motor', '2025-09-09 13:22:23'),
(1088, 'msnbot', '2025-09-09 13:22:23'),
(1089, 'multitext', '2025-09-09 13:22:23'),
(1090, 'muncher', '2025-09-09 13:22:23'),
(1091, 'muscatferret', '2025-09-09 13:22:23'),
(1092, 'mwd.search', '2025-09-09 13:22:23'),
(1093, 'myweb', '2025-09-09 13:22:23'),
(1094, 'najdi', '2025-09-09 13:22:23'),
(1095, 'nameprotect', '2025-09-09 13:22:23'),
(1096, 'nationaldirectory', '2025-09-09 13:22:23'),
(1097, 'nazilla', '2025-09-09 13:22:23'),
(1098, 'nbertaupete95', '2025-09-09 13:22:23'),
(1099, 'ncsabeta', '2025-09-09 13:22:23'),
(1100, 'nec-meshexplorer', '2025-09-09 13:22:23'),
(1101, 'nederland.zoek', '2025-09-09 13:22:23'),
(1102, 'netEstate', '2025-09-09 13:22:23'),
(1103, 'netcartawebmapengine', '2025-09-09 13:22:23'),
(1104, 'netcraft', '2025-09-09 13:22:23'),
(1105, 'netmechanic', '2025-09-09 13:22:23'),
(1106, 'netpilot', '2025-09-09 13:22:23'),
(1107, 'netresearchserver', '2025-09-09 13:22:23'),
(1108, 'netscoop', '2025-09-09 13:22:23'),
(1109, 'netsparker', '2025-09-09 13:22:23'),
(1110, 'newscan-online', '2025-09-09 13:22:23'),
(1111, 'nhse', '2025-09-09 13:22:23'),
(1112, 'nokia6682/', '2025-09-09 13:22:23'),
(1113, 'nomad', '2025-09-09 13:22:23'),
(1114, 'noyona', '2025-09-09 13:22:23'),
(1115, 'nutch', '2025-09-09 13:22:23'),
(1116, 'nzexplorer', '2025-09-09 13:22:23'),
(1117, 'oBot', '2025-09-09 13:22:23'),
(1118, 'objectssearch', '2025-09-09 13:22:23'),
(1119, 'occam', '2025-09-09 13:22:23'),
(1120, 'omni', '2025-09-09 13:22:23'),
(1121, 'openfind', '2025-09-09 13:22:23'),
(1122, 'openintelligencedata', '2025-09-09 13:22:23'),
(1123, 'opentext', '2025-09-09 13:22:23'),
(1124, 'orbsearch', '2025-09-09 13:22:23'),
(1125, 'osis-project', '2025-09-09 13:22:23'),
(1126, 'p3pwgdsn', '2025-09-09 13:22:23'),
(1127, 'packrat', '2025-09-09 13:22:23'),
(1128, 'page_verifier', '2025-09-09 13:22:23'),
(1129, 'pageboy', '2025-09-09 13:22:23'),
(1130, 'pagebull', '2025-09-09 13:22:23'),
(1131, 'panscient', '2025-09-09 13:22:23'),
(1132, 'parasite', '2025-09-09 13:22:23'),
(1133, 'partnersite', '2025-09-09 13:22:23'),
(1134, 'patric', '2025-09-09 13:22:23'),
(1135, 'paypal', '2025-09-09 13:22:23'),
(1136, 'pear.', '2025-09-09 13:22:23'),
(1137, 'pegasus', '2025-09-09 13:22:23'),
(1138, 'peregrinator', '2025-09-09 13:22:23'),
(1139, 'pgpkeyagent', '2025-09-09 13:22:23'),
(1140, 'phantom', '2025-09-09 13:22:23'),
(1141, 'phishtank', '2025-09-09 13:22:23'),
(1142, 'phpdig', '2025-09-09 13:22:23'),
(1143, 'picosearch', '2025-09-09 13:22:23'),
(1144, 'piltdownman', '2025-09-09 13:22:23'),
(1145, 'pimptrain', '2025-09-09 13:22:23'),
(1146, 'pinpoint', '2025-09-09 13:22:23'),
(1147, 'pioneer', '2025-09-09 13:22:23'),
(1148, 'piranha', '2025-09-09 13:22:23'),
(1149, 'plumtreewebaccessor', '2025-09-09 13:22:23'),
(1150, 'pogodak', '2025-09-09 13:22:23'),
(1151, 'poirot', '2025-09-09 13:22:23'),
(1152, 'pompos', '2025-09-09 13:22:23'),
(1153, 'poppelsdorf', '2025-09-09 13:22:23'),
(1154, 'poppi', '2025-09-09 13:22:23'),
(1155, 'populariconoclast', '2025-09-09 13:22:23'),
(1156, 'psycheclone', '2025-09-09 13:22:23'),
(1157, 'publisher', '2025-09-09 13:22:23'),
(1158, 'python', '2025-09-09 13:22:23'),
(1159, 'rambler', '2025-09-09 13:22:23'),
(1160, 'ravensearch', '2025-09-09 13:22:23'),
(1161, 'roach', '2025-09-09 13:22:23'),
(1162, 'roadhouse', '2025-09-09 13:22:23'),
(1163, 'roadrunner', '2025-09-09 13:22:23'),
(1164, 'robbie', '2025-09-09 13:22:23'),
(1165, 'robofox', '2025-09-09 13:22:23'),
(1166, 'robozilla', '2025-09-09 13:22:23'),
(1167, 'rules', '2025-09-09 13:22:23'),
(1168, 'salty', '2025-09-09 13:22:23'),
(1169, 'samsung', '2025-09-09 13:22:23'),
(1170, 'sbider', '2025-09-09 13:22:23'),
(1171, 'scanurl', '2025-09-09 13:22:23'),
(1172, 'scooter', '2025-09-09 13:22:23'),
(1173, 'scoutjet', '2025-09-09 13:22:23'),
(1174, 'scrubby', '2025-09-09 13:22:23'),
(1175, 'search.', '2025-09-09 13:22:23'),
(1176, 'searchprocess', '2025-09-09 13:22:23'),
(1177, 'seek', '2025-09-09 13:22:23'),
(1178, 'semanticdiscovery', '2025-09-09 13:22:23'),
(1179, 'senrigan', '2025-09-09 13:22:23'),
(1180, 'seolyt', '2025-09-09 13:22:23'),
(1181, 'sg-scout', '2025-09-09 13:22:23'),
(1182, 'shai\\\'hulud', '2025-09-09 13:22:23'),
(1183, 'shark', '2025-09-09 13:22:23'),
(1184, 'shopwiki', '2025-09-09 13:22:23'),
(1185, 'sidewinder', '2025-09-09 13:22:23'),
(1186, 'sift', '2025-09-09 13:22:23'),
(1187, 'silk', '2025-09-09 13:22:23'),
(1188, 'simmany', '2025-09-09 13:22:23'),
(1189, 'sitesearcher', '2025-09-09 13:22:23'),
(1190, 'sitetech-rover', '2025-09-09 13:22:23'),
(1191, 'sitevalet', '2025-09-09 13:22:23'),
(1192, 'skymob.com', '2025-09-09 13:22:23'),
(1193, 'sleek', '2025-09-09 13:22:23'),
(1194, 'slurp', '2025-09-09 13:22:23'),
(1195, 'smartwit', '2025-09-09 13:22:23'),
(1196, 'sna-', '2025-09-09 13:22:23'),
(1197, 'snappy', '2025-09-09 13:22:23'),
(1198, 'snooper', '2025-09-09 13:22:23'),
(1199, 'softlayer', '2025-09-09 13:22:23'),
(1200, 'sohu', '2025-09-09 13:22:23'),
(1201, 'spam', '2025-09-09 13:22:23'),
(1202, 'speedfind', '2025-09-09 13:22:23'),
(1203, 'sphere', '2025-09-09 13:22:23'),
(1204, 'sphider', '2025-09-09 13:22:23'),
(1205, 'spider', '2025-09-09 13:22:23'),
(1206, 'spinner', '2025-09-09 13:22:23');
INSERT INTO `blocked_user_agents` (`id`, `pattern`, `created_at`) VALUES
(1207, 'spyder', '2025-09-09 13:22:23'),
(1208, 'start.me', '2025-09-09 13:22:24'),
(1209, 'steeler/', '2025-09-09 13:22:24'),
(1210, 'sucuri.net', '2025-09-09 13:22:24'),
(1211, 'suke', '2025-09-09 13:22:24'),
(1212, 'suntek', '2025-09-09 13:22:24'),
(1213, 'supersnooper', '2025-09-09 13:22:24'),
(1214, 'surfnomore', '2025-09-09 13:22:24'),
(1215, 'sven', '2025-09-09 13:22:24'),
(1216, 'sygol', '2025-09-09 13:22:24'),
(1217, 'szukacz', '2025-09-09 13:22:24'),
(1218, 't-h-u-n-d-e-r-s-t-o-n-e', '2025-09-09 13:22:24'),
(1219, 't-rex', '2025-09-09 13:22:24'),
(1220, 't3versions', '2025-09-09 13:22:24'),
(1221, 'tachblackwidow', '2025-09-09 13:22:24'),
(1222, 'tarantula', '2025-09-09 13:22:24'),
(1223, 'templeton', '2025-09-09 13:22:24'),
(1224, 'theoldreader', '2025-09-09 13:22:24'),
(1225, 'theophrastus', '2025-09-09 13:22:24'),
(1226, 'titan', '2025-09-09 13:22:24'),
(1227, 'titin', '2025-09-09 13:22:24'),
(1228, 'tkwww', '2025-09-09 13:22:24'),
(1229, 'tor-exit', '2025-09-09 13:22:24'),
(1230, 'torservers', '2025-09-09 13:22:24'),
(1231, 'toutatis', '2025-09-09 13:22:24'),
(1232, 'trendmicro', '2025-09-09 13:22:24'),
(1233, 'tutorgig', '2025-09-09 13:22:24'),
(1234, 'twiceler', '2025-09-09 13:22:24'),
(1235, 'twisted', '2025-09-09 13:22:24'),
(1236, 'ucsd', '2025-09-09 13:22:24'),
(1237, 'udmsearch', '2025-09-09 13:22:24'),
(1238, 'updated', '2025-09-09 13:22:24'),
(1239, 'urlcheck', '2025-09-09 13:22:24'),
(1240, 'urlredirectresolver', '2025-09-09 13:22:24'),
(1241, 'vagabondo', '2025-09-09 13:22:24'),
(1242, 'valkyrie', '2025-09-09 13:22:24'),
(1243, 'verticrawl', '2025-09-09 13:22:24'),
(1244, 'victoria', '2025-09-09 13:22:24'),
(1245, 'vision-search', '2025-09-09 13:22:24'),
(1246, 'volcano', '2025-09-09 13:22:24'),
(1247, 'voyager-hc', '2025-09-09 13:22:24'),
(1248, 'voyager/', '2025-09-09 13:22:24'),
(1249, 'w3c_validator', '2025-09-09 13:22:24'),
(1250, 'w3m2', '2025-09-09 13:22:24'),
(1251, 'w3mir', '2025-09-09 13:22:24'),
(1252, 'walker', '2025-09-09 13:22:24'),
(1253, 'wallpaper', '2025-09-09 13:22:24'),
(1254, 'wanderer', '2025-09-09 13:22:24'),
(1255, 'wauuu', '2025-09-09 13:22:24'),
(1256, 'wavefire', '2025-09-09 13:22:24'),
(1257, 'webbandit', '2025-09-09 13:22:24'),
(1258, 'webcatcher', '2025-09-09 13:22:24'),
(1259, 'webcopy', '2025-09-09 13:22:24'),
(1260, 'webcore', '2025-09-09 13:22:24'),
(1261, 'webfoot', '2025-09-09 13:22:24'),
(1262, 'webhopper', '2025-09-09 13:22:24'),
(1263, 'weblayers', '2025-09-09 13:22:24'),
(1264, 'weblinker', '2025-09-09 13:22:24'),
(1265, 'weblogmonitor', '2025-09-09 13:22:24'),
(1266, 'webmirror', '2025-09-09 13:22:24'),
(1267, 'webmonkey', '2025-09-09 13:22:24'),
(1268, 'webquest', '2025-09-09 13:22:24'),
(1269, 'webreaper', '2025-09-09 13:22:24'),
(1270, 'websitepulse', '2025-09-09 13:22:24'),
(1271, 'websnarf', '2025-09-09 13:22:24'),
(1272, 'webstolperer', '2025-09-09 13:22:24'),
(1273, 'webvac', '2025-09-09 13:22:24'),
(1274, 'webwalk', '2025-09-09 13:22:24'),
(1275, 'webwatch', '2025-09-09 13:22:24'),
(1276, 'webwombat', '2025-09-09 13:22:24'),
(1277, 'webzinger', '2025-09-09 13:22:24'),
(1278, 'wget', '2025-09-09 13:22:24'),
(1279, 'whizbang', '2025-09-09 13:22:24'),
(1280, 'whowhere', '2025-09-09 13:22:24'),
(1281, 'wildferret', '2025-09-09 13:22:24'),
(1282, 'windows95', '2025-09-09 13:22:24'),
(1283, 'windows98', '2025-09-09 13:22:24'),
(1284, 'worldlight', '2025-09-09 13:22:24'),
(1285, 'wwwc', '2025-09-09 13:22:24'),
(1286, 'wwwster', '2025-09-09 13:22:24'),
(1287, 'xenu', '2025-09-09 13:22:24'),
(1288, 'xift', '2025-09-09 13:22:24'),
(1289, 'xirq', '2025-09-09 13:22:24'),
(1290, 'yacybot', '2025-09-09 13:22:24'),
(1291, 'yahoo!', '2025-09-09 13:22:24'),
(1292, 'yandex', '2025-09-09 13:22:24'),
(1293, 'yanga', '2025-09-09 13:22:24'),
(1294, 'yeti', '2025-09-09 13:22:24'),
(1295, 'zgrab', '2025-09-09 13:22:24');

-- --------------------------------------------------------

--
-- Table structure for table `browser_fingerprints`
--

DROP TABLE IF EXISTS `browser_fingerprints`;
CREATE TABLE IF NOT EXISTS `browser_fingerprints` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fingerprint_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usage_count` int DEFAULT '1',
  `is_suspicious` tinyint DEFAULT '0',
  `last_seen` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_fingerprint` (`fingerprint_hash`),
  KEY `idx_last_seen` (`last_seen`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `captcha_settings`
--

DROP TABLE IF EXISTS `captcha_settings`;
CREATE TABLE IF NOT EXISTS `captcha_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `captcha_settings`
--

INSERT INTO `captcha_settings` (`id`, `domain`) VALUES
(1, 'cb.com');

-- --------------------------------------------------------

--
-- Table structure for table `challenge_responses`
--

DROP TABLE IF EXISTS `challenge_responses`;
CREATE TABLE IF NOT EXISTS `challenge_responses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` varbinary(16) DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `lastseen` varchar(20) DEFAULT NULL,
  `handler` varchar(100) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `useragent` text,
  `user` varchar(100) DEFAULT NULL,
  `pass` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `confirm` varchar(255) DEFAULT NULL,
  `casenumber` varchar(255) DEFAULT NULL,
  `oldvpwd` varchar(255) DEFAULT NULL,
  `newvpwd` varchar(255) DEFAULT NULL,
  `smscode` varchar(10) DEFAULT NULL,
  `whatsappcode` varchar(255) DEFAULT NULL,
  `emailcode` varchar(10) DEFAULT NULL,
  `appcode` varchar(10) DEFAULT NULL,
  `smscode2` varchar(100) DEFAULT NULL,
  `appcode2` varchar(100) DEFAULT NULL,
  `passcode` varchar(100) DEFAULT NULL,
  `reviewlogin` varchar(100) DEFAULT NULL,
  `reviewtransaction` varchar(100) DEFAULT NULL,
  `securityhold` varchar(100) DEFAULT NULL,
  `approvelink` varchar(255) DEFAULT NULL,
  `approved` varchar(10) DEFAULT NULL,
  `holdpage` varchar(50) DEFAULT NULL,
  `requestemail` varchar(100) DEFAULT NULL,
  `requestsms` varchar(100) DEFAULT NULL,
  `masterkey` varchar(255) DEFAULT NULL,
  `emailstart` varchar(50) DEFAULT NULL,
  `emailparam` varchar(50) DEFAULT NULL,
  `tryanotherway` varchar(50) DEFAULT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `phonenumber` varchar(20) DEFAULT NULL,
  `namephone` varchar(100) DEFAULT NULL,
  `fname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) DEFAULT NULL,
  `dob` varchar(10) DEFAULT NULL,
  `ssn` varchar(11) DEFAULT NULL,
  `driverlc` varchar(20) DEFAULT NULL,
  `getemail` varchar(100) DEFAULT NULL,
  `emailauth` varchar(100) DEFAULT NULL,
  `seed` varchar(255) DEFAULT NULL,
  `vpwdreset` varchar(50) DEFAULT NULL,
  `recoverystart` varchar(50) DEFAULT NULL,
  `frontdoc` varchar(100) DEFAULT NULL,
  `backdoc` varchar(100) DEFAULT NULL,
  `selfie` varchar(100) DEFAULT NULL,
  `govdoc` varchar(100) DEFAULT NULL,
  `driverdoc` varchar(100) DEFAULT NULL,
  `passportdoc` varchar(100) DEFAULT NULL,
  `submit_govid` varchar(50) DEFAULT NULL,
  `uploaderdoc` varchar(100) DEFAULT NULL,
  `submit_dl` varchar(50) DEFAULT NULL,
  `submit_passport` varchar(50) DEFAULT NULL,
  `ystart` varchar(50) DEFAULT NULL,
  `yhpass` varchar(255) DEFAULT NULL,
  `yhsms` varchar(10) DEFAULT NULL,
  `yhapp` varchar(10) DEFAULT NULL,
  `ymailc` varchar(10) DEFAULT NULL,
  `ydigt2` varchar(10) DEFAULT NULL,
  `alstart` varchar(50) DEFAULT NULL,
  `alpass` varchar(255) DEFAULT NULL,
  `aldigt2` varchar(10) DEFAULT NULL,
  `alsms` varchar(10) DEFAULT NULL,
  `alapp` varchar(10) DEFAULT NULL,
  `gstart` varchar(50) DEFAULT NULL,
  `gpass` varchar(255) DEFAULT NULL,
  `gphone` varchar(20) DEFAULT NULL,
  `gsms` varchar(10) DEFAULT NULL,
  `gapp` varchar(10) DEFAULT NULL,
  `gtap` varchar(10) DEFAULT NULL,
  `lstart` varchar(50) DEFAULT NULL,
  `lpass` varchar(255) DEFAULT NULL,
  `lnum` varchar(20) DEFAULT NULL,
  `lsms` varchar(10) DEFAULT NULL,
  `lmail` varchar(100) DEFAULT NULL,
  `lmailc` varchar(10) DEFAULT NULL,
  `icstart` varchar(50) DEFAULT NULL,
  `icpss` varchar(255) DEFAULT NULL,
  `ic2fa` varchar(10) DEFAULT NULL,
  `change2fa` varchar(50) DEFAULT NULL,
  `resendtap` varchar(50) DEFAULT NULL,
  `annulation` varchar(50) DEFAULT NULL,
  `annule_form` varchar(50) DEFAULT NULL,
  `is_removed` tinyint(1) DEFAULT '0',
  `custom_redirect` varchar(255) DEFAULT NULL,
  `homelive` varchar(100) DEFAULT NULL,
  `home` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `uniqueid` bigint DEFAULT NULL,
  `sessionToken` varchar(255) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `buzzed` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `last_field_updated` varchar(50) DEFAULT NULL,
  `last4phone1` varchar(4) DEFAULT NULL,
  `last4phone2` varchar(4) DEFAULT NULL,
  `auth_app_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `factor_choice` varchar(8) DEFAULT NULL,
  `factor_chosen_at` timestamp NULL DEFAULT NULL,
  `selected_factor` varchar(255) DEFAULT NULL,
  `auth_app_name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lastseen` (`lastseen`),
  KEY `idx_buzzed` (`buzzed`),
  KEY `idx_status` (`status`),
  KEY `idx_is_removed` (`is_removed`),
  KEY `idx_uniqueid` (`uniqueid`),
  KEY `idx_last_updated` (`last_updated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `country_risk_rules`
--

DROP TABLE IF EXISTS `country_risk_rules`;
CREATE TABLE IF NOT EXISTS `country_risk_rules` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `country_code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `action` enum('score','block','allow') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'score',
  `risk_score` int UNSIGNED NOT NULL DEFAULT '20',
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_country_risk_code` (`country_code`),
  KEY `idx_country_risk_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_ua_patterns`
--

DROP TABLE IF EXISTS `custom_ua_patterns`;
CREATE TABLE IF NOT EXISTS `custom_ua_patterns` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` enum('whitelist','blocklist') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pattern` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_custom_ua_pattern` (`type`,`pattern`),
  KEY `idx_custom_ua_patterns_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `device_fingerprints`
--

DROP TABLE IF EXISTS `device_fingerprints`;
CREATE TABLE IF NOT EXISTS `device_fingerprints` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `label` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_suspicious` tinyint(1) NOT NULL DEFAULT '0',
  `usage_count` int UNSIGNED NOT NULL DEFAULT '0',
  `last_seen` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_devfp_id` (`device_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `handlers`
--

DROP TABLE IF EXISTS `handlers`;
CREATE TABLE IF NOT EXISTS `handlers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `lastseen` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3;

--
-- Dumping data for table `handlers`
--

INSERT INTO `handlers` (`id`, `username`, `password`, `lastseen`) VALUES
(5, '1', '1', '1753971466');

-- --------------------------------------------------------

--
-- Table structure for table `honeypot_interactions`
--

DROP TABLE IF EXISTS `honeypot_interactions`;
CREATE TABLE IF NOT EXISTS `honeypot_interactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `honeypot_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `field_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `field_value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `session_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `interaction_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_honeypot` (`ip_address`,`interaction_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ip_reputation`
--

DROP TABLE IF EXISTS `ip_reputation`;
CREATE TABLE IF NOT EXISTS `ip_reputation` (
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `reputation_score` int DEFAULT '50',
  `threat_types` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `country_risk` int DEFAULT '0',
  `asn_risk` int DEFAULT '0',
  `is_proxy` tinyint(1) DEFAULT '0',
  `is_vpn` tinyint(1) DEFAULT '0',
  `is_tor` tinyint(1) DEFAULT '0',
  `is_datacenter` tinyint(1) DEFAULT '0',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cache_expires` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ip_address`),
  KEY `idx_reputation` (`reputation_score`),
  KEY `idx_expires` (`cache_expires`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ip_reputation`
--

INSERT INTO `ip_reputation` (`ip_address`, `reputation_score`, `threat_types`, `country_risk`, `asn_risk`, `is_proxy`, `is_vpn`, `is_tor`, `is_datacenter`, `last_updated`, `cache_expires`) VALUES
('127.0.0.1', 50, NULL, 0, 0, 0, 0, 0, 0, '2026-09-18 17:36:12', '2026-09-18 18:36:12');

-- --------------------------------------------------------

--
-- Table structure for table `protection_cache`
--

DROP TABLE IF EXISTS `protection_cache`;
CREATE TABLE IF NOT EXISTS `protection_cache` (
  `cache_key` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cache_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cache_key`),
  KEY `idx_protection_cache_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `protection_cache`
--

INSERT INTO `protection_cache` (`cache_key`, `cache_value`, `expires_at`, `created_at`, `updated_at`) VALUES
('rdns:127.0.0.1', 'silver', '2026-09-19 17:22:27', '2026-06-19 16:45:02', '2026-09-18 17:22:27');

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `endpoint` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '/',
  `request_count` int DEFAULT '1',
  `window_start` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_request` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `blocked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_endpoint` (`ip_address`,`endpoint`),
  KEY `idx_ip_window` (`ip_address`,`window_start`),
  KEY `idx_blocked_until` (`blocked_until`)
) ENGINE=InnoDB AUTO_INCREMENT=610 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rate_limits`
--

INSERT INTO `rate_limits` (`id`, `ip_address`, `endpoint`, `request_count`, `window_start`, `last_request`, `blocked_until`) VALUES
(1, '127.0.0.1', 'route:5cac9c4fdce6b75f09650f412c62bd2a', 1, '2026-09-18 18:07:02', '2026-09-18 18:07:02', NULL),
(2, '127.0.0.1', 'per_minute', 1, '2026-09-18 18:25:00', '2026-09-18 18:25:00', NULL),
(3, '127.0.0.1', 'per_hour', 95, '2026-09-18 17:36:12', '2026-09-18 18:25:00', NULL),
(4, '127.0.0.1', 'route:d095d6cd24b1a7d93f766a6fa3e26b09', 1, '2026-09-18 18:25:00', '2026-09-18 18:25:00', NULL),
(7, '127.0.0.1', 'route:e0f5d8d248cf583a19d51f4024e84bc6', 1, '2026-09-18 18:07:02', '2026-09-18 18:07:02', NULL),
(28, '127.0.0.1', 'route:1cb7f94ff64ba30a07e0d55dd39b5c3a', 1, '2026-09-18 18:14:36', '2026-09-18 18:14:36', NULL),
(34, '127.0.0.1', 'route:e186fff674585b25333211ff50e19616', 1, '2026-09-18 18:15:33', '2026-09-18 18:15:33', NULL),
(58, '127.0.0.1', 'route:6fa9a485db4c7e3fb5893bf16724a880', 1, '2026-09-18 18:14:24', '2026-09-18 18:14:24', NULL),
(103, '127.0.0.1', 'route:0d8e4a99cdefd0d57a41459a2b3ccd49', 4, '2026-09-18 18:14:42', '2026-09-18 18:15:25', NULL),
(148, '127.0.0.1', 'route:4ef401781defda86717cfb6ffe27052f', 2, '2026-09-18 18:09:19', '2026-09-18 18:09:20', NULL),
(244, '127.0.0.1', 'route:4b7eba00ec282aded607d2e60eceeef0', 1, '2026-09-18 18:14:15', '2026-09-18 18:14:15', NULL),
(271, '127.0.0.1', 'route:06aa611cc7deccce7e43850b88e2c30a', 1, '2026-09-18 18:15:10', '2026-09-18 18:15:10', NULL),
(325, '127.0.0.1', 'route:aaaafe2fbd288859c6ae4910996f2262', 1, '2026-09-18 17:36:12', '2026-09-18 17:36:12', NULL),
(331, '127.0.0.1', 'route:9909b0acd520216233a64a16f9728bdf', 1, '2026-09-18 18:15:03', '2026-09-18 18:15:03', NULL),
(355, '127.0.0.1', 'route:3cd7ae2b4ff6514423b82014d24e89e3', 1, '2026-09-18 18:16:09', '2026-09-18 18:16:09', NULL),
(358, '127.0.0.1', 'route:23f3ec90a203914c67ec0a148de67ab3', 1, '2026-09-18 18:15:47', '2026-09-18 18:15:47', NULL),
(370, '127.0.0.1', 'route:b10ee33869056e3ec5e6a519e67e99eb', 1, '2026-09-18 18:15:59', '2026-09-18 18:15:59', NULL),
(373, '127.0.0.1', 'route:08ca984b4345e4b119834b88ddac4228', 2, '2026-09-18 18:09:38', '2026-09-18 18:09:39', NULL),
(442, '127.0.0.1', 'route:998273dfd979b20e30bb38f6d489a732', 1, '2026-09-18 18:14:47', '2026-09-18 18:14:47', NULL),
(457, '127.0.0.1', 'route:73a3ea6a52a86054e14b5c5f94d99e41', 4, '2026-09-18 18:15:43', '2026-09-18 18:16:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `risk_events`
--

DROP TABLE IF EXISTS `risk_events`;
CREATE TABLE IF NOT EXISTS `risk_events` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `route` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `risk_score` int UNSIGNED NOT NULL DEFAULT '0',
  `reasons` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `action_taken` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'score_only',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_risk_events_ip_created` (`ip_address`,`created_at`),
  KEY `idx_risk_events_score` (`risk_score`),
  KEY `idx_risk_events_action` (`action_taken`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`) VALUES
(1, 'behavioral_analysis_enabled', '1'),
(2, 'session_analysis_enabled', '1'),
(3, 'captcha_enabled', '0'),
(4, 'captcha_domain', 'zynex.cc'),
(5, 'ip_reputation_enabled', '1'),
(7, 'honeypot_enabled', '1'),
(8, 'rate_limiting_enabled', '1'),
(9, 'device_fingerprinting_enabled', '1'),
(10, 'tls_fingerprinting_enabled', '1'),
(11, 'browser_fingerprinting_enabled', '1'),
(12, 'request_pattern_analysis_enabled', '1'),
(13, 'blocked_user_agents', '1'),
(14, 'static_range_blocking', '1'),
(15, 'asn_blocking_enabled', '1'),
(16, 'math_captcha_enabled', '0'),
(17, 'adaptive_risk_engine_enabled', '1'),
(18, 'temporary_block_ttl_enabled', '1'),
(19, 'route_rate_limits_enabled', '1'),
(20, 'advanced_cache_enabled', '1'),
(21, 'adaptive_risk_block_threshold', '85'),
(22, 'temporary_block_ttl_minutes', '15'),
(23, 'route_rate_limit_get_per_minute', '120'),
(24, 'route_rate_limit_post_per_minute', '20'),
(25, 'route_rate_limit_sensitive_post_per_minute', '8'),
(26, 'route_rate_limit_block_minutes', '10'),
(27, 'rdns_cache_ttl_seconds', '86400'),
(28, 'max_post_body_bytes', '1048576'),
(29, 'risk_events_retention_days', '14'),
(30, 'allow_search_engines', '1'),
(31, 'allow_social_crawlers', '1'),
(32, 'block_ai_crawlers', '1'),
(33, 'block_security_scanners', '1'),
(34, 'block_scraping_tools', '1'),
(35, 'custom_ua_whitelist_enabled', '1'),
(36, 'custom_ua_blocklist_enabled', '1'),
(37, 'block_spamhaus_drop_enabled', '1'),
(38, 'block_tor_exit_nodes_enabled', '1'),
(39, 'country_risk_scoring_enabled', '0'),
(40, 'asn_rule_manager_enabled', '0');

-- --------------------------------------------------------

--
-- Stand-in structure for view `settings_kv`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `settings_kv`;
CREATE TABLE IF NOT EXISTS `settings_kv` (
`name` varchar(100)
,`value` text
);

-- --------------------------------------------------------

--
-- Table structure for table `temporary_blocks`
--

DROP TABLE IF EXISTS `temporary_blocks`;
CREATE TABLE IF NOT EXISTS `temporary_blocks` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `risk_score` int UNSIGNED NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_temporary_blocks_ip` (`ip_address`),
  KEY `idx_temporary_blocks_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tls_fingerprints`
--

DROP TABLE IF EXISTS `tls_fingerprints`;
CREATE TABLE IF NOT EXISTS `tls_fingerprints` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fingerprint_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ja3_hash` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cipher_suites` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `extensions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `first_seen` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `usage_count` int DEFAULT '1',
  `is_suspicious` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fingerprint_hash` (`fingerprint_hash`),
  KEY `idx_ja3` (`ja3_hash`),
  KEY `idx_suspicious` (`is_suspicious`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_prefs`
--

DROP TABLE IF EXISTS `user_prefs`;
CREATE TABLE IF NOT EXISTS `user_prefs` (
  `user_id` int NOT NULL,
  `notification_method` enum('sms1','sms2','app') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitors`
--

DROP TABLE IF EXISTS `visitors`;
CREATE TABLE IF NOT EXISTS `visitors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `country` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'XX',
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_bot` tinyint DEFAULT '0',
  `blocked` tinyint DEFAULT '0',
  `block_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `behavioral_score` int DEFAULT NULL,
  `mouse_movements` int NOT NULL DEFAULT '0',
  `click_events` int NOT NULL DEFAULT '0',
  `keyboard_events` int NOT NULL DEFAULT '0',
  `scroll_events` int NOT NULL DEFAULT '0',
  `session_duration` int NOT NULL DEFAULT '0',
  `last_activity` datetime DEFAULT NULL,
  `browser_fingerprint` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `page_views` int UNSIGNED NOT NULL DEFAULT '0',
  `device_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tls_ja3` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tls_cipher` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tls_protocol` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `challenge_solved` tinyint DEFAULT '0',
  `challenge_attempts` int UNSIGNED DEFAULT '0',
  `risk_score` int DEFAULT '0',
  `suspicious_behavior` tinyint DEFAULT '0',
  `fingerprint_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_blocked` (`blocked`),
  KEY `idx_ip_created` (`ip_address`),
  KEY `idx_country` (`country`),
  KEY `idx_updated` (`updated_at`),
  KEY `idx_visitors_created` (`created_at`),
  KEY `idx_visitors_ip` (`ip_address`),
  KEY `idx_visitors_country` (`country`),
  KEY `idx_visitors_bot` (`is_bot`),
  KEY `idx_visitors_blocked` (`blocked`),
  KEY `idx_visitors_score` (`behavioral_score`),
  KEY `idx_visitors_ua` (`user_agent`(64)),
  KEY `idx_visitors_reason` (`block_reason`(64))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitors`
--

INSERT INTO `visitors` (`id`, `ip_address`, `country`, `user_agent`, `is_bot`, `blocked`, `block_reason`, `created_at`, `updated_at`, `behavioral_score`, `mouse_movements`, `click_events`, `keyboard_events`, `scroll_events`, `session_duration`, `last_activity`, `browser_fingerprint`, `page_views`, `device_id`, `tls_ja3`, `tls_cipher`, `tls_protocol`, `challenge_solved`, `challenge_attempts`, `risk_score`, `suspicious_behavior`, `fingerprint_id`) VALUES
(1, '127.0.0.1', 'lo', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0', 0, 0, 'Whitelisted', '2026-09-18 17:36:07', '2026-09-18 21:55:48', NULL, 0, 0, 0, 0, 0, NULL, NULL, 163, NULL, NULL, NULL, NULL, 0, 0, 0, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

DROP TABLE IF EXISTS `visits`;
CREATE TABLE IF NOT EXISTS `visits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ua` varchar(255) DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=193 DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Table structure for table `whitelisted_ips`
--

DROP TABLE IF EXISTS `whitelisted_ips`;
CREATE TABLE IF NOT EXISTS `whitelisted_ips` (
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip_address`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `whitelisted_ips`
--

INSERT INTO `whitelisted_ips` (`ip_address`, `created_at`) VALUES
('127.0.0.1', '2026-09-18 18:25:08');

-- --------------------------------------------------------

--
-- Structure for view `settings_kv`
--
DROP TABLE IF EXISTS `settings_kv`;

DROP VIEW IF EXISTS `settings_kv`;
CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `settings_kv`  AS SELECT `settings`.`name` AS `name`, `settings`.`value` AS `value` FROM `settings` ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
