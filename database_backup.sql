-- Inventory Management System Database Backup
-- Generated: 2026-01-02 12:25:09
-- Host: localhost
-- Database: pmsdb

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- 
-- Table structure for table `attribute_values` --
--

DROP TABLE IF EXISTS `attribute_values`;

CREATE TABLE `attribute_values` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attribute_id` int NOT NULL,
  `value` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `attribute_id` (`attribute_id`),
  CONSTRAINT `attribute_values_ibfk_1` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=156 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `attribute_values` --
--

INSERT INTO `attribute_values` VALUES("1","1","XS","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("2","1","S","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("3","1","M","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("4","1","L","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("5","1","XL","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("6","1","XXL","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("7","1","XXXL","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("8","1","34","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("9","1","35","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("10","1","36","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("11","1","37","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("12","1","38","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("13","1","39","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("14","1","40","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("15","1","41","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("16","1","42","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("17","2","YellowGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("18","2","Yellow","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("19","2","WhiteSmoke","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("20","2","White","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("21","2","Wheat","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("22","2","Violet","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("23","2","Turquoise","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("24","2","Tomato","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("25","2","Thistle","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("26","2","Teal","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("27","2","Tan","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("28","2","SteelBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("29","2","SpringGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("30","2","Snow","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("31","2","SlateGray","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("32","2","SlateBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("33","2","SkyBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("34","2","Silver","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("35","2","Sienna","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("36","2","Seashell","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("37","2","SeaGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("38","2","SandyBrown","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("39","2","Salmon","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("40","2","SaddleBrown","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("41","2","RoyalBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("42","2","RosyBrown","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("43","2","Red","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("44","2","Purple","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("45","2","PowderBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("46","2","Plum","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("47","2","Pink","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("48","2","Peru","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("49","2","PeachPuff","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("50","2","PapayaWhip","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("51","2","PaleVioletRed","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("52","2","PaleTurquoise","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("53","2","PaleGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("54","2","PaleGoldenrod","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("55","2","Orchid","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("56","2","OrangeRed","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("57","2","Orange","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("58","2","OliveDrab","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("59","2","Olive","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("60","2","OldLace","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("61","2","Navy","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("62","2","NavajoWhite","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("63","2","Moccasin","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("64","2","MistyRose","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("65","2","MintCream","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("66","2","MidnightBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("67","2","MediumVioletRed","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("68","2","MediumTurquoise","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("69","2","MediumSpringGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("70","2","MediumSlateBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("71","2","MediumSeaGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("72","2","MediumPurple","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("73","2","MediumOrchid","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("74","2","MediumBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("75","2","MediumAquamarine","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("76","2","Maroon","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("77","2","Magenta","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("78","2","Linen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("79","2","LimeGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("80","2","Lime","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("81","2","LightYellow","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("82","2","LightSteelBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("83","2","LightSlateGray","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("84","2","LightSkyBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("85","2","LightSeaGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("86","2","LightSalmon","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("87","2","LightPink","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("88","2","LightGrey","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("89","2","LightGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("90","2","LightGoldenrodYellow","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("91","2","LightCyan","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("92","2","LightCoral","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("93","2","LightBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("94","2","LemonChiffon","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("95","2","LawnGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("96","2","LavenderBlush","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("97","2","Lavender","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("98","2","Khaki","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("99","2","Ivory","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("100","2","Indigo","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("101","2","IndianRed","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("102","2","HotPink","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("103","2","Honeydew","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("104","2","GreenYellow","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("105","2","Green","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("106","2","Gray","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("107","2","Goldenrod","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("108","2","Gold","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("109","2","GhostWhite","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("110","2","Gainsboro","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("111","2","ForestGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("112","2","FloralWhite","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("113","2","FireBrick","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("114","2","DodgerBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("115","2","DimGray","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("116","2","DeepSkyBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("117","2","DeepPink","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("118","2","DarkViolet","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("119","2","DarkTurquoise","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("120","2","DarkSlateGray","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("121","2","DarkSlateBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("122","2","DarkSeaGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("123","2","DarkSalmon","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("124","2","DarkRed","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("125","2","DarkOrchid","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("126","2","DarkOrange","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("127","2","DarkOliveGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("128","2","DarkMagenta","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("129","2","DarkKhaki","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("130","2","DarkGreen","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("131","2","DarkGray","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("132","2","DarkGoldenrod","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("133","2","DarkCyan","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("134","2","DarkBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("135","2","Crimson","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("136","2","Cornsilk","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("137","2","CornflowerBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("138","2","Coral","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("139","2","Chocolate","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("140","2","Chartreuse","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("141","2","CadetBlue","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("142","2","BurlyWood","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("143","2","Brown","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attribute_values` VALUES("144","2","BlueViolet","2025-12-07 15:43:00","2025-12-07 15:43:00");
INSERT INTO `attribute_values` VALUES("145","2","Blue","2025-12-07 15:43:00","2025-12-07 15:43:00");
INSERT INTO `attribute_values` VALUES("146","2","BlanchedAlmond","2025-12-07 15:43:00","2025-12-07 15:43:00");
INSERT INTO `attribute_values` VALUES("147","2","Black","2025-12-07 15:43:00","2025-12-07 15:43:00");
INSERT INTO `attribute_values` VALUES("148","2","Bisque","2025-12-07 15:43:00","2025-12-07 15:43:00");
INSERT INTO `attribute_values` VALUES("149","2","Beige","2025-12-07 15:43:00","2025-12-07 15:43:00");
INSERT INTO `attribute_values` VALUES("150","2","Azure","2025-12-07 15:43:00","2025-12-07 15:43:00");
INSERT INTO `attribute_values` VALUES("151","2","Aquamarine","2025-12-07 15:43:00","2025-12-07 15:43:00");
INSERT INTO `attribute_values` VALUES("152","2","Aqua","2025-12-07 15:43:00","2025-12-07 15:43:00");
INSERT INTO `attribute_values` VALUES("153","2","AntiqueWhite","2025-12-07 15:43:00","2025-12-07 15:43:00");
INSERT INTO `attribute_values` VALUES("154","2","Amethyst","2025-12-07 15:43:00","2025-12-07 15:43:00");
INSERT INTO `attribute_values` VALUES("155","2","AliceBlue","2025-12-07 15:43:00","2025-12-07 15:43:00");



-- 
-- Table structure for table `attributes` --
--

DROP TABLE IF EXISTS `attributes`;

CREATE TABLE `attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `attributes` --
--

INSERT INTO `attributes` VALUES("1","Size","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attributes` VALUES("2","Color","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attributes` VALUES("3","Material","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attributes` VALUES("4","Fit/Style","2025-12-07 15:42:59","2025-12-07 15:42:59");
INSERT INTO `attributes` VALUES("5","Features","2025-12-07 15:42:59","2025-12-07 15:42:59");



-- 
-- Table structure for table `audit_logs` --
--

DROP TABLE IF EXISTS `audit_logs`;

CREATE TABLE `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `audit_logs` --
--

INSERT INTO `audit_logs` VALUES("1",NULL,"LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-03 11:33:22");
INSERT INTO `audit_logs` VALUES("2",NULL,"LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-03 21:18:29");
INSERT INTO `audit_logs` VALUES("3","7","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-04 12:58:35");
INSERT INTO `audit_logs` VALUES("4","7","PRODUCT_ADD",NULL,NULL,"Added product: rrrrrr (SKU: 2222222222)",NULL,"2025-12-04 13:30:45");
INSERT INTO `audit_logs` VALUES("5","7","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-05 18:57:39");
INSERT INTO `audit_logs` VALUES("6","7","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-05 18:58:54");
INSERT INTO `audit_logs` VALUES("7","7","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-05 18:59:43");
INSERT INTO `audit_logs` VALUES("8",NULL,"LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-05 19:02:26");
INSERT INTO `audit_logs` VALUES("9","7","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-05 19:38:19");
INSERT INTO `audit_logs` VALUES("10","7","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-06 07:24:19");
INSERT INTO `audit_logs` VALUES("11","7","PRODUCT_ADD",NULL,NULL,"Added product: Women\'s high waisted Yoga pants (SKU: 2222222222)",NULL,"2025-12-06 09:52:07");
INSERT INTO `audit_logs` VALUES("12","7","PRODUCT_ADD",NULL,NULL,"Added product: Alexandranx Elegant Minimalist Daily Wear Unique Solid Color Short Sleeve Fitted (SKU: 23423)",NULL,"2025-12-06 09:53:40");
INSERT INTO `audit_logs` VALUES("13","7","STOCK_OUT",NULL,NULL,"Removed 1 from product ID 6. Reason: Other",NULL,"2025-12-06 10:00:36");
INSERT INTO `audit_logs` VALUES("14","7","STOCK_IN",NULL,NULL,"Added 43 to product ID 6. Reason: Supplier Delivery",NULL,"2025-12-06 10:02:28");
INSERT INTO `audit_logs` VALUES("15","7","STOCK_OUT",NULL,NULL,"Removed 82 from product ID 6. Reason: Sale",NULL,"2025-12-06 10:23:35");
INSERT INTO `audit_logs` VALUES("16","7","STOCK_OUT",NULL,NULL,"Removed 20 from product ID 5. Reason: Adjustment",NULL,"2025-12-06 10:26:48");
INSERT INTO `audit_logs` VALUES("17","7","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-06 10:27:10");
INSERT INTO `audit_logs` VALUES("18","7","STOCK_OUT",NULL,NULL,"Removed 50 from product ID 6. Reason: Sale",NULL,"2025-12-06 10:39:29");
INSERT INTO `audit_logs` VALUES("19","7","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-06 13:23:08");
INSERT INTO `audit_logs` VALUES("20","7","PRODUCT_ADD",NULL,NULL,"Added product: Off-the-Shoulder Party dress (SKU: PRD-1765099969-483)",NULL,"2025-12-07 10:04:59");
INSERT INTO `audit_logs` VALUES("21","7","STOCK_OUT",NULL,NULL,"Removed 1 from product ID 8. Reason: Lost",NULL,"2025-12-07 11:14:34");
INSERT INTO `audit_logs` VALUES("22","7","STOCK_IN",NULL,NULL,"Added 1 to product ID 8. Reason: Return",NULL,"2025-12-07 11:14:58");
INSERT INTO `audit_logs` VALUES("23","7","STOCK_OUT",NULL,NULL,"Removed 2 from product ID 8. Reason: Damaged",NULL,"2025-12-07 11:18:24");
INSERT INTO `audit_logs` VALUES("24","7","PRODUCT_UPDATE",NULL,NULL,"Updated product: Off-the-Shoulder Party dress (ID: 8)",NULL,"2025-12-07 11:23:43");
INSERT INTO `audit_logs` VALUES("25","7","PRODUCT_ADD",NULL,NULL,"Added product: Alexandranx Elegant Minimalist Daily Wear Unique Solid Color Short Sleeve Fitted (SKU: PRD-1765121200-489)",NULL,"2025-12-07 15:28:12");
INSERT INTO `audit_logs` VALUES("26","7","PRODUCT_ADD",NULL,NULL,"Added product: Alexandranx Elegant Minimalist Daily Wear Unique Solid Color Short Sleeve Fitted (SKU: PRD-1765125180-519)",NULL,"2025-12-07 16:33:53");
INSERT INTO `audit_logs` VALUES("27","7","PRODUCT_ADD",NULL,NULL,"Added product: test2 (SKU: PRD-1765125367-840)",NULL,"2025-12-07 16:36:59");
INSERT INTO `audit_logs` VALUES("28","7","PRODUCT_ADD",NULL,NULL,"Added product: Off-the-Shoulder Party dress (SKU: PRD-1765127726-386)",NULL,"2025-12-07 17:16:29");
INSERT INTO `audit_logs` VALUES("29","7","PRODUCT_ADD",NULL,NULL,"Added product: Elegant Off-Shoulder Ruffle Hem Black Dress (SKU: PRD-1765181024-611)",NULL,"2025-12-08 08:05:55");
INSERT INTO `audit_logs` VALUES("30","7","PRODUCT_ADD",NULL,NULL,"Added product: Classic Red Bodycon Party Dress (SKU: PRD-1765181170-420)",NULL,"2025-12-08 08:06:53");
INSERT INTO `audit_logs` VALUES("31","7","PRODUCT_ADD",NULL,NULL,"Added product: The Power Play Sheath Dress - Ruby Red Cutout Neck (SKU: PRD-1765181225-685)",NULL,"2025-12-08 08:07:45");
INSERT INTO `audit_logs` VALUES("32","7","PRODUCT_ADD",NULL,NULL,"Added product: Elegant V-Neck Floral Print A-Line Maxi Dress with Pockets (SKU: PRD-1765181278-988)",NULL,"2025-12-08 08:08:33");
INSERT INTO `audit_logs` VALUES("33","7","PRODUCT_ADD",NULL,NULL,"Added product: ChicMe Sexy Party Midi Dress (SKU: PRD-1765181327-438)",NULL,"2025-12-08 08:09:23");
INSERT INTO `audit_logs` VALUES("34","7","PRODUCT_ADD",NULL,NULL,"Added product: The &#039;Chevron Cruise&#039; 3-Piece Leisure Set (SKU: PRD-1765181391-193)",NULL,"2025-12-08 08:10:27");
INSERT INTO `audit_logs` VALUES("35","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;S Elegant Off-Shoulder Polka Dot Jumpsuit (SKU: PRD-1765181437-363)",NULL,"2025-12-08 08:11:15");
INSERT INTO `audit_logs` VALUES("36","7","PRODUCT_ADD",NULL,NULL,"Added product: Maxi Dress (SKU: PRD-1765181484-712)",NULL,"2025-12-08 08:11:58");
INSERT INTO `audit_logs` VALUES("37","7","PRODUCT_ADD",NULL,NULL,"Added product: Elegant Black Mesh Bodycon Midi Dress with Deep V-Neck &amp; Lace Detailing (SKU: PRD-1765181538-103)",NULL,"2025-12-08 08:13:06");
INSERT INTO `audit_logs` VALUES("38","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s Mesh Leggings, Tights, Breathable (SKU: PRD-1765181596-363)",NULL,"2025-12-08 08:13:54");
INSERT INTO `audit_logs` VALUES("39","7","PRODUCT_ADD",NULL,NULL,"Added product: Teen Casual Pajama Sets (SKU: PRD-1765181644-490)",NULL,"2025-12-08 08:14:35");
INSERT INTO `audit_logs` VALUES("40","7","PRODUCT_ADD",NULL,NULL,"Added product: Pajama, Cute Cherry Print Spaghetti Strap Nightgowns for Teens (SKU: PRD-1765181693-343)",NULL,"2025-12-08 08:15:25");
INSERT INTO `audit_logs` VALUES("41","7","PRODUCT_ADD",NULL,NULL,"Added product: Elegant of shoulder a line Maxi Dress (SKU: PRD-1765181738-988)",NULL,"2025-12-08 08:16:39");
INSERT INTO `audit_logs` VALUES("42","7","PRODUCT_ADD",NULL,NULL,"Added product: Two-Piece Crop Top and Wide-Leg Pant Set (SKU: PRD-1765181809-820)",NULL,"2025-12-08 08:17:26");
INSERT INTO `audit_logs` VALUES("43","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s Denim Maxi dress (SKU: PRD-1765181859-257)",NULL,"2025-12-08 08:18:15");
INSERT INTO `audit_logs` VALUES("44","7","PRODUCT_ADD",NULL,NULL,"Added product: Floral print Teired ruched elastic waist vacation skirt (SKU: PRD-1765181907-827)",NULL,"2025-12-08 08:19:11");
INSERT INTO `audit_logs` VALUES("45","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s Maxi skirt tiny floral casual Elastic waist with pockets ruffeled hem la (SKU: PRD-1765181956-991)",NULL,"2025-12-08 08:19:55");
INSERT INTO `audit_logs` VALUES("46","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s solid color simple daily slit skirt (SKU: PRD-1765182006-175)",NULL,"2025-12-08 08:21:05");
INSERT INTO `audit_logs` VALUES("47","7","PRODUCT_ADD",NULL,NULL,"Added product: Denim Skirt (SKU: PRD-1765182077-640)",NULL,"2025-12-08 08:21:53");
INSERT INTO `audit_logs` VALUES("48","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s Elegant Black and white Abstract print pencil dress (SKU: PRD-1765182123-178)",NULL,"2025-12-08 08:22:42");
INSERT INTO `audit_logs` VALUES("49","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s Elegant summer off shoulder Beaded body suit with high waist wide leg De (SKU: PRD-1765182177-336)",NULL,"2025-12-08 08:23:32");
INSERT INTO `audit_logs` VALUES("50","7","PRODUCT_ADD",NULL,NULL,"Added product: Black and White Geometric wide leg jumpsuit (SKU: PRD-1765182225-730)",NULL,"2025-12-08 08:24:20");
INSERT INTO `audit_logs` VALUES("51","7","PRODUCT_ADD",NULL,NULL,"Added product: Cherry Embroidered crop knit cardigan (SKU: PRD-1765182278-581)",NULL,"2025-12-08 08:25:24");
INSERT INTO `audit_logs` VALUES("52","7","PRODUCT_ADD",NULL,NULL,"Added product: Chic Color Block Floral Cardigan - V-Neck (SKU: PRD-1765182341-108)",NULL,"2025-12-08 08:26:18");
INSERT INTO `audit_logs` VALUES("53","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;S Purple Cable Cardigan with Cherry &amp; Leaf Embellishments (SKU: PRD-1765182392-239)",NULL,"2025-12-08 08:27:10");
INSERT INTO `audit_logs` VALUES("54","7","PRODUCT_ADD",NULL,NULL,"Added product: Elenzga women&#039;s spring/summer French chic Elegant minimalist commuting/office (SKU: PRD-1765182451-682)",NULL,"2025-12-08 08:28:05");
INSERT INTO `audit_logs` VALUES("55","7","PRODUCT_ADD",NULL,NULL,"Added product: MIUSOL solid Asymmetrical Sleeve Ruffle Trim Cocktail Party Fitted Dress (SKU: PRD-1765182507-215)",NULL,"2025-12-08 08:29:04");
INSERT INTO `audit_logs` VALUES("56","7","PRODUCT_ADD",NULL,NULL,"Added product: Amorya Solid Scallop Trim Dress With Belt (SKU: PRD-1765182556-485)",NULL,"2025-12-08 08:29:51");
INSERT INTO `audit_logs` VALUES("57","7","PRODUCT_ADD",NULL,NULL,"Added product: Elengza New Fashionable Solid RED Mini collar Sleeveless Sexy hollow-out Mesh (SKU: PRD-1765182605-569)",NULL,"2025-12-08 08:30:41");
INSERT INTO `audit_logs` VALUES("58","7","PRODUCT_ADD",NULL,NULL,"Added product: Elengza New women Hollow out collar color block tie waist side Ruched hem sleeve (SKU: PRD-1765182653-727)",NULL,"2025-12-08 08:31:23");
INSERT INTO `audit_logs` VALUES("59","7","PRODUCT_ADD",NULL,NULL,"Added product: Selianne french Elegant Casual Vacation square Neck Lotus Leaf Flying sleeves po (SKU: PRD-1765182706-971)",NULL,"2025-12-08 08:32:23");
INSERT INTO `audit_logs` VALUES("60","7","PRODUCT_ADD",NULL,NULL,"Added product: Modelyn Women&#039;s Elegant Floral Print pleated sleeveless Casual Dress (SKU: PRD-1765182751-269)",NULL,"2025-12-08 08:33:19");
INSERT INTO `audit_logs` VALUES("61","7","PRODUCT_ADD",NULL,NULL,"Added product: Modelyn Women&#039;s solid color sleeveless Ruffle lace Trim Bodycon Dress Long Eveni (SKU: PRD-1765182824-792)",NULL,"2025-12-08 08:34:23");
INSERT INTO `audit_logs` VALUES("62","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s high waisted Yoga pants (SKU: PRD-1765182874-126)",NULL,"2025-12-08 08:35:09");
INSERT INTO `audit_logs` VALUES("63","7","PRODUCT_ADD",NULL,NULL,"Added product: Off-the-Shoulder Party dress (SKU: PRD-1765182919-516)",NULL,"2025-12-08 08:35:54");
INSERT INTO `audit_logs` VALUES("64","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s Elegant A-Line Short Sleeve Dress with Geometric Print &amp; Waist Tie Belt (SKU: PRD-1765182970-406)",NULL,"2025-12-08 08:37:07");
INSERT INTO `audit_logs` VALUES("65","7","PRODUCT_ADD",NULL,NULL,"Added product: womens Elegant sleeveless jamsuit with stand collar (SKU: PRD-1765183051-892)",NULL,"2025-12-08 08:38:08");
INSERT INTO `audit_logs` VALUES("66","7","PRODUCT_ADD",NULL,NULL,"Added product: Alexandranx Elegant Minimalist Daily Wear Unique Solid Color Short Sleeve Fitted (SKU: PRD-1765183113-160)",NULL,"2025-12-08 08:39:29");
INSERT INTO `audit_logs` VALUES("67","7","PRODUCT_ADD",NULL,NULL,"Added product: Miaspire 2pcs Women Solid Color Crew Neck 3/4 Sleeve Top And Pants Set (SKU: PRD-1765183188-894)",NULL,"2025-12-08 08:40:37");
INSERT INTO `audit_logs` VALUES("68","7","PRODUCT_ADD",NULL,NULL,"Added product: Privé Guipure Lace Panel Bodycon Dress (SKU: PRD-1765183252-939)",NULL,"2025-12-08 08:41:30");
INSERT INTO `audit_logs` VALUES("69","7","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-08 12:25:16");
INSERT INTO `audit_logs` VALUES("70","7","PRODUCT_ADD",NULL,NULL,"Added product: test123 (SKU: PRD-XDCM2H5F)",NULL,"2025-12-08 12:35:52");
INSERT INTO `audit_logs` VALUES("71","19","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-10 14:07:39");
INSERT INTO `audit_logs` VALUES("72","7","bulk_delete_products",NULL,NULL,"Bulk deleted 10 products",NULL,"2025-12-17 07:49:19");
INSERT INTO `audit_logs` VALUES("73","7","bulk_delete_products",NULL,NULL,"Bulk deleted 10 products",NULL,"2025-12-17 07:49:27");
INSERT INTO `audit_logs` VALUES("74","7","bulk_delete_products",NULL,NULL,"Bulk deleted 10 products",NULL,"2025-12-17 07:49:33");
INSERT INTO `audit_logs` VALUES("75","7","bulk_delete_products",NULL,NULL,"Bulk deleted 10 products",NULL,"2025-12-17 07:49:41");
INSERT INTO `audit_logs` VALUES("76","7","PRODUCT_ADD",NULL,NULL,"Added product: Elegant Off-Shoulder Ruffle Hem Black Dress (SKU: 176595814091)",NULL,"2025-12-17 07:55:40");
INSERT INTO `audit_logs` VALUES("77","7","PRODUCT_ADD",NULL,NULL,"Added product: Elegant V-Neck Floral Print A-Line Maxi Dress with Pockets (SKU: 176595858538)",NULL,"2025-12-17 08:03:05");
INSERT INTO `audit_logs` VALUES("78","7","PRODUCT_ADD",NULL,NULL,"Added product: Elegant of shoulder a line Maxi Dress (SKU: 176595882991)",NULL,"2025-12-17 08:07:09");
INSERT INTO `audit_logs` VALUES("79","7","PRODUCT_ADD",NULL,NULL,"Added product: Elengza New women Hollow out collar color block tie waist side Ruched hem sleeve (SKU: 176595904099)",NULL,"2025-12-17 08:10:40");
INSERT INTO `audit_logs` VALUES("80","7","PRODUCT_ADD",NULL,NULL,"Added product: Modelyn Women&#039;s solid color sleeveless Ruffle lace Trim Bodycon Dress Long Eveni (SKU: 176595922369)",NULL,"2025-12-17 08:13:43");
INSERT INTO `audit_logs` VALUES("81","7","PRODUCT_ADD",NULL,NULL,"Added product: Maxi Dress (SKU: 176595949717)",NULL,"2025-12-17 08:18:17");
INSERT INTO `audit_logs` VALUES("82","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s Elegant Black and white Abstract print pencil dress (SKU: 176595961986)",NULL,"2025-12-17 08:20:19");
INSERT INTO `audit_logs` VALUES("83","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s Elegant A-Line Short Sleeve Dress with Geometric Print &amp; Waist Tie Belt (SKU: 176596003579)",NULL,"2025-12-17 08:27:15");
INSERT INTO `audit_logs` VALUES("84","7","PRODUCT_ADD",NULL,NULL,"Added product: womens Elegant sleeveless jamsuit with stand collar (SKU: 176596053994)",NULL,"2025-12-17 08:35:39");
INSERT INTO `audit_logs` VALUES("85","7","PRODUCT_ADD",NULL,NULL,"Added product: Two-Piece Crop Top and Wide-Leg Pant Set (SKU: 176596082369)",NULL,"2025-12-17 08:40:23");
INSERT INTO `audit_logs` VALUES("86","7","PRODUCT_ADD",NULL,NULL,"Added product: Selianne french Elegant Casual Vacation square Neck Lotus Leaf Flying sleeves po (SKU: 176596112202)",NULL,"2025-12-17 08:45:22");
INSERT INTO `audit_logs` VALUES("87","7","PRODUCT_ADD",NULL,NULL,"Added product: MIUSOL solid Asymmetrical Sleeve Ruffle Trim Cocktail Party Fitted Dress (SKU: 176596193133)",NULL,"2025-12-17 08:58:51");
INSERT INTO `audit_logs` VALUES("88","7","PRODUCT_ADD",NULL,NULL,"Added product: Amorya Solid Scallop Trim Dress With Belt (SKU: 176596231026)",NULL,"2025-12-17 09:05:10");
INSERT INTO `audit_logs` VALUES("89","7","PRODUCT_ADD",NULL,NULL,"Added product: Alexandranx Elegant Minimalist Daily Wear Unique Solid Color Short Sleeve Fitted (SKU: 176596269500)",NULL,"2025-12-17 09:11:35");
INSERT INTO `audit_logs` VALUES("90","7","PRODUCT_ADD",NULL,NULL,"Added product: Floral print Teired ruched elastic waist vacation skirt (SKU: 176596323844)",NULL,"2025-12-17 09:20:38");
INSERT INTO `audit_logs` VALUES("91","7","PRODUCT_ADD",NULL,NULL,"Added product: Elegant Black Mesh Bodycon Midi Dress with Deep V-Neck &amp; Lace Detailing (SKU: 176596345273)",NULL,"2025-12-17 09:24:12");
INSERT INTO `audit_logs` VALUES("92","7","PRODUCT_ADD",NULL,NULL,"Added product: The Power Play Sheath Dress - Ruby Red Cutout Neck (SKU: 176596374853)",NULL,"2025-12-17 09:29:08");
INSERT INTO `audit_logs` VALUES("93","7","PRODUCT_ADD",NULL,NULL,"Added product: Elengza New Fashionable Solid RED Mini collar Sleeveless Sexy hollow-out Mesh (SKU: 176596387884)",NULL,"2025-12-17 09:31:18");
INSERT INTO `audit_logs` VALUES("94","7","PRODUCT_ADD",NULL,NULL,"Added product: Elenzga women&#039;s spring/summer French chic Elegant minimalist commuting/office (SKU: 176596447168)",NULL,"2025-12-17 09:41:11");
INSERT INTO `audit_logs` VALUES("95","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;S Elegant Off-Shoulder Polka Dot Jumpsuit (SKU: 176596462840)",NULL,"2025-12-17 09:43:48");
INSERT INTO `audit_logs` VALUES("96","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s Denim Maxi dress (SKU: 176596479954)",NULL,"2025-12-17 09:46:39");
INSERT INTO `audit_logs` VALUES("97","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s solid color simple daily slit skirt (SKU: 176596510800)",NULL,"2025-12-17 09:51:48");
INSERT INTO `audit_logs` VALUES("98","7","PRODUCT_ADD",NULL,NULL,"Added product: ChicMe Sexy Party Midi Dress (SKU: 176596724238)",NULL,"2025-12-17 10:27:22");
INSERT INTO `audit_logs` VALUES("99","7","PRODUCT_ADD",NULL,NULL,"Added product: Privé Guipure Lace Panel Bodycon Dress (SKU: 176596736569)",NULL,"2025-12-17 10:29:25");
INSERT INTO `audit_logs` VALUES("100","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s high waisted Yoga pants (SKU: 176596770945)",NULL,"2025-12-17 10:35:09");
INSERT INTO `audit_logs` VALUES("101","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s Mesh Leggings, Tights, Breathable (SKU: 176596791162)",NULL,"2025-12-17 10:38:31");
INSERT INTO `audit_logs` VALUES("102","7","PRODUCT_ADD",NULL,NULL,"Added product: The &#039;Chevron Cruise&#039; 3-Piece Leisure Set (SKU: 176596822262)",NULL,"2025-12-17 10:43:42");
INSERT INTO `audit_logs` VALUES("103","7","PRODUCT_ADD",NULL,NULL,"Added product: Classic Red Bodycon Party Dress (SKU: 176596830267)",NULL,"2025-12-17 10:45:02");
INSERT INTO `audit_logs` VALUES("104","7","PRODUCT_ADD",NULL,NULL,"Added product: test product (SKU: 176604922885)",NULL,"2025-12-18 09:14:06");
INSERT INTO `audit_logs` VALUES("105","7","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-18 13:22:33");
INSERT INTO `audit_logs` VALUES("106","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s high waisted Yoga pants (SKU: 176606868570)",NULL,"2025-12-18 14:38:05");
INSERT INTO `audit_logs` VALUES("107","7","PRODUCT_ADD",NULL,NULL,"Added product: Off-the-Shoulder Party dress (SKU: 176607240566)",NULL,"2025-12-18 15:40:05");
INSERT INTO `audit_logs` VALUES("108","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;S Purple Cable Cardigan with Cherry &amp; Leaf Embellishments (SKU: 176607266924)",NULL,"2025-12-18 15:44:29");
INSERT INTO `audit_logs` VALUES("109","7","PRODUCT_ADD",NULL,NULL,"Added product: Cherry Embroidered crop knit cardigan (SKU: 176607285796)",NULL,"2025-12-18 15:47:37");
INSERT INTO `audit_logs` VALUES("110","7","PRODUCT_ADD",NULL,NULL,"Added product: Chic Color Block Floral Cardigan - V-Neck (SKU: 176607294941)",NULL,"2025-12-18 15:49:09");
INSERT INTO `audit_logs` VALUES("111","7","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-18 16:34:00");
INSERT INTO `audit_logs` VALUES("112","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s Mesh Leggings, Tights, Breathable (SKU: 176612520943)",NULL,"2025-12-19 06:20:09");
INSERT INTO `audit_logs` VALUES("113","7","PRODUCT_ADD",NULL,NULL,"Added product: Women&#039;s Maxi skirt tiny floral casual Elastic waist with pockets ruffeled hem la (SKU: 176612648036)",NULL,"2025-12-19 06:41:20");
INSERT INTO `audit_logs` VALUES("114","7","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-22 19:15:57");
INSERT INTO `audit_logs` VALUES("115","7","PRODUCT_ADD",NULL,NULL,"Added product: Summer Vacation Elegant Square Neck Short Sleeve Slim Top (SKU: 176650189607)",NULL,"2025-12-23 14:58:25");
INSERT INTO `audit_logs` VALUES("116","7","PRODUCT_ADD",NULL,NULL,"Added product: Miaspire 2pcs Women Solid Color Crew Neck 3/4 Sleeve Top And Pants Set (SKU: 176655882848)",NULL,"2025-12-24 06:47:08");
INSERT INTO `audit_logs` VALUES("117","7","PRODUCT_ADD",NULL,NULL,"Added product: Plus Size Colorblock Print Batwing Sleeve Dress, Casual Resort Wear (SKU: 176656208166)",NULL,"2025-12-24 07:41:21");
INSERT INTO `audit_logs` VALUES("118","7","PRODUCT_ADD",NULL,NULL,"Added product: Arave Women&#039;s Casual Versatile Long Fur Coat, Grey, Autumn/Winter (SKU: 176656292004)",NULL,"2025-12-24 07:55:20");
INSERT INTO `audit_logs` VALUES("119","7","PRODUCT_ADD",NULL,NULL,"Added product: Elenzga Women&#039;s Elegant Black &amp; White Printed Sleeveless Halter Neck Dress (SKU: 176656694051)",NULL,"2025-12-24 09:02:25");
INSERT INTO `audit_logs` VALUES("120","7","PRODUCT_ADD",NULL,NULL,"Added product: Elenzga Plus Size Women&#039;s Printed Fabric Waist Cincher Jumpsuit, Elegant Design (SKU: 176656723935)",NULL,"2025-12-24 09:07:19");
INSERT INTO `audit_logs` VALUES("121","7","bulk_edit_products",NULL,NULL,"Bulk edited 1 products",NULL,"2025-12-24 12:24:21");
INSERT INTO `audit_logs` VALUES("122","19","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-29 20:38:57");
INSERT INTO `audit_logs` VALUES("123","7","LOGOUT",NULL,NULL,"User logged out.",NULL,"2025-12-31 08:21:09");
INSERT INTO `audit_logs` VALUES("124","7","PRODUCT_ADD",NULL,NULL,"Added product: The Essential Textured Chino kaki jeans (SKU: 176734287229)",NULL,"2026-01-02 08:35:36");
INSERT INTO `audit_logs` VALUES("125","7","PRODUCT_ADD",NULL,NULL,"Added product: The CRUX Denim Blue Slim-Fit Men&#039;s Jeans (SKU: 176734393661)",NULL,"2026-01-02 08:52:16");
INSERT INTO `audit_logs` VALUES("126","7","PRODUCT_ADD",NULL,NULL,"Added product: The CRUX Men&#039;s Slim-Fit Jeans (SKU: 176734457264)",NULL,"2026-01-02 09:02:52");
INSERT INTO `audit_logs` VALUES("127","7","PRODUCT_ADD",NULL,NULL,"Added product: Marvel Spider-Man Side Panel Graphic Tee (SKU: 176734509499)",NULL,"2026-01-02 09:11:34");
INSERT INTO `audit_logs` VALUES("128","7","PRODUCT_ADD",NULL,NULL,"Added product: Kids Mickey Mouse HELLO Graphic Tee (SKU: 176734574510)",NULL,"2026-01-02 09:22:25");
INSERT INTO `audit_logs` VALUES("129","7","PRODUCT_ADD",NULL,NULL,"Added product: Disney Frozen II Character Print T-Shirt in Mustard Yellow (SKU: 176734604620)",NULL,"2026-01-02 09:28:07");
INSERT INTO `audit_logs` VALUES("130","7","PRODUCT_ADD",NULL,NULL,"Added product: Kids&#039; Classic Disney Mickey Mouse Face Graphic T-Shirt | White Cartoon Tee (SKU: 176734635723)",NULL,"2026-01-02 09:33:13");
INSERT INTO `audit_logs` VALUES("131","7","PRODUCT_ADD",NULL,NULL,"Added product: Kids&#039; Classic Disney Mickey Mouse Face Graphic T-Shirt | White Cartoon Tee (SKU: 176734665966)",NULL,"2026-01-02 09:37:42");



-- 
-- Table structure for table `brands` --
--

DROP TABLE IF EXISTS `brands`;

CREATE TABLE `brands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `brands` --
--

INSERT INTO `brands` VALUES("3","No-Brand","Products that has no known brands","2025-12-07 09:30:14","2025-12-07 09:30:14");
INSERT INTO `brands` VALUES("4","shein","clothes imported from japan","2025-12-17 07:50:49","2025-12-17 07:50:49");
INSERT INTO `brands` VALUES("5","Jeans","Any Jeans pants including Kaki","2026-01-02 08:21:39","2026-01-02 08:21:39");



-- 
-- Table structure for table `categories` --
--

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `categories` --
--

INSERT INTO `categories` VALUES("5","Clothes","clothes","2025-12-08 08:03:26","2025-12-17 07:50:09");
INSERT INTO `categories` VALUES("6","Electronics","Any Electronics products","2026-01-02 08:20:36","2026-01-02 08:20:36");



-- 
-- Table structure for table `cost_settings` --
--

DROP TABLE IF EXISTS `cost_settings`;

CREATE TABLE `cost_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` decimal(10,2) NOT NULL,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g., per_km, per_hour, per_liter',
  `description` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_name` (`setting_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `cost_settings` --
--

INSERT INTO `cost_settings` VALUES("1","fuel_cost_per_liter","50.00","ETB","Cost of fuel per liter","2025-12-13 09:25:32");
INSERT INTO `cost_settings` VALUES("2","driver_hourly_rate","100.00","ETB","Default driver hourly rate","2025-12-13 09:25:32");
INSERT INTO `cost_settings` VALUES("3","vehicle_maintenance_per_km","2.50","ETB","Average maintenance cost per km","2025-12-13 09:25:32");
INSERT INTO `cost_settings` VALUES("4","carbon_emission_factor","2.31","kg_CO2_per_liter","CO2 emissions per liter of fuel","2025-12-13 09:25:32");



-- 
-- Table structure for table `customer_addresses` --
--

DROP TABLE IF EXISTS `customer_addresses`;

CREATE TABLE `customer_addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `address_label` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g., Home, Office, Warehouse',
  `address_line1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Ethiopia',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `special_instructions` text COLLATE utf8mb4_unicode_ci,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_coordinates` (`latitude`,`longitude`),
  CONSTRAINT `customer_addresses_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `customer_addresses` --
--




-- 
-- Table structure for table `customers` --
--

DROP TABLE IF EXISTS `customers`;

CREATE TABLE `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notification_preference` enum('email','sms','both','none') COLLATE utf8mb4_unicode_ci DEFAULT 'email',
  `default_address_id` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  KEY `idx_customer_code` (`customer_code`),
  KEY `idx_email` (`email`),
  KEY `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `customers` --
--




-- 
-- Table structure for table `delivery_notifications` --
--

DROP TABLE IF EXISTS `delivery_notifications`;

CREATE TABLE `delivery_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `package_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `notification_type` enum('dispatch','in_transit','out_for_delivery','delivered','failed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` enum('email','sms','push') COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'email or phone number',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  KEY `customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_at` (`sent_at`),
  CONSTRAINT `delivery_notifications_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `delivery_packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delivery_notifications_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `delivery_notifications` --
--




-- 
-- Table structure for table `delivery_packages` --
--

DROP TABLE IF EXISTS `delivery_packages`;

CREATE TABLE `delivery_packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tracking_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `address_id` int DEFAULT NULL,
  `route_stop_id` int DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL COMMENT 'in kg',
  `volume` decimal(10,2) DEFAULT NULL COMMENT 'in cubic meters',
  `dimensions` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'LxWxH in cm',
  `package_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g., fragile, perishable, standard',
  `priority` enum('low','normal','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `value` decimal(10,2) DEFAULT NULL COMMENT 'declared value',
  `special_handling` json DEFAULT NULL COMMENT 'e.g., ["refrigerated", "fragile", "signature_required"]',
  `status` enum('pending','in_transit','delivered','failed','returned') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracking_number` (`tracking_number`),
  KEY `customer_id` (`customer_id`),
  KEY `address_id` (`address_id`),
  KEY `route_stop_id` (`route_stop_id`),
  KEY `idx_tracking` (`tracking_number`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  CONSTRAINT `delivery_packages_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `delivery_packages_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `customer_addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `delivery_packages_ibfk_3` FOREIGN KEY (`route_stop_id`) REFERENCES `route_stops` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `delivery_packages` --
--




-- 
-- Table structure for table `delivery_proof` --
--

DROP TABLE IF EXISTS `delivery_proof`;

CREATE TABLE `delivery_proof` (
  `id` int NOT NULL AUTO_INCREMENT,
  `package_id` int NOT NULL,
  `route_stop_id` int DEFAULT NULL,
  `delivered_by` int DEFAULT NULL COMMENT 'driver_id',
  `delivery_time` datetime NOT NULL,
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_signature` text COLLATE utf8mb4_unicode_ci COMMENT 'base64 encoded signature image',
  `photo_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `gps_latitude` decimal(10,8) DEFAULT NULL,
  `gps_longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `route_stop_id` (`route_stop_id`),
  KEY `delivered_by` (`delivered_by`),
  KEY `idx_package` (`package_id`),
  KEY `idx_delivery_time` (`delivery_time`),
  CONSTRAINT `delivery_proof_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `delivery_packages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `delivery_proof_ibfk_2` FOREIGN KEY (`route_stop_id`) REFERENCES `route_stops` (`id`) ON DELETE SET NULL,
  CONSTRAINT `delivery_proof_ibfk_3` FOREIGN KEY (`delivered_by`) REFERENCES `drivers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `delivery_proof` --
--




-- 
-- Table structure for table `delivery_time_windows` --
--

DROP TABLE IF EXISTS `delivery_time_windows`;

CREATE TABLE `delivery_time_windows` (
  `id` int NOT NULL AUTO_INCREMENT,
  `package_id` int NOT NULL,
  `earliest_time` time DEFAULT NULL,
  `latest_time` time DEFAULT NULL,
  `preferred_date` date DEFAULT NULL,
  `is_flexible` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_package` (`package_id`),
  CONSTRAINT `delivery_time_windows_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `delivery_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `delivery_time_windows` --
--




-- 
-- Table structure for table `driver_schedules` --
--

DROP TABLE IF EXISTS `driver_schedules`;

CREATE TABLE `driver_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `driver_id` int NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_start` time DEFAULT NULL,
  `break_end` time DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_driver_date` (`driver_id`,`date`),
  KEY `idx_date` (`date`),
  CONSTRAINT `driver_schedules_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `driver_schedules` --
--




-- 
-- Table structure for table `drivers` --
--

DROP TABLE IF EXISTS `drivers`;

CREATE TABLE `drivers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `license_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `status` enum('active','inactive','on_leave') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `skills` json DEFAULT NULL COMMENT 'e.g., ["refrigerated", "hazmat", "heavy_load"]',
  `max_working_hours` decimal(4,2) DEFAULT '8.00',
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_email` (`email`),
  CONSTRAINT `drivers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `drivers` --
--




-- 
-- Table structure for table `notifications` --
--

DROP TABLE IF EXISTS `notifications`;

CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'info',
  `message` text COLLATE utf8mb4_general_ci,
  `link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `notifications` --
--

INSERT INTO `notifications` VALUES("1","7","warning","Low Stock Alert: Product ID 6 is down to 0 units.","products/view_products.php","1","2025-12-06 10:23:35");
INSERT INTO `notifications` VALUES("2","7","warning","Low Stock Alert: Product ID 5 is down to 0 units.","products/view_products.php","1","2025-12-06 10:26:48");
INSERT INTO `notifications` VALUES("3","7","info","New user registration: test234 (tigist1276@gmail.com)","users/view_users.php","1","2025-12-06 10:28:15");
INSERT INTO `notifications` VALUES("4","7","success","Test Notification 11:37:16","#","1","2025-12-06 10:37:16");
INSERT INTO `notifications` VALUES("5","7","warning","Low Stock Alert: Product ID 6 is down to 0 units.","products/view_products.php","1","2025-12-06 10:39:29");
INSERT INTO `notifications` VALUES("7","7","info","New user registration: admin@example.com (Carementfashion40@gmail.com)","users/view_users.php","1","2025-12-06 13:23:37");
INSERT INTO `notifications` VALUES("8","7","info","New user registration: abu123 (belayhabtamu2001@gmail.com)","users/view_users.php","1","2025-12-06 19:57:09");
INSERT INTO `notifications` VALUES("9","7","info","New user registration: abu123 (belayhabtamu2001@gmail.com)","users/view_users.php","1","2025-12-06 20:09:47");
INSERT INTO `notifications` VALUES("10","7","warning","Low Stock Alert: Product ID 8 is down to 1 units.","products/view_products.php","1","2025-12-07 11:14:34");
INSERT INTO `notifications` VALUES("11","7","warning","Low Stock Alert: Product ID 8 is down to 0 units.","products/view_products.php","1","2025-12-07 11:18:24");
INSERT INTO `notifications` VALUES("12","7","info","New user registration: mimi (hiwotsisay@gmail.com)","users/view_users.php","1","2025-12-08 12:28:23");
INSERT INTO `notifications` VALUES("13","7","info","New user registration: abrahamsisay (abrahamsisaysis@gmail.com)","users/view_users.php","1","2025-12-10 13:51:20");
INSERT INTO `notifications` VALUES("14","8","info","New user registration: abrahamsisay (abrahamsisaysis@gmail.com)","users/view_users.php","0","2025-12-10 13:51:20");
INSERT INTO `notifications` VALUES("15","7","info","New user registration: Test (hilarymola0432@gmail.com)","users/view_users.php","1","2025-12-18 10:05:51");
INSERT INTO `notifications` VALUES("16","8","info","New user registration: Test (hilarymola0432@gmail.com)","users/view_users.php","0","2025-12-18 10:05:51");
INSERT INTO `notifications` VALUES("17","19","info","New user registration: Test (hilarymola0432@gmail.com)","users/view_users.php","0","2025-12-18 10:05:51");
INSERT INTO `notifications` VALUES("18","7","info","New user registration: Biniab (biniyam.abraha23@gmail.com)","users/view_users.php","1","2025-12-22 18:40:47");
INSERT INTO `notifications` VALUES("19","8","info","New user registration: Biniab (biniyam.abraha23@gmail.com)","users/view_users.php","0","2025-12-22 18:40:47");
INSERT INTO `notifications` VALUES("20","19","info","New user registration: Biniab (biniyam.abraha23@gmail.com)","users/view_users.php","0","2025-12-22 18:40:47");
INSERT INTO `notifications` VALUES("21","7","info","New user registration: afomia (afomiahabtamu69@gmail.com)","users/view_users.php","1","2025-12-23 06:25:46");
INSERT INTO `notifications` VALUES("22","8","info","New user registration: afomia (afomiahabtamu69@gmail.com)","users/view_users.php","0","2025-12-23 06:25:46");
INSERT INTO `notifications` VALUES("23","19","info","New user registration: afomia (afomiahabtamu69@gmail.com)","users/view_users.php","0","2025-12-23 06:25:46");
INSERT INTO `notifications` VALUES("24","21","info","New user registration: afomia (afomiahabtamu69@gmail.com)","users/view_users.php","0","2025-12-23 06:25:46");
INSERT INTO `notifications` VALUES("25","7","info","New user registration: aman (aman@gmail.com)","users/view_users.php","1","2026-01-01 06:07:39");
INSERT INTO `notifications` VALUES("26","8","info","New user registration: aman (aman@gmail.com)","users/view_users.php","0","2026-01-01 06:07:39");
INSERT INTO `notifications` VALUES("27","19","info","New user registration: aman (aman@gmail.com)","users/view_users.php","0","2026-01-01 06:07:39");
INSERT INTO `notifications` VALUES("28","21","info","New user registration: aman (aman@gmail.com)","users/view_users.php","0","2026-01-01 06:07:39");



-- 
-- Table structure for table `optimization_preferences` --
--

DROP TABLE IF EXISTS `optimization_preferences`;

CREATE TABLE `optimization_preferences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `preference_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preference_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_preference` (`user_id`,`preference_key`),
  CONSTRAINT `optimization_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `optimization_preferences` --
--




-- 
-- Table structure for table `price_calculation_history` --
--

DROP TABLE IF EXISTS `price_calculation_history`;

CREATE TABLE `price_calculation_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `source_currency` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source_amount` decimal(10,2) DEFAULT NULL,
  `exchange_rate` decimal(10,4) DEFAULT NULL,
  `etb_amount` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `calculation_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `price_calculation_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `price_calculation_history_chk_1` CHECK (json_valid(`calculation_data`))
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `price_calculation_history` --
--

INSERT INTO `price_calculation_history` VALUES("1","7","USD","100.00","154.1300","15413.00","20236.90","{\"sourceAmount\":100,\"sourceCurrency\":\"USD\",\"etbAmount\":15413,\"valueTax\":2311.95,\"shipmentFee\":1541.3000000000002,\"processingFee\":770.6500000000001,\"additionalFee\":\"200.0000\",\"totalFees\":4823.9,\"totalCost\":20236.9,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 14:21:02");
INSERT INTO `price_calculation_history` VALUES("2","7","USD","12.00","154.1300","1849.56","2604.43","{\"sourceAmount\":12,\"sourceCurrency\":\"USD\",\"etbAmount\":1849.56,\"valueTax\":277.43399999999997,\"shipmentFee\":184.95600000000002,\"processingFee\":92.47800000000001,\"additionalFee\":\"200.0000\",\"totalFees\":754.8679999999999,\"totalCost\":2604.428,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 14:26:50");
INSERT INTO `price_calculation_history` VALUES("3","7","USD","12.00","154.1300","1849.56","2604.43","{\"sourceAmount\":12,\"sourceCurrency\":\"USD\",\"etbAmount\":1849.56,\"valueTax\":277.43399999999997,\"shipmentFee\":184.95600000000002,\"processingFee\":92.47800000000001,\"additionalFee\":\"200.0000\",\"totalFees\":754.8679999999999,\"totalCost\":2604.428,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 14:27:31");
INSERT INTO `price_calculation_history` VALUES("4","7","USD","100.00","154.1300","15413.00","20236.90","{\"sourceAmount\":100,\"sourceCurrency\":\"USD\",\"etbAmount\":15413,\"valueTax\":2311.95,\"shipmentFee\":1541.3000000000002,\"processingFee\":770.6500000000001,\"additionalFee\":\"200.0000\",\"totalFees\":4823.9,\"totalCost\":20236.9,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 14:31:18");
INSERT INTO `price_calculation_history` VALUES("5","7","USD","10.00","154.1300","1541.30","2203.69","{\"sourceAmount\":10,\"sourceCurrency\":\"USD\",\"etbAmount\":1541.3,\"valueTax\":231.195,\"shipmentFee\":154.13,\"processingFee\":77.065,\"additionalFee\":\"200.0000\",\"totalFees\":662.39,\"totalCost\":2203.69,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 14:31:58");
INSERT INTO `price_calculation_history` VALUES("6","7","USD","10.00","154.1300","1541.30","2203.69","{\"sourceAmount\":10,\"sourceCurrency\":\"USD\",\"etbAmount\":1541.3,\"valueTax\":231.195,\"shipmentFee\":154.13,\"processingFee\":77.065,\"additionalFee\":\"200.0000\",\"totalFees\":662.39,\"totalCost\":2203.69,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 14:47:56");
INSERT INTO `price_calculation_history` VALUES("7","7","USD","12.00","154.1300","1849.56","2604.43","{\"sourceAmount\":12,\"sourceCurrency\":\"USD\",\"etbAmount\":1849.56,\"valueTax\":277.43399999999997,\"shipmentFee\":184.95600000000002,\"processingFee\":92.47800000000001,\"additionalFee\":\"200.0000\",\"totalFees\":754.8679999999999,\"totalCost\":2604.428,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 14:55:03");
INSERT INTO `price_calculation_history` VALUES("8","7","USD","10.00","154.1300","1541.30","2203.69","{\"sourceAmount\":10,\"sourceCurrency\":\"USD\",\"etbAmount\":1541.3,\"valueTax\":231.195,\"shipmentFee\":154.13,\"processingFee\":77.065,\"additionalFee\":\"200.0000\",\"totalFees\":662.39,\"totalCost\":2203.69,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 15:00:44");
INSERT INTO `price_calculation_history` VALUES("9","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:06:19");
INSERT INTO `price_calculation_history` VALUES("10","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:08:37");
INSERT INTO `price_calculation_history` VALUES("11","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:12:13");
INSERT INTO `price_calculation_history` VALUES("12","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:12:23");
INSERT INTO `price_calculation_history` VALUES("13","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:13:48");
INSERT INTO `price_calculation_history` VALUES("14","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:14:39");
INSERT INTO `price_calculation_history` VALUES("15","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:16:42");
INSERT INTO `price_calculation_history` VALUES("16","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:19:32");
INSERT INTO `price_calculation_history` VALUES("17","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:20:18");
INSERT INTO `price_calculation_history` VALUES("18","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:20:50");
INSERT INTO `price_calculation_history` VALUES("19","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:24:40");
INSERT INTO `price_calculation_history` VALUES("20","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:32:37");
INSERT INTO `price_calculation_history` VALUES("21","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:38:53");
INSERT INTO `price_calculation_history` VALUES("22","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:41:43");
INSERT INTO `price_calculation_history` VALUES("23","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:49:08");
INSERT INTO `price_calculation_history` VALUES("24","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:50:09");
INSERT INTO `price_calculation_history` VALUES("25","7","USD","1.00","154.1300","154.13","400.37","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":154.13,\"valueTax\":23.1195,\"shipmentFee\":15.413,\"processingFee\":7.7065,\"additionalFee\":\"200.0000\",\"totalFees\":246.239,\"totalCost\":400.369,\"exchangeRate\":154.13,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-06 17:56:29");
INSERT INTO `price_calculation_history` VALUES("26","7","USD","1.00","153.6500","153.65","399.75","{\"sourceAmount\":1,\"sourceCurrency\":\"USD\",\"etbAmount\":153.65,\"valueTax\":23.0475,\"shipmentFee\":15.365000000000002,\"processingFee\":7.682500000000001,\"additionalFee\":\"200.0000\",\"totalFees\":246.095,\"totalCost\":399.745,\"exchangeRate\":153.65,\"taxFeeDetails\":[{\"id\":\"4\",\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"3\",\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"2\",\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"},{\"id\":\"1\",\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":\"1\",\"created_at\":\"2025-12-06 17:08:22\",\"updated_at\":\"2025-12-06 17:08:22\"}]}","2025-12-07 04:52:59");
INSERT INTO `price_calculation_history` VALUES("27","7","USD","25.00","153.6900","3842.25","5194.93","{\"sourceAmount\":25,\"sourceCurrency\":\"USD\",\"etbAmount\":3842.25,\"valueTax\":576.3375,\"shipmentFee\":384.225,\"processingFee\":192.1125,\"additionalFee\":\"200.0000\",\"totalFees\":1352.675,\"totalCost\":5194.925,\"exchangeRate\":153.69,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-08 07:45:50");
INSERT INTO `price_calculation_history` VALUES("28","7","USD","12.00","153.6900","1844.28","2597.56","{\"sourceAmount\":12,\"sourceCurrency\":\"USD\",\"etbAmount\":1844.28,\"valueTax\":276.642,\"shipmentFee\":184.428,\"processingFee\":92.214,\"additionalFee\":\"200.0000\",\"totalFees\":753.284,\"totalCost\":2597.564,\"exchangeRate\":153.69,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-08 11:21:15");
INSERT INTO `price_calculation_history` VALUES("29","18","USD","13.49","153.6900","2073.28","2895.26","{\"sourceAmount\":13.49,\"sourceCurrency\":\"USD\",\"etbAmount\":2073.2781,\"valueTax\":310.991715,\"shipmentFee\":207.32781,\"processingFee\":103.663905,\"additionalFee\":\"200.0000\",\"totalFees\":821.98343,\"totalCost\":2895.2615299999998,\"exchangeRate\":153.69,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-08 15:52:04");
INSERT INTO `price_calculation_history` VALUES("30","18","USD","11.14","154.1000","1716.67","2431.68","{\"sourceAmount\":11.14,\"sourceCurrency\":\"USD\",\"etbAmount\":1716.674,\"valueTax\":257.5011,\"shipmentFee\":171.66740000000001,\"processingFee\":85.83370000000001,\"additionalFee\":\"200.0000\",\"totalFees\":715.0022,\"totalCost\":2431.6762,\"exchangeRate\":154.1,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-09 06:38:57");
INSERT INTO `price_calculation_history` VALUES("31","18","USD","14.65","154.1000","2257.57","3134.83","{\"sourceAmount\":14.65,\"sourceCurrency\":\"USD\",\"etbAmount\":2257.565,\"valueTax\":338.63475,\"shipmentFee\":225.75650000000002,\"processingFee\":112.87825000000001,\"additionalFee\":\"200.0000\",\"totalFees\":877.2695,\"totalCost\":3134.8345,\"exchangeRate\":154.1,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-09 06:40:01");
INSERT INTO `price_calculation_history` VALUES("32","18","USD","12.55","154.1000","1933.96","2714.14","{\"sourceAmount\":12.55,\"sourceCurrency\":\"USD\",\"etbAmount\":1933.955,\"valueTax\":290.09324999999995,\"shipmentFee\":193.3955,\"processingFee\":96.69775,\"additionalFee\":\"200.0000\",\"totalFees\":780.1865,\"totalCost\":2714.1414999999997,\"exchangeRate\":154.1,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-09 06:41:05");
INSERT INTO `price_calculation_history` VALUES("33","18","USD","9.76","154.1000","1504.02","2155.22","{\"sourceAmount\":9.76,\"sourceCurrency\":\"USD\",\"etbAmount\":1504.0159999999998,\"valueTax\":225.60239999999996,\"shipmentFee\":150.4016,\"processingFee\":75.2008,\"additionalFee\":\"200.0000\",\"totalFees\":651.2048,\"totalCost\":2155.2208,\"exchangeRate\":154.1,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-09 13:25:04");
INSERT INTO `price_calculation_history` VALUES("34","18","USD","12.10","154.1000","1864.61","2623.99","{\"sourceAmount\":12.1,\"sourceCurrency\":\"USD\",\"etbAmount\":1864.61,\"valueTax\":279.69149999999996,\"shipmentFee\":186.461,\"processingFee\":93.2305,\"additionalFee\":\"200.0000\",\"totalFees\":759.383,\"totalCost\":2623.993,\"exchangeRate\":154.1,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-09 13:28:13");
INSERT INTO `price_calculation_history` VALUES("35","18","USD","24.36","154.1000","3753.88","5080.04","{\"sourceAmount\":24.36,\"sourceCurrency\":\"USD\",\"etbAmount\":3753.8759999999997,\"valueTax\":563.0813999999999,\"shipmentFee\":375.3876,\"processingFee\":187.6938,\"additionalFee\":\"200.0000\",\"totalFees\":1326.1628,\"totalCost\":5080.0388,\"exchangeRate\":154.1,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-09 13:58:13");
INSERT INTO `price_calculation_history` VALUES("36","7","USD","10.00","154.1000","1541.00","2203.30","{\"sourceAmount\":10,\"sourceCurrency\":\"USD\",\"etbAmount\":1541,\"valueTax\":231.14999999999998,\"shipmentFee\":154.10000000000002,\"processingFee\":77.05000000000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":662.3,\"totalCost\":2203.3,\"exchangeRate\":154.1,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-09 14:21:07");
INSERT INTO `price_calculation_history` VALUES("37","7","USD","10.00","154.1000","1541.00","2403.30","{\"sourceAmount\":10,\"sourceCurrency\":\"USD\",\"etbAmount\":1541,\"valueTax\":231.14999999999998,\"shipmentFee\":154.10000000000002,\"processingFee\":77.05000000000001,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":862.3,\"totalCost\":2403.3,\"exchangeRate\":154.1,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-09 14:21:49");
INSERT INTO `price_calculation_history` VALUES("38","7","USD","10.00","154.1000","1541.00","2403.30","{\"sourceAmount\":10,\"sourceCurrency\":\"USD\",\"etbAmount\":1541,\"valueTax\":231.14999999999998,\"shipmentFee\":154.10000000000002,\"processingFee\":77.05000000000001,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":862.3,\"totalCost\":2403.3,\"exchangeRate\":154.1,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-09 14:24:59");
INSERT INTO `price_calculation_history` VALUES("39","18","USD","7.83","154.1000","1206.60","1768.58","{\"sourceAmount\":7.83,\"sourceCurrency\":\"USD\",\"etbAmount\":1206.603,\"valueTax\":180.99045,\"shipmentFee\":120.6603,\"processingFee\":60.33015,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":561.9809,\"totalCost\":1768.5839,\"exchangeRate\":154.1,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-09 14:27:33");
INSERT INTO `price_calculation_history` VALUES("40","7","USD","12.80","154.1000","1972.48","2764.22","{\"sourceAmount\":12.8,\"sourceCurrency\":\"USD\",\"etbAmount\":1972.48,\"valueTax\":295.872,\"shipmentFee\":197.24800000000002,\"processingFee\":98.62400000000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":791.744,\"totalCost\":2764.224,\"exchangeRate\":154.1,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-09 16:25:14");
INSERT INTO `price_calculation_history` VALUES("41","18","USD","10.71","154.0900","1650.30","2345.40","{\"sourceAmount\":10.71,\"sourceCurrency\":\"USD\",\"etbAmount\":1650.3039,\"valueTax\":247.54558500000002,\"shipmentFee\":165.03039,\"processingFee\":82.515195,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":695.09117,\"totalCost\":2345.39507,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 06:29:20");
INSERT INTO `price_calculation_history` VALUES("42","8","USD","20.00","154.0900","3081.80","4206.34","{\"sourceAmount\":20,\"sourceCurrency\":\"USD\",\"etbAmount\":3081.8,\"valueTax\":462.27,\"shipmentFee\":308.18000000000006,\"processingFee\":154.09000000000003,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":1124.54,\"totalCost\":4206.34,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 06:32:50");
INSERT INTO `price_calculation_history` VALUES("43","18","USD","11.70","154.0900","1802.85","2543.71","{\"sourceAmount\":11.7,\"sourceCurrency\":\"USD\",\"etbAmount\":1802.8529999999998,\"valueTax\":270.42794999999995,\"shipmentFee\":180.2853,\"processingFee\":90.14265,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":740.8559,\"totalCost\":2543.7088999999996,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 07:02:08");
INSERT INTO `price_calculation_history` VALUES("44","18","USD","11.70","154.0900","1802.85","2543.71","{\"sourceAmount\":11.7,\"sourceCurrency\":\"USD\",\"etbAmount\":1802.8529999999998,\"valueTax\":270.42794999999995,\"shipmentFee\":180.2853,\"processingFee\":90.14265,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":740.8559,\"totalCost\":2543.7088999999996,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 07:02:11");
INSERT INTO `price_calculation_history` VALUES("45","7","USD","11.80","154.0900","1818.26","2763.74","{\"sourceAmount\":11.8,\"sourceCurrency\":\"USD\",\"etbAmount\":1818.2620000000002,\"valueTax\":272.7393,\"shipmentFee\":181.82620000000003,\"processingFee\":90.91310000000001,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":945.4786,\"totalCost\":2763.7406,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 07:06:59");
INSERT INTO `price_calculation_history` VALUES("46","7","USD","14.63","154.0900","2254.34","3330.64","{\"sourceAmount\":14.63,\"sourceCurrency\":\"USD\",\"etbAmount\":2254.3367000000003,\"valueTax\":338.150505,\"shipmentFee\":225.43367000000003,\"processingFee\":112.71683500000002,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1076.3010100000001,\"totalCost\":3330.6377100000004,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 07:11:05");
INSERT INTO `price_calculation_history` VALUES("47","8","USD","20.00","154.0900","3081.80","4406.34","{\"sourceAmount\":20,\"sourceCurrency\":\"USD\",\"etbAmount\":3081.8,\"valueTax\":462.27,\"shipmentFee\":308.18000000000006,\"processingFee\":154.09000000000003,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1324.54,\"totalCost\":4406.34,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 08:03:01");
INSERT INTO `price_calculation_history` VALUES("48","18","USD","11.78","154.0900","1815.18","2559.73","{\"sourceAmount\":11.78,\"sourceCurrency\":\"USD\",\"etbAmount\":1815.1802,\"valueTax\":272.27702999999997,\"shipmentFee\":181.51802,\"processingFee\":90.75901,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":744.5540599999999,\"totalCost\":2559.73426,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 08:30:26");
INSERT INTO `price_calculation_history` VALUES("49","18","USD","11.60","154.0900","1787.44","2523.68","{\"sourceAmount\":11.6,\"sourceCurrency\":\"USD\",\"etbAmount\":1787.444,\"valueTax\":268.1166,\"shipmentFee\":178.7444,\"processingFee\":89.3722,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":736.2332,\"totalCost\":2523.6772,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 08:33:48");
INSERT INTO `price_calculation_history` VALUES("50","18","USD","11.60","150.0900","1741.04","2463.36","{\"sourceAmount\":11.6,\"sourceCurrency\":\"USD\",\"etbAmount\":1741.044,\"valueTax\":261.1566,\"shipmentFee\":174.10440000000003,\"processingFee\":87.05220000000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":722.3132,\"totalCost\":2463.3572000000004,\"exchangeRate\":150.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 08:44:38");
INSERT INTO `price_calculation_history` VALUES("51","18","USD","11.78","150.0900","1768.06","2498.48","{\"sourceAmount\":11.78,\"sourceCurrency\":\"USD\",\"etbAmount\":1768.0602,\"valueTax\":265.20903,\"shipmentFee\":176.80602,\"processingFee\":88.40301,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":730.41806,\"totalCost\":2498.47826,\"exchangeRate\":150.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 08:46:25");
INSERT INTO `price_calculation_history` VALUES("52","18","USD","11.78","150.0900","1768.06","2498.48","{\"sourceAmount\":11.78,\"sourceCurrency\":\"USD\",\"etbAmount\":1768.0602,\"valueTax\":265.20903,\"shipmentFee\":176.80602,\"processingFee\":88.40301,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":730.41806,\"totalCost\":2498.47826,\"exchangeRate\":150.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 08:46:27");
INSERT INTO `price_calculation_history` VALUES("53","18","USD","11.78","150.0900","1768.06","2498.48","{\"sourceAmount\":11.78,\"sourceCurrency\":\"USD\",\"etbAmount\":1768.0602,\"valueTax\":265.20903,\"shipmentFee\":176.80602,\"processingFee\":88.40301,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":730.41806,\"totalCost\":2498.47826,\"exchangeRate\":150.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 08:46:29");
INSERT INTO `price_calculation_history` VALUES("54","18","USD","11.78","150.0900","1768.06","2498.48","{\"sourceAmount\":11.78,\"sourceCurrency\":\"USD\",\"etbAmount\":1768.0602,\"valueTax\":265.20903,\"shipmentFee\":176.80602,\"processingFee\":88.40301,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":730.41806,\"totalCost\":2498.47826,\"exchangeRate\":150.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 08:46:34");
INSERT INTO `price_calculation_history` VALUES("55","18","USD","11.15","154.0900","1718.10","2433.53","{\"sourceAmount\":11.15,\"sourceCurrency\":\"USD\",\"etbAmount\":1718.1035000000002,\"valueTax\":257.715525,\"shipmentFee\":171.81035000000003,\"processingFee\":85.90517500000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":715.43105,\"totalCost\":2433.5345500000003,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 08:47:28");
INSERT INTO `price_calculation_history` VALUES("56","18","USD","6.55","154.0900","1009.29","1712.08","{\"sourceAmount\":6.55,\"sourceCurrency\":\"USD\",\"etbAmount\":1009.2895,\"valueTax\":151.39342499999998,\"shipmentFee\":100.92895,\"processingFee\":50.464475,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":702.78685,\"totalCost\":1712.0763499999998,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 09:24:58");
INSERT INTO `price_calculation_history` VALUES("57","18","USD","11.70","154.0900","1802.85","2743.71","{\"sourceAmount\":11.7,\"sourceCurrency\":\"USD\",\"etbAmount\":1802.8529999999998,\"valueTax\":270.42794999999995,\"shipmentFee\":180.2853,\"processingFee\":90.14265,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":940.8559,\"totalCost\":2743.7088999999996,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 09:25:49");
INSERT INTO `price_calculation_history` VALUES("58","18","USD","11.70","154.0900","1802.85","2743.71","{\"sourceAmount\":11.7,\"sourceCurrency\":\"USD\",\"etbAmount\":1802.8529999999998,\"valueTax\":270.42794999999995,\"shipmentFee\":180.2853,\"processingFee\":90.14265,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":940.8559,\"totalCost\":2743.7088999999996,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 09:28:29");
INSERT INTO `price_calculation_history` VALUES("59","7","USD","11.70","154.0900","1802.85","2543.71","{\"sourceAmount\":11.7,\"sourceCurrency\":\"USD\",\"etbAmount\":1802.8529999999998,\"valueTax\":270.42794999999995,\"shipmentFee\":180.2853,\"processingFee\":90.14265,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":740.8559,\"totalCost\":2543.7088999999996,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 10:10:21");
INSERT INTO `price_calculation_history` VALUES("60","7","USD","11.70","154.0900","1802.85","2743.71","{\"sourceAmount\":11.7,\"sourceCurrency\":\"USD\",\"etbAmount\":1802.8529999999998,\"valueTax\":270.42794999999995,\"shipmentFee\":180.2853,\"processingFee\":90.14265,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":940.8559,\"totalCost\":2743.7088999999996,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 10:10:26");
INSERT INTO `price_calculation_history` VALUES("61","19","USD","12.00","154.0900","1849.08","2603.80","{\"sourceAmount\":12,\"sourceCurrency\":\"USD\",\"etbAmount\":1849.08,\"valueTax\":277.36199999999997,\"shipmentFee\":184.90800000000002,\"processingFee\":92.45400000000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":754.7239999999999,\"totalCost\":2603.804,\"exchangeRate\":154.09,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-10 13:58:20");
INSERT INTO `price_calculation_history` VALUES("62","7","USD","13.72","154.6800","2122.21","2958.87","{\"sourceAmount\":13.72,\"sourceCurrency\":\"USD\",\"etbAmount\":2122.2096,\"valueTax\":318.33144,\"shipmentFee\":212.22096000000002,\"processingFee\":106.11048000000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":836.6628800000001,\"totalCost\":2958.87248,\"exchangeRate\":154.68,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-12 08:14:24");
INSERT INTO `price_calculation_history` VALUES("63","18","USD","8.39","154.6800","1297.77","1887.09","{\"sourceAmount\":8.39,\"sourceCurrency\":\"USD\",\"etbAmount\":1297.7652,\"valueTax\":194.66478,\"shipmentFee\":129.77652,\"processingFee\":64.88826,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":589.32956,\"totalCost\":1887.09476,\"exchangeRate\":154.68,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-12 09:43:45");
INSERT INTO `price_calculation_history` VALUES("64","18","USD","8.39","154.6800","1297.77","2087.09","{\"sourceAmount\":8.39,\"sourceCurrency\":\"USD\",\"etbAmount\":1297.7652,\"valueTax\":194.66478,\"shipmentFee\":129.77652,\"processingFee\":64.88826,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":789.32956,\"totalCost\":2087.09476,\"exchangeRate\":154.68,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-12 09:43:53");
INSERT INTO `price_calculation_history` VALUES("65","7","USD","13.30","154.6800","2057.24","2874.42","{\"sourceAmount\":13.3,\"sourceCurrency\":\"USD\",\"etbAmount\":2057.244,\"valueTax\":308.58660000000003,\"shipmentFee\":205.72440000000003,\"processingFee\":102.86220000000002,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":817.1732000000001,\"totalCost\":2874.4172000000003,\"exchangeRate\":154.68,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-12 11:52:36");
INSERT INTO `price_calculation_history` VALUES("66","18","USD","10.34","154.9900","1602.60","2483.38","{\"sourceAmount\":10.34,\"sourceCurrency\":\"USD\",\"etbAmount\":1602.5966,\"valueTax\":240.38949,\"shipmentFee\":160.25966000000003,\"processingFee\":80.12983000000001,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":880.77898,\"totalCost\":2483.37558,\"exchangeRate\":154.99,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-13 09:23:36");
INSERT INTO `price_calculation_history` VALUES("67","18","USD","4.99","154.9900","773.40","1405.42","{\"sourceAmount\":4.99,\"sourceCurrency\":\"USD\",\"etbAmount\":773.4001000000001,\"valueTax\":116.01001500000001,\"shipmentFee\":77.34001,\"processingFee\":38.670005,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":632.02003,\"totalCost\":1405.42013,\"exchangeRate\":154.99,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-13 09:25:24");
INSERT INTO `price_calculation_history` VALUES("68","18","USD","3.07","154.9900","475.82","1018.57","{\"sourceAmount\":3.07,\"sourceCurrency\":\"USD\",\"etbAmount\":475.8193,\"valueTax\":71.372895,\"shipmentFee\":47.58193,\"processingFee\":23.790965,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":542.7457899999999,\"totalCost\":1018.5650899999999,\"exchangeRate\":154.99,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-13 09:35:53");
INSERT INTO `price_calculation_history` VALUES("69","18","USD","7.24","154.7100","1120.10","1856.13","{\"sourceAmount\":7.24,\"sourceCurrency\":\"USD\",\"etbAmount\":1120.1004,\"valueTax\":168.01506,\"shipmentFee\":112.01004,\"processingFee\":56.00502,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":736.03012,\"totalCost\":1856.1305200000002,\"exchangeRate\":154.71,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-16 12:39:32");
INSERT INTO `price_calculation_history` VALUES("70","18","USD","5.34","154.7100","826.15","1474.00","{\"sourceAmount\":5.34,\"sourceCurrency\":\"USD\",\"etbAmount\":826.1514,\"valueTax\":123.92271,\"shipmentFee\":82.61514,\"processingFee\":41.30757,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":647.84542,\"totalCost\":1473.9968199999998,\"exchangeRate\":154.71,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-16 12:40:04");
INSERT INTO `price_calculation_history` VALUES("71","7","USD","24.00","154.3300","3703.92","5015.10","{\"sourceAmount\":24,\"sourceCurrency\":\"USD\",\"etbAmount\":3703.92,\"valueTax\":555.588,\"shipmentFee\":370.39200000000005,\"processingFee\":185.19600000000003,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":1311.176,\"totalCost\":5015.096,\"exchangeRate\":154.33,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-17 09:13:47");
INSERT INTO `price_calculation_history` VALUES("72","18","USD","33.39","154.3300","5153.08","6899.00","{\"sourceAmount\":33.39,\"sourceCurrency\":\"USD\",\"etbAmount\":5153.078700000001,\"valueTax\":772.9618050000001,\"shipmentFee\":515.3078700000001,\"processingFee\":257.65393500000005,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":1745.9236100000003,\"totalCost\":6899.002310000002,\"exchangeRate\":154.33,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-17 09:27:31");
INSERT INTO `price_calculation_history` VALUES("73","18","USD","33.39","154.3300","5153.08","7099.00","{\"sourceAmount\":33.39,\"sourceCurrency\":\"USD\",\"etbAmount\":5153.078700000001,\"valueTax\":772.9618050000001,\"shipmentFee\":515.3078700000001,\"processingFee\":257.65393500000005,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1945.9236100000003,\"totalCost\":7099.002310000002,\"exchangeRate\":154.33,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-17 09:27:36");
INSERT INTO `price_calculation_history` VALUES("74","18","USD","11.05","149.9500","1656.95","2554.03","{\"sourceAmount\":11.05,\"sourceCurrency\":\"USD\",\"etbAmount\":1656.9475,\"valueTax\":248.542125,\"shipmentFee\":165.69475,\"processingFee\":82.847375,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":897.08425,\"totalCost\":2554.03175,\"exchangeRate\":149.95,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-18 05:53:07");
INSERT INTO `price_calculation_history` VALUES("75","18","USD","11.05","149.9500","1656.95","2554.03","{\"sourceAmount\":11.05,\"sourceCurrency\":\"USD\",\"etbAmount\":1656.9475,\"valueTax\":248.542125,\"shipmentFee\":165.69475,\"processingFee\":82.847375,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":897.08425,\"totalCost\":2554.03175,\"exchangeRate\":149.95,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-18 05:53:13");
INSERT INTO `price_calculation_history` VALUES("76","18","USD","28.00","154.5500","4327.40","6025.62","{\"sourceAmount\":28,\"sourceCurrency\":\"USD\",\"etbAmount\":4327.400000000001,\"valueTax\":649.11,\"shipmentFee\":432.74000000000007,\"processingFee\":216.37000000000003,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1698.2200000000003,\"totalCost\":6025.620000000001,\"exchangeRate\":154.55,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-18 09:00:25");
INSERT INTO `price_calculation_history` VALUES("77","18","USD","28.00","154.5500","4327.40","6025.62","{\"sourceAmount\":28,\"sourceCurrency\":\"USD\",\"etbAmount\":4327.400000000001,\"valueTax\":649.11,\"shipmentFee\":432.74000000000007,\"processingFee\":216.37000000000003,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1698.2200000000003,\"totalCost\":6025.620000000001,\"exchangeRate\":154.55,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-18 09:00:30");
INSERT INTO `price_calculation_history` VALUES("78","18","USD","12.68","154.5500","1959.69","2947.60","{\"sourceAmount\":12.68,\"sourceCurrency\":\"USD\",\"etbAmount\":1959.6940000000002,\"valueTax\":293.95410000000004,\"shipmentFee\":195.96940000000004,\"processingFee\":97.98470000000002,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":987.9082000000001,\"totalCost\":2947.6022000000003,\"exchangeRate\":154.55,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-18 09:03:36");
INSERT INTO `price_calculation_history` VALUES("79","18","USD","11.97","154.5500","1849.96","2604.95","{\"sourceAmount\":11.97,\"sourceCurrency\":\"USD\",\"etbAmount\":1849.9635000000003,\"valueTax\":277.494525,\"shipmentFee\":184.99635000000004,\"processingFee\":92.49817500000002,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":754.9890500000001,\"totalCost\":2604.9525500000004,\"exchangeRate\":154.55,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-18 13:31:58");
INSERT INTO `price_calculation_history` VALUES("80","18","USD","12.20","154.5500","1885.51","2651.16","{\"sourceAmount\":12.2,\"sourceCurrency\":\"USD\",\"etbAmount\":1885.51,\"valueTax\":282.8265,\"shipmentFee\":188.55100000000002,\"processingFee\":94.27550000000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":765.653,\"totalCost\":2651.163,\"exchangeRate\":154.55,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-18 14:03:04");
INSERT INTO `price_calculation_history` VALUES("81","18","USD","12.20","154.5500","1885.51","2651.16","{\"sourceAmount\":12.2,\"sourceCurrency\":\"USD\",\"etbAmount\":1885.51,\"valueTax\":282.8265,\"shipmentFee\":188.55100000000002,\"processingFee\":94.27550000000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":765.653,\"totalCost\":2651.163,\"exchangeRate\":154.55,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-18 14:03:20");
INSERT INTO `price_calculation_history` VALUES("82","18","USD","12.20","154.5500","1885.51","2651.16","{\"sourceAmount\":12.2,\"sourceCurrency\":\"USD\",\"etbAmount\":1885.51,\"valueTax\":282.8265,\"shipmentFee\":188.55100000000002,\"processingFee\":94.27550000000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":765.653,\"totalCost\":2651.163,\"exchangeRate\":154.55,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-18 14:03:28");
INSERT INTO `price_calculation_history` VALUES("83","18","USD","12.30","154.5500","1900.97","2671.25","{\"sourceAmount\":12.3,\"sourceCurrency\":\"USD\",\"etbAmount\":1900.9650000000001,\"valueTax\":285.14475,\"shipmentFee\":190.09650000000002,\"processingFee\":95.04825000000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":770.2895000000001,\"totalCost\":2671.2545,\"exchangeRate\":154.55,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-19 09:03:31");
INSERT INTO `price_calculation_history` VALUES("84","18","USD","13.10","154.5500","2024.61","2831.99","{\"sourceAmount\":13.1,\"sourceCurrency\":\"USD\",\"etbAmount\":2024.605,\"valueTax\":303.69075,\"shipmentFee\":202.46050000000002,\"processingFee\":101.23025000000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":807.3815,\"totalCost\":2831.9865,\"exchangeRate\":154.55,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-19 09:14:38");
INSERT INTO `price_calculation_history` VALUES("85","7","USD","13.85","154.2900","2136.92","2977.99","{\"sourceAmount\":13.85,\"sourceCurrency\":\"USD\",\"etbAmount\":2136.9165,\"valueTax\":320.537475,\"shipmentFee\":213.69164999999998,\"processingFee\":106.84582499999999,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":841.07495,\"totalCost\":2977.9914499999995,\"exchangeRate\":154.29,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-20 09:36:19");
INSERT INTO `price_calculation_history` VALUES("86","7","USD","15.00","154.2900","2314.35","3208.66","{\"sourceAmount\":15,\"sourceCurrency\":\"USD\",\"etbAmount\":2314.35,\"valueTax\":347.1525,\"shipmentFee\":231.435,\"processingFee\":115.7175,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":894.305,\"totalCost\":3208.6549999999997,\"exchangeRate\":154.29,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-20 09:56:04");
INSERT INTO `price_calculation_history` VALUES("87","7","USD","15.00","154.2900","2314.35","3208.66","{\"sourceAmount\":15,\"sourceCurrency\":\"USD\",\"etbAmount\":2314.35,\"valueTax\":347.1525,\"shipmentFee\":231.435,\"processingFee\":115.7175,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":894.305,\"totalCost\":3208.6549999999997,\"exchangeRate\":154.29,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-20 09:56:04");
INSERT INTO `price_calculation_history` VALUES("88","18","USD","106.26","154.2900","16394.86","21513.31","{\"sourceAmount\":106.26,\"sourceCurrency\":\"USD\",\"etbAmount\":16394.8554,\"valueTax\":2459.22831,\"shipmentFee\":1639.4855400000001,\"processingFee\":819.7427700000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":5118.45662,\"totalCost\":21513.31202,\"exchangeRate\":154.29,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-20 17:26:58");
INSERT INTO `price_calculation_history` VALUES("89","18","USD","91.42","154.2900","14105.19","18536.75","{\"sourceAmount\":91.42,\"sourceCurrency\":\"USD\",\"etbAmount\":14105.191799999999,\"valueTax\":2115.77877,\"shipmentFee\":1410.51918,\"processingFee\":705.25959,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":4431.55754,\"totalCost\":18536.74934,\"exchangeRate\":154.29,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-20 17:27:34");
INSERT INTO `price_calculation_history` VALUES("90","18","USD","24.60","154.2900","3795.53","5134.19","{\"sourceAmount\":24.6,\"sourceCurrency\":\"USD\",\"etbAmount\":3795.534,\"valueTax\":569.3301,\"shipmentFee\":379.5534,\"processingFee\":189.7767,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":1338.6602,\"totalCost\":5134.1942,\"exchangeRate\":154.29,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-20 17:48:16");
INSERT INTO `price_calculation_history` VALUES("91","18","USD","34.88","154.2900","5381.64","7196.13","{\"sourceAmount\":34.88,\"sourceCurrency\":\"USD\",\"etbAmount\":5381.6352,\"valueTax\":807.24528,\"shipmentFee\":538.16352,\"processingFee\":269.08176,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":1814.49056,\"totalCost\":7196.12576,\"exchangeRate\":154.29,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-20 18:03:54");
INSERT INTO `price_calculation_history` VALUES("92","18","USD","17.54","154.2700","2705.90","3917.66","{\"sourceAmount\":17.54,\"sourceCurrency\":\"USD\",\"etbAmount\":2705.8958000000002,\"valueTax\":405.88437000000005,\"shipmentFee\":270.58958,\"processingFee\":135.29479,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1211.76874,\"totalCost\":3917.66454,\"exchangeRate\":154.27,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-23 12:20:49");
INSERT INTO `price_calculation_history` VALUES("93","7","USD","16.70","154.2700","2576.31","3549.20","{\"sourceAmount\":16.7,\"sourceCurrency\":\"USD\",\"etbAmount\":2576.309,\"valueTax\":386.44635,\"shipmentFee\":257.63090000000005,\"processingFee\":128.81545000000003,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":972.8927000000001,\"totalCost\":3549.2017000000005,\"exchangeRate\":154.27,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-23 14:17:50");
INSERT INTO `price_calculation_history` VALUES("94","7","USD","12.55","154.5400","1939.48","2721.32","{\"sourceAmount\":12.55,\"sourceCurrency\":\"USD\",\"etbAmount\":1939.477,\"valueTax\":290.92155,\"shipmentFee\":193.94770000000003,\"processingFee\":96.97385000000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":781.8431,\"totalCost\":2721.3201,\"exchangeRate\":154.54,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-24 07:32:25");
INSERT INTO `price_calculation_history` VALUES("95","18","USD","35.82","154.5400","5535.62","7596.31","{\"sourceAmount\":35.82,\"sourceCurrency\":\"USD\",\"etbAmount\":5535.6228,\"valueTax\":830.34342,\"shipmentFee\":553.56228,\"processingFee\":276.78114,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":2060.6868400000003,\"totalCost\":7596.30964,\"exchangeRate\":154.54,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-24 08:04:50");
INSERT INTO `price_calculation_history` VALUES("96","18","USD","5.39","154.5400","832.97","1482.86","{\"sourceAmount\":5.39,\"sourceCurrency\":\"USD\",\"etbAmount\":832.9705999999999,\"valueTax\":124.94558999999998,\"shipmentFee\":83.29705999999999,\"processingFee\":41.648529999999994,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":649.89118,\"totalCost\":1482.8617799999997,\"exchangeRate\":154.54,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-24 08:06:52");
INSERT INTO `price_calculation_history` VALUES("97","18","USD","56.78","154.5400","8774.78","11807.22","{\"sourceAmount\":56.78,\"sourceCurrency\":\"USD\",\"etbAmount\":8774.7812,\"valueTax\":1316.2171799999999,\"shipmentFee\":877.47812,\"processingFee\":438.73906,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":3032.4343599999997,\"totalCost\":11807.215559999999,\"exchangeRate\":154.54,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-24 08:09:08");
INSERT INTO `price_calculation_history` VALUES("98","18","USD","14.60","154.5400","2256.28","3333.17","{\"sourceAmount\":14.6,\"sourceCurrency\":\"USD\",\"etbAmount\":2256.2839999999997,\"valueTax\":338.4425999999999,\"shipmentFee\":225.62839999999997,\"processingFee\":112.81419999999999,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1076.8852,\"totalCost\":3333.1691999999994,\"exchangeRate\":154.54,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-24 08:10:51");
INSERT INTO `price_calculation_history` VALUES("99","7","USD","17.00","154.5400","2627.18","3615.33","{\"sourceAmount\":17,\"sourceCurrency\":\"USD\",\"etbAmount\":2627.18,\"valueTax\":394.07699999999994,\"shipmentFee\":262.718,\"processingFee\":131.359,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":988.154,\"totalCost\":3615.334,\"exchangeRate\":154.54,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-24 08:29:54");
INSERT INTO `price_calculation_history` VALUES("100","18","USD","14.70","154.5400","2271.74","3353.26","{\"sourceAmount\":14.7,\"sourceCurrency\":\"USD\",\"etbAmount\":2271.738,\"valueTax\":340.7607,\"shipmentFee\":227.1738,\"processingFee\":113.5869,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1081.5214,\"totalCost\":3353.2594,\"exchangeRate\":154.54,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-24 08:50:55");
INSERT INTO `price_calculation_history` VALUES("101","18","USD","13.33","154.5400","2060.02","3078.02","{\"sourceAmount\":13.33,\"sourceCurrency\":\"USD\",\"etbAmount\":2060.0182,\"valueTax\":309.00273,\"shipmentFee\":206.00182,\"processingFee\":103.00091,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1018.00546,\"totalCost\":3078.02366,\"exchangeRate\":154.54,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-24 08:51:28");
INSERT INTO `price_calculation_history` VALUES("102","7","USD","14.60","154.5400","2256.28","3133.17","{\"sourceAmount\":14.6,\"sourceCurrency\":\"USD\",\"etbAmount\":2256.2839999999997,\"valueTax\":338.4425999999999,\"shipmentFee\":225.62839999999997,\"processingFee\":112.81419999999999,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":876.8851999999999,\"totalCost\":3133.1691999999994,\"exchangeRate\":154.54,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-24 09:11:40");
INSERT INTO `price_calculation_history` VALUES("103","18","USD","12.70","154.5400","1962.66","2951.46","{\"sourceAmount\":12.7,\"sourceCurrency\":\"USD\",\"etbAmount\":1962.658,\"valueTax\":294.39869999999996,\"shipmentFee\":196.2658,\"processingFee\":98.1329,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":988.7973999999999,\"totalCost\":2951.4554,\"exchangeRate\":154.54,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-24 09:13:30");
INSERT INTO `price_calculation_history` VALUES("104","18","USD","12.70","154.5400","1962.66","2951.46","{\"sourceAmount\":12.7,\"sourceCurrency\":\"USD\",\"etbAmount\":1962.658,\"valueTax\":294.39869999999996,\"shipmentFee\":196.2658,\"processingFee\":98.1329,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":988.7973999999999,\"totalCost\":2951.4554,\"exchangeRate\":154.54,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-24 09:13:36");
INSERT INTO `price_calculation_history` VALUES("105","7","USD","230.00","154.5400","35544.20","46407.46","{\"sourceAmount\":230,\"sourceCurrency\":\"USD\",\"etbAmount\":35544.2,\"valueTax\":5331.629999999999,\"shipmentFee\":3554.42,\"processingFee\":1777.21,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":10863.259999999998,\"totalCost\":46407.45999999999,\"exchangeRate\":154.54,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-24 09:23:23");
INSERT INTO `price_calculation_history` VALUES("106","7","USD","2.30","154.5400","355.44","662.07","{\"sourceAmount\":2.3,\"sourceCurrency\":\"USD\",\"etbAmount\":355.44199999999995,\"valueTax\":53.31629999999999,\"shipmentFee\":35.5442,\"processingFee\":17.7721,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":306.63259999999997,\"totalCost\":662.0745999999999,\"exchangeRate\":154.54,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-24 09:23:30");
INSERT INTO `price_calculation_history` VALUES("107","18","USD","15.63","154.6100","2416.55","3541.52","{\"sourceAmount\":15.63,\"sourceCurrency\":\"USD\",\"etbAmount\":2416.5543000000002,\"valueTax\":362.48314500000004,\"shipmentFee\":241.65543000000002,\"processingFee\":120.82771500000001,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1124.96629,\"totalCost\":3541.52059,\"exchangeRate\":154.61,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-25 09:19:01");
INSERT INTO `price_calculation_history` VALUES("108","18","USD","5.01","154.6100","774.60","1406.97","{\"sourceAmount\":5.01,\"sourceCurrency\":\"USD\",\"etbAmount\":774.5961000000001,\"valueTax\":116.18941500000001,\"shipmentFee\":77.45961000000001,\"processingFee\":38.729805000000006,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":632.37883,\"totalCost\":1406.97493,\"exchangeRate\":154.61,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-25 10:09:41");
INSERT INTO `price_calculation_history` VALUES("109","18","USD","2.62","154.6100","405.08","926.60","{\"sourceAmount\":2.62,\"sourceCurrency\":\"USD\",\"etbAmount\":405.07820000000004,\"valueTax\":60.76173,\"shipmentFee\":40.50782000000001,\"processingFee\":20.253910000000005,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":521.52346,\"totalCost\":926.60166,\"exchangeRate\":154.61,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-25 10:16:51");
INSERT INTO `price_calculation_history` VALUES("110","18","USD","11.62","154.6100","1796.57","2735.54","{\"sourceAmount\":11.62,\"sourceCurrency\":\"USD\",\"etbAmount\":1796.5682,\"valueTax\":269.48523,\"shipmentFee\":179.65682,\"processingFee\":89.82841,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":938.97046,\"totalCost\":2735.53866,\"exchangeRate\":154.61,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-25 11:36:16");
INSERT INTO `price_calculation_history` VALUES("111","18","USD","3.80","154.5300","587.21","1163.38","{\"sourceAmount\":3.8,\"sourceCurrency\":\"USD\",\"etbAmount\":587.2139999999999,\"valueTax\":88.08209999999998,\"shipmentFee\":58.721399999999996,\"processingFee\":29.360699999999998,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":576.1641999999999,\"totalCost\":1163.3781999999999,\"exchangeRate\":154.53,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-26 14:06:26");
INSERT INTO `price_calculation_history` VALUES("112","18","USD","39.65","154.5300","6127.11","8365.25","{\"sourceAmount\":39.65,\"sourceCurrency\":\"USD\",\"etbAmount\":6127.1145,\"valueTax\":919.0671749999999,\"shipmentFee\":612.71145,\"processingFee\":306.355725,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":2238.13435,\"totalCost\":8365.24885,\"exchangeRate\":154.53,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-26 14:12:59");
INSERT INTO `price_calculation_history` VALUES("113","18","USD","29.41","154.5300","4544.73","6308.15","{\"sourceAmount\":29.41,\"sourceCurrency\":\"USD\",\"etbAmount\":4544.7273000000005,\"valueTax\":681.709095,\"shipmentFee\":454.47273000000007,\"processingFee\":227.23636500000003,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1763.41819,\"totalCost\":6308.145490000001,\"exchangeRate\":154.53,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-26 14:14:01");
INSERT INTO `price_calculation_history` VALUES("114","18","USD","11.86","150.5300","1785.29","2720.87","{\"sourceAmount\":11.86,\"sourceCurrency\":\"USD\",\"etbAmount\":1785.2857999999999,\"valueTax\":267.79287,\"shipmentFee\":178.52858,\"processingFee\":89.26429,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":935.58574,\"totalCost\":2720.87154,\"exchangeRate\":150.53,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-26 14:47:03");
INSERT INTO `price_calculation_history` VALUES("115","18","USD","11.86","149.9000","1777.81","2711.16","{\"sourceAmount\":11.86,\"sourceCurrency\":\"USD\",\"etbAmount\":1777.814,\"valueTax\":266.6721,\"shipmentFee\":177.78140000000002,\"processingFee\":88.89070000000001,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":933.3442,\"totalCost\":2711.1582,\"exchangeRate\":149.9,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-26 14:47:21");
INSERT INTO `price_calculation_history` VALUES("116","18","USD","11.86","149.9000","1777.81","2711.16","{\"sourceAmount\":11.86,\"sourceCurrency\":\"USD\",\"etbAmount\":1777.814,\"valueTax\":266.6721,\"shipmentFee\":177.78140000000002,\"processingFee\":88.89070000000001,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":933.3442,\"totalCost\":2711.1582,\"exchangeRate\":149.9,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-26 14:47:34");
INSERT INTO `price_calculation_history` VALUES("117","18","USD","11.86","149.9000","1777.81","2711.16","{\"sourceAmount\":11.86,\"sourceCurrency\":\"USD\",\"etbAmount\":1777.814,\"valueTax\":266.6721,\"shipmentFee\":177.78140000000002,\"processingFee\":88.89070000000001,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":933.3442,\"totalCost\":2711.1582,\"exchangeRate\":149.9,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-26 14:47:40");
INSERT INTO `price_calculation_history` VALUES("118","18","USD","11.86","149.9000","1777.81","2511.16","{\"sourceAmount\":11.86,\"sourceCurrency\":\"USD\",\"etbAmount\":1777.814,\"valueTax\":266.6721,\"shipmentFee\":177.78140000000002,\"processingFee\":88.89070000000001,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":733.3442,\"totalCost\":2511.1582,\"exchangeRate\":149.9,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-26 14:48:04");
INSERT INTO `price_calculation_history` VALUES("119","18","USD","12.68","154.5300","1959.44","2747.27","{\"sourceAmount\":12.68,\"sourceCurrency\":\"USD\",\"etbAmount\":1959.4404,\"valueTax\":293.91605999999996,\"shipmentFee\":195.94404,\"processingFee\":97.97202,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":787.83212,\"totalCost\":2747.27252,\"exchangeRate\":154.53,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-26 15:32:56");
INSERT INTO `price_calculation_history` VALUES("120","18","USD","12.68","154.5300","1959.44","2947.27","{\"sourceAmount\":12.68,\"sourceCurrency\":\"USD\",\"etbAmount\":1959.4404,\"valueTax\":293.91605999999996,\"shipmentFee\":195.94404,\"processingFee\":97.97202,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":987.83212,\"totalCost\":2947.27252,\"exchangeRate\":154.53,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-26 15:33:19");
INSERT INTO `price_calculation_history` VALUES("121","18","USD","11.80","154.0800","1818.14","2763.59","{\"sourceAmount\":11.8,\"sourceCurrency\":\"USD\",\"etbAmount\":1818.1440000000002,\"valueTax\":272.7216,\"shipmentFee\":181.81440000000003,\"processingFee\":90.90720000000002,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":945.4432,\"totalCost\":2763.5872000000004,\"exchangeRate\":154.08,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-27 15:52:22");
INSERT INTO `price_calculation_history` VALUES("122","18","USD","5.17","154.0800","796.59","1435.57","{\"sourceAmount\":5.17,\"sourceCurrency\":\"USD\",\"etbAmount\":796.5936,\"valueTax\":119.48904,\"shipmentFee\":79.65936,\"processingFee\":39.82968,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":638.97808,\"totalCost\":1435.57168,\"exchangeRate\":154.08,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-27 15:53:22");
INSERT INTO `price_calculation_history` VALUES("123","18","USD","12.68","154.0800","1953.73","2939.85","{\"sourceAmount\":12.68,\"sourceCurrency\":\"USD\",\"etbAmount\":1953.7344,\"valueTax\":293.06016,\"shipmentFee\":195.37344000000002,\"processingFee\":97.68672000000001,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":986.12032,\"totalCost\":2939.8547200000003,\"exchangeRate\":154.08,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-27 16:00:04");
INSERT INTO `price_calculation_history` VALUES("124","18","USD","26.53","153.7300","4078.46","5501.99","{\"sourceAmount\":26.53,\"sourceCurrency\":\"USD\",\"etbAmount\":4078.4568999999997,\"valueTax\":611.7685349999999,\"shipmentFee\":407.84569,\"processingFee\":203.922845,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":1423.5370699999999,\"totalCost\":5501.9939699999995,\"exchangeRate\":153.73,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-28 07:46:10");
INSERT INTO `price_calculation_history` VALUES("125","18","USD","14.00","153.7300","2152.22","3197.89","{\"sourceAmount\":14,\"sourceCurrency\":\"USD\",\"etbAmount\":2152.22,\"valueTax\":322.83299999999997,\"shipmentFee\":215.22199999999998,\"processingFee\":107.61099999999999,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1045.666,\"totalCost\":3197.8859999999995,\"exchangeRate\":153.73,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-28 17:42:40");
INSERT INTO `price_calculation_history` VALUES("126","18","USD","13.79","153.7300","2119.94","3155.92","{\"sourceAmount\":13.79,\"sourceCurrency\":\"USD\",\"etbAmount\":2119.9366999999997,\"valueTax\":317.9905049999999,\"shipmentFee\":211.99366999999998,\"processingFee\":105.99683499999999,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1035.98101,\"totalCost\":3155.9177099999997,\"exchangeRate\":153.73,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-28 17:43:40");
INSERT INTO `price_calculation_history` VALUES("127","18","USD","2.39","1.0000","2.39","403.11","{\"sourceAmount\":2.39,\"sourceCurrency\":\"USD\",\"etbAmount\":2.39,\"valueTax\":0.3585,\"shipmentFee\":0.23900000000000002,\"processingFee\":0.11950000000000001,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":400.717,\"totalCost\":403.10699999999997,\"exchangeRate\":1,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-29 19:07:38");
INSERT INTO `price_calculation_history` VALUES("128","18","USD","2.39","154.7300","369.80","880.75","{\"sourceAmount\":2.39,\"sourceCurrency\":\"USD\",\"etbAmount\":369.80469999999997,\"valueTax\":55.470704999999995,\"shipmentFee\":36.98047,\"processingFee\":18.490235,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":510.94141,\"totalCost\":880.74611,\"exchangeRate\":154.73,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-29 19:07:48");
INSERT INTO `price_calculation_history` VALUES("129","18","USD","15.12","154.6400","2338.16","3439.60","{\"sourceAmount\":15.12,\"sourceCurrency\":\"USD\",\"etbAmount\":2338.1567999999997,\"valueTax\":350.72351999999995,\"shipmentFee\":233.81568,\"processingFee\":116.90784,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1101.44704,\"totalCost\":3439.6038399999998,\"exchangeRate\":154.64,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-30 10:22:12");
INSERT INTO `price_calculation_history` VALUES("130","18","USD","34.40","154.6400","5319.62","7315.50","{\"sourceAmount\":34.4,\"sourceCurrency\":\"USD\",\"etbAmount\":5319.615999999999,\"valueTax\":797.9423999999998,\"shipmentFee\":531.9616,\"processingFee\":265.9808,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1995.8847999999998,\"totalCost\":7315.500799999999,\"exchangeRate\":154.64,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-30 15:25:29");
INSERT INTO `price_calculation_history` VALUES("131","18","USD","12.94","154.6400","2001.04","3001.35","{\"sourceAmount\":12.94,\"sourceCurrency\":\"USD\",\"etbAmount\":2001.0415999999998,\"valueTax\":300.15623999999997,\"shipmentFee\":200.10415999999998,\"processingFee\":100.05207999999999,\"deliveryFee\":400,\"location\":\"jimma\",\"totalFees\":1000.3124799999999,\"totalCost\":3001.3540799999996,\"exchangeRate\":154.64,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-30 15:28:30");
INSERT INTO `price_calculation_history` VALUES("132","18","USD","21.42","154.5200","3309.82","4502.76","{\"sourceAmount\":21.42,\"sourceCurrency\":\"USD\",\"etbAmount\":3309.8184000000006,\"valueTax\":496.47276000000005,\"shipmentFee\":330.9818400000001,\"processingFee\":165.49092000000005,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":1192.9455200000002,\"totalCost\":4502.763920000001,\"exchangeRate\":154.52,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-31 14:06:03");
INSERT INTO `price_calculation_history` VALUES("133","18","USD","17.00","154.5200","2626.84","3614.89","{\"sourceAmount\":17,\"sourceCurrency\":\"USD\",\"etbAmount\":2626.84,\"valueTax\":394.026,\"shipmentFee\":262.684,\"processingFee\":131.342,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":988.052,\"totalCost\":3614.8920000000003,\"exchangeRate\":154.52,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2025-12-31 14:06:27");
INSERT INTO `price_calculation_history` VALUES("134","7","USD","9.85","154.1200","1518.08","2173.51","{\"sourceAmount\":9.85,\"sourceCurrency\":\"USD\",\"etbAmount\":1518.0819999999999,\"valueTax\":227.71229999999997,\"shipmentFee\":151.8082,\"processingFee\":75.9041,\"deliveryFee\":200,\"location\":\"addis_ababa\",\"totalFees\":655.4245999999999,\"totalCost\":2173.5065999999997,\"exchangeRate\":154.12,\"taxFeeDetails\":[{\"id\":4,\"name\":\"Delivery Fee\",\"description\":\"Fixed delivery fee\",\"rate_type\":\"fixed\",\"rate_value\":\"200.0000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":3,\"name\":\"Processing Fee\",\"description\":\"5% processing fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.0500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":2,\"name\":\"Shipment Fee\",\"description\":\"10% shipment fee\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1000\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"},{\"id\":1,\"name\":\"Value Tax\",\"description\":\"15% value tax on imported goods\",\"rate_type\":\"percentage\",\"rate_value\":\"0.1500\",\"is_active\":1,\"created_at\":\"2025-12-06 14:08:22\",\"updated_at\":\"2025-12-06 14:08:22\"}]}","2026-01-02 07:27:08");



-- 
-- Table structure for table `product_attributes` --
--

DROP TABLE IF EXISTS `product_attributes`;

CREATE TABLE `product_attributes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `attribute_id` int NOT NULL,
  `attribute_value_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `attribute_id` (`attribute_id`),
  KEY `attribute_value_id` (`attribute_value_id`),
  CONSTRAINT `product_attributes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_attributes_ibfk_2` FOREIGN KEY (`attribute_id`) REFERENCES `attributes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_attributes_ibfk_3` FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `product_attributes` --
--




-- 
-- Table structure for table `product_variants` --
--

DROP TABLE IF EXISTS `product_variants`;

CREATE TABLE `product_variants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `color` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `size` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int DEFAULT '0',
  `sku` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `price` decimal(10,2) DEFAULT NULL,
  `min_stock` int DEFAULT '5',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sku` (`sku`),
  KEY `idx_product_variants_product_id` (`product_id`),
  CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=241 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `product_variants` --
--

INSERT INTO `product_variants` VALUES("76","54","Black","L","1","176595814015","2025-12-17 07:55:40","2025-12-17 07:55:40","2990.00","1");
INSERT INTO `product_variants` VALUES("77","55","Black","S","1","176595858545","2025-12-17 08:03:05","2025-12-17 08:03:05","3460.00","1");
INSERT INTO `product_variants` VALUES("78","56","Biege","M","3","176595882953","2025-12-17 08:07:09","2025-12-22 14:20:56","2490.00","1");
INSERT INTO `product_variants` VALUES("79","57","Cream","M","1","176595904075","2025-12-17 08:10:40","2025-12-17 08:10:40","2990.00","1");
INSERT INTO `product_variants` VALUES("80","57","Cream","L","1","176595904044","2025-12-17 08:10:40","2025-12-17 08:10:40","2990.00","1");
INSERT INTO `product_variants` VALUES("81","58","Burgundy","M","2","176595922376","2025-12-17 08:13:43","2025-12-17 09:01:29","3399.00","1");
INSERT INTO `product_variants` VALUES("82","59","black","L","2","176595949762","2025-12-17 08:18:17","2025-12-17 08:18:17","3690.00","1");
INSERT INTO `product_variants` VALUES("83","60","Black","M","2","176595961962","2025-12-17 08:20:19","2025-12-17 08:20:19","2990.00","1");
INSERT INTO `product_variants` VALUES("84","61","Black","M","2","176596003555","2025-12-17 08:27:15","2025-12-17 08:27:15","2890.00","1");
INSERT INTO `product_variants` VALUES("85","61","Black","XL","3","176596003562","2025-12-17 08:27:15","2025-12-23 12:42:27","2890.00","1");
INSERT INTO `product_variants` VALUES("86","61","Black","L","1","176596003593","2025-12-17 08:27:15","2025-12-17 08:27:15","2890.00","1");
INSERT INTO `product_variants` VALUES("87","62","Cream","M","2","176596053925","2025-12-17 08:35:39","2025-12-17 10:15:40","3690.00","1");
INSERT INTO `product_variants` VALUES("88","63","Biege","M","2","176596082399","2025-12-17 08:40:23","2025-12-31 12:03:55","2790.00","1");
INSERT INTO `product_variants` VALUES("89","64","Black","XL","1","176596182271","2025-12-17 08:45:22","2025-12-28 09:22:21","2998.00","1");
INSERT INTO `product_variants` VALUES("90","64","Black","L","1","177596112219","2025-12-17 08:45:22","2025-12-19 06:55:05","2998.00","1");
INSERT INTO `product_variants` VALUES("91","64","Black","M","1","176696112202","2025-12-17 08:45:22","2025-12-19 06:55:41","2998.00","1");
INSERT INTO `product_variants` VALUES("92","61","black","S","3","176596125387","2025-12-17 08:47:33","2025-12-23 13:42:52","2890.00","1");
INSERT INTO `product_variants` VALUES("93","65","DarkRed","L","1","176596193171","2025-12-17 08:58:51","2026-01-01 13:23:03","4299.00","1");
INSERT INTO `product_variants` VALUES("94","65","Black","L","1","176596193188","2025-12-17 08:58:51","2025-12-17 08:58:51","4299.00","1");
INSERT INTO `product_variants` VALUES("95","65","NavyBlue","L","2","176596193157","2025-12-17 08:58:51","2025-12-17 09:49:36","4299.00","1");
INSERT INTO `product_variants` VALUES("96","58","Burgundy","L","1","176596208905","2025-12-17 09:01:29","2025-12-17 09:01:29","3399.00","1");
INSERT INTO `product_variants` VALUES("97","66","DarkRed","L","1","176596231019","2025-12-17 09:05:10","2025-12-17 09:05:10","3189.00","1");
INSERT INTO `product_variants` VALUES("98","67","Burgundy","L","2","176596269593","2025-12-17 09:11:35","2025-12-18 08:56:56","3690.00","1");
INSERT INTO `product_variants` VALUES("99","68","Red","L","3","176596323882","2025-12-17 09:20:38","2025-12-18 15:17:30","1290.00","1");
INSERT INTO `product_variants` VALUES("100","69","Black","L","1","176596345259","2025-12-17 09:24:12","2025-12-17 09:24:12","2490.00","1");
INSERT INTO `product_variants` VALUES("101","69","Black","XS","1","176596345235","2025-12-17 09:24:12","2025-12-17 09:24:12","2490.00","1");
INSERT INTO `product_variants` VALUES("102","70","Red","M","2","176596374891","2025-12-17 09:29:08","2025-12-30 08:19:42","2990.00","1");
INSERT INTO `product_variants` VALUES("103","70","Red","L","1","176596374860","2025-12-17 09:29:08","2025-12-30 11:30:47","2990.00","1");
INSERT INTO `product_variants` VALUES("104","70","Red","S","2","176596374884","2025-12-17 09:29:08","2025-12-30 08:19:25","2990.00","1");
INSERT INTO `product_variants` VALUES("105","71","Red","M","1","176596387891","2025-12-17 09:31:18","2025-12-17 09:31:18","2989.97","1");
INSERT INTO `product_variants` VALUES("106","71","NavyBlue","M","1","176596387860","2025-12-17 09:31:18","2025-12-17 09:31:18","2989.97","1");
INSERT INTO `product_variants` VALUES("107","62","Cream","L","1","176596424275","2025-12-17 09:37:22","2025-12-17 09:37:22","3690.00","1");
INSERT INTO `product_variants` VALUES("108","62","Burgundy","M","1","176596424220","2025-12-17 09:37:22","2025-12-17 09:37:22","3690.00","1");
INSERT INTO `product_variants` VALUES("109","72","Burgundy","XL","1","176596447151","2025-12-17 09:41:11","2025-12-17 09:41:11","3590.00","1");
INSERT INTO `product_variants` VALUES("110","73","Cream","L","1","176596462871","2025-12-17 09:43:48","2025-12-18 15:16:55","2890.00","1");
INSERT INTO `product_variants` VALUES("111","74","Jeans","XL","1","176596479978","2025-12-17 09:46:39","2025-12-17 09:46:39","2790.00","1");
INSERT INTO `product_variants` VALUES("112","74","Jeans","XXL","1","176596487652","2025-12-17 09:47:56","2025-12-17 09:48:08","2790.00","1");
INSERT INTO `product_variants` VALUES("113","75","NavyBlue","XL","1","176596510893","2025-12-17 09:51:48","2025-12-17 09:51:48","1820.00","1");
INSERT INTO `product_variants` VALUES("114","68","NavyBlue","M","3","176596630102","2025-12-17 10:11:41","2025-12-23 12:45:59","1290.00","1");
INSERT INTO `product_variants` VALUES("115","68","Black","M","2","176596630133","2025-12-17 10:11:41","2025-12-23 13:00:27","1290.00","1");
INSERT INTO `product_variants` VALUES("116","55","Black","M","4","176596657901","2025-12-17 10:16:19","2025-12-23 13:19:25","3460.00","1");
INSERT INTO `product_variants` VALUES("117","65","DarkRed","M","1","176596672041","2025-12-17 10:18:40","2026-01-01 13:22:48","4299.00","1");
INSERT INTO `product_variants` VALUES("118","76","Black","L","3","176596724269","2025-12-17 10:27:22","2025-12-23 12:49:54","2920.00","1");
INSERT INTO `product_variants` VALUES("119","77","Burgundy","L","1","176596736514","2025-12-17 10:29:25","2025-12-17 10:29:25","2980.00","1");
INSERT INTO `product_variants` VALUES("126","80","NavyBlue","L","3","176596822231","2025-12-17 10:43:42","2025-12-18 15:19:46","3390.00","1");
INSERT INTO `product_variants` VALUES("127","80","NavyBlue","M","1","176596822255","2025-12-17 10:43:42","2025-12-17 10:43:42","3390.00","1");
INSERT INTO `product_variants` VALUES("128","81","Burgundy","L","1","176596830229","2025-12-17 10:45:02","2025-12-17 10:45:02","3070.00","1");
INSERT INTO `product_variants` VALUES("133","83","Black","XL","3","176606868580","2025-12-18 14:38:05","2025-12-23 13:40:15","1490.00","1");
INSERT INTO `product_variants` VALUES("134","83","NavyBlue","XL","2","176606868521","2025-12-18 14:38:05","2025-12-23 13:40:15","1490.00","1");
INSERT INTO `product_variants` VALUES("135","83","NavyBlue","L","2","176606868588","2025-12-18 14:38:05","2025-12-23 13:40:15","1490.00","1");
INSERT INTO `product_variants` VALUES("136","83","Black","L","4","17660686855","2025-12-18 14:38:05","2026-01-01 13:28:23","1490.00","1");
INSERT INTO `product_variants` VALUES("137","83","NavyBlue","M","1","176606868559","2025-12-18 14:38:05","2025-12-23 13:40:15","1490.00","1");
INSERT INTO `product_variants` VALUES("138","80","NavyBlue","XL","3","176607061246","2025-12-18 15:10:12","2025-12-23 13:41:27","3390.00","1");
INSERT INTO `product_variants` VALUES("139","80","NavyBlue","S","1","176607061277","2025-12-18 15:10:12","2025-12-18 15:10:12","3390.00","1");
INSERT INTO `product_variants` VALUES("140","73","Cream","S","0","176607101553","2025-12-18 15:16:55","2025-12-22 09:54:34","2890.00","1");
INSERT INTO `product_variants` VALUES("141","84","Green","M","1","176607240597","2025-12-18 15:40:05","2025-12-18 15:40:05","3190.00","1");
INSERT INTO `product_variants` VALUES("142","84","Black","L","1","176607240580","2025-12-18 15:40:05","2025-12-18 15:40:05","3190.00","1");
INSERT INTO `product_variants` VALUES("143","84","Cream","L","1","176607240535","2025-12-18 15:40:05","2025-12-18 15:40:05","3190.00","1");
INSERT INTO `product_variants` VALUES("144","85","White","XL","1","176607266986","2025-12-18 15:44:29","2025-12-18 15:44:29",NULL,"5");
INSERT INTO `product_variants` VALUES("145","86","White","M","1","176607285734","2025-12-18 15:47:37","2025-12-18 15:47:37","2990.00","1");
INSERT INTO `product_variants` VALUES("146","86","White","L","0","176607285741","2025-12-18 15:47:37","2025-12-31 08:12:11","2990.00","1");
INSERT INTO `product_variants` VALUES("147","87","White","FreeSize","1","176607294934","2025-12-18 15:49:09","2025-12-18 15:49:09","3790.00","1");
INSERT INTO `product_variants` VALUES("148","88","Black","M","3","176612520981","2025-12-19 06:20:09","2026-01-01 13:24:54","990.00","2");
INSERT INTO `product_variants` VALUES("149","88","Black","S","5","176612520943","2025-12-19 06:20:09","2025-12-23 13:34:34","990.00","2");
INSERT INTO `product_variants` VALUES("150","88","Black","XL","0","176612520967","2025-12-19 06:20:09","2025-12-22 11:56:23","990.00","2");
INSERT INTO `product_variants` VALUES("151","88","Black","XXL","0","176612520974","2025-12-19 06:20:09","2025-12-29 13:08:24","990.00","2");
INSERT INTO `product_variants` VALUES("152","88","Black","L","5","176612520929","2025-12-19 06:20:09","2025-12-23 13:20:50","990.00","2");
INSERT INTO `product_variants` VALUES("153","76","Black","S","1","176612541382","2025-12-19 06:23:33","2025-12-19 06:23:33","2920.00","1");
INSERT INTO `product_variants` VALUES("154","75","NavyBlue","L","1","176612625143","2025-12-19 06:37:31","2025-12-19 06:37:31","1820.00","1");
INSERT INTO `product_variants` VALUES("155","75","Black","M","2","176612632745","2025-12-19 06:38:47","2025-12-19 06:38:47","1820.00","1");
INSERT INTO `product_variants` VALUES("156","89","Black","L","1","176612648029","2025-12-19 06:41:20","2025-12-19 06:41:20","1490.00","1");
INSERT INTO `product_variants` VALUES("157","63","Biege","L","3","176649386260","2025-12-23 12:44:22","2025-12-23 13:37:22","2790.00","1");
INSERT INTO `product_variants` VALUES("158","68","Black","L","1","176649428915","2025-12-23 12:51:29","2025-12-23 12:51:29","1290.00","1");
INSERT INTO `product_variants` VALUES("159","76","Black","M","1","176649438068","2025-12-23 12:53:00","2025-12-23 12:53:00","2920.00","1");
INSERT INTO `product_variants` VALUES("160","68","NavyBlue","XL","1","176649571437","2025-12-23 13:15:14","2025-12-23 13:15:14","1290.00","1");
INSERT INTO `product_variants` VALUES("161","56","Biege","L","1","176649593637","2025-12-23 13:18:56","2026-01-02 12:13:48","2490.00","1");
INSERT INTO `product_variants` VALUES("162","83","Black","M","1","176649721511","2025-12-23 13:40:15","2025-12-23 13:40:15","1490.00","1");
INSERT INTO `product_variants` VALUES("163","85","","L","1","176649744725","2025-12-23 13:44:07","2025-12-23 13:44:07",NULL,"5");
INSERT INTO `product_variants` VALUES("164","55","Red","S","1","176649769414","2025-12-23 13:48:14","2025-12-23 13:48:14","3460.00","1");
INSERT INTO `product_variants` VALUES("165","90","Black","XL","1","176650190511","2025-12-23 14:58:25","2025-12-23 14:58:25","3690.00","1");
INSERT INTO `product_variants` VALUES("166","90","Black","S","2","176650190504","2025-12-23 14:58:25","2025-12-23 14:58:25","3690.00","1");
INSERT INTO `product_variants` VALUES("167","90","Black","L","4","176650190566","2025-12-23 14:58:25","2025-12-23 14:58:25","3690.00","1");
INSERT INTO `product_variants` VALUES("168","90","Black","M","3","176650190542","2025-12-23 14:58:25","2025-12-23 14:58:25","3690.00","1");
INSERT INTO `product_variants` VALUES("169","55","Red","M","1","176650363649","2025-12-23 15:27:16","2025-12-23 15:27:16","3460.00","1");
INSERT INTO `product_variants` VALUES("170","91","Biege","M","1","176655882817","2025-12-24 06:47:08","2025-12-24 06:47:08","4130.00","1");
INSERT INTO `product_variants` VALUES("171","92","NavyBlue","1XL","0","176656208128","2025-12-24 07:41:21","2025-12-25 13:59:50","2900.00","1");
INSERT INTO `product_variants` VALUES("172","92","NavyBlue","2XL","1","176656208197","2025-12-24 07:41:21","2026-01-01 13:25:50","2900.00","1");
INSERT INTO `product_variants` VALUES("173","93","Biege","L","1","176656292004","2025-12-24 07:55:20","2025-12-24 07:55:20","8597.00","1");
INSERT INTO `product_variants` VALUES("174","94","Biege","L","0","176656694570","2025-12-24 09:02:25","2025-12-25 11:51:31","2290.00","1");
INSERT INTO `product_variants` VALUES("175","94","White","M","1","176656694563","2025-12-24 09:02:25","2025-12-24 09:02:25","2290.00","1");
INSERT INTO `product_variants` VALUES("176","95","Biege","1XL","1","176656723959","2025-12-24 09:07:19","2025-12-24 09:07:19","3615.33","1");
INSERT INTO `product_variants` VALUES("177","96","Biege","32","0","176734278451","2026-01-02 08:35:36","2026-01-02 08:35:36","1550.00","5");
INSERT INTO `product_variants` VALUES("178","96","Biege","33","0","176734278452","2026-01-02 08:35:36","2026-01-02 08:35:36","1550.00","5");
INSERT INTO `product_variants` VALUES("179","96","Biege","34","1","176734278453","2026-01-02 08:35:36","2026-01-02 08:39:20","1550.00","5");
INSERT INTO `product_variants` VALUES("180","96","Biege","36","6","176734278454","2026-01-02 08:35:36","2026-01-02 08:39:20","1550.00","5");
INSERT INTO `product_variants` VALUES("181","96","Biege","38","0","176734278455","2026-01-02 08:35:36","2026-01-02 12:18:25","1550.00","5");
INSERT INTO `product_variants` VALUES("182","96","Olive","32","4","176734278456","2026-01-02 08:35:36","2026-01-02 08:39:20","1550.00","5");
INSERT INTO `product_variants` VALUES("183","96","Olive","33","0","176734278457","2026-01-02 08:35:36","2026-01-02 08:35:36","1550.00","5");
INSERT INTO `product_variants` VALUES("184","96","Olive","34","0","176734278458","2026-01-02 08:35:36","2026-01-02 08:35:36","1550.00","5");
INSERT INTO `product_variants` VALUES("185","96","Olive","36","3","176734278459","2026-01-02 08:35:36","2026-01-02 08:39:20","1550.00","5");
INSERT INTO `product_variants` VALUES("186","96","Olive","38","0","176734279451","2026-01-02 08:35:36","2026-01-02 08:35:36","1550.00","5");
INSERT INTO `product_variants` VALUES("187","96","Brown","32","2","176734279452","2026-01-02 08:35:36","2026-01-02 08:39:20","1550.00","5");
INSERT INTO `product_variants` VALUES("188","96","Brown","33","0","176734279453","2026-01-02 08:35:36","2026-01-02 08:43:30","1550.00","5");
INSERT INTO `product_variants` VALUES("189","96","Brown","34","1","176734279454","2026-01-02 08:35:36","2026-01-02 08:39:20","1550.00","5");
INSERT INTO `product_variants` VALUES("190","96","Brown","36","1","176734279455","2026-01-02 08:35:36","2026-01-02 08:39:20","1550.00","5");
INSERT INTO `product_variants` VALUES("191","96","Brown","38","1","176734279456","2026-01-02 08:35:36","2026-01-02 08:39:20","1550.00","5");
INSERT INTO `product_variants` VALUES("192","96","Kaki","32","3","176734279457","2026-01-02 08:35:36","2026-01-02 08:39:20","1550.00","5");
INSERT INTO `product_variants` VALUES("193","96","Kaki","33","0","176734279458","2026-01-02 08:35:36","2026-01-02 08:35:36","1550.00","5");
INSERT INTO `product_variants` VALUES("194","96","Kaki","34","0","176734279459","2026-01-02 08:35:36","2026-01-02 08:35:36","1550.00","5");
INSERT INTO `product_variants` VALUES("195","96","Kaki","36","4","176734277459","2026-01-02 08:35:36","2026-01-02 08:39:20","1550.00","5");
INSERT INTO `product_variants` VALUES("196","96","Kaki","38","0","186734278452","2026-01-02 08:35:36","2026-01-02 12:18:47","1550.00","5");
INSERT INTO `product_variants` VALUES("197","97","Sky-Blue","31","0","176734384661","2026-01-02 08:52:16","2026-01-02 08:52:16","1090.00","5");
INSERT INTO `product_variants` VALUES("198","97","Sky-Blue","32","3","176734384662","2026-01-02 08:52:16","2026-01-02 08:52:16","1090.00","5");
INSERT INTO `product_variants` VALUES("199","97","Sky-Blue","33","2","176734384663","2026-01-02 08:52:16","2026-01-02 08:52:16","1090.00","5");
INSERT INTO `product_variants` VALUES("200","97","Sky-Blue","34","2","176734384664","2026-01-02 08:52:16","2026-01-02 08:52:16","1090.00","5");
INSERT INTO `product_variants` VALUES("201","97","Sky-Blue","36","3","176734384665","2026-01-02 08:52:16","2026-01-02 08:52:16","1090.00","5");
INSERT INTO `product_variants` VALUES("202","97","Sky-Blue","38","2","176734384666","2026-01-02 08:52:16","2026-01-02 08:52:16","1090.00","5");
INSERT INTO `product_variants` VALUES("203","97","Steel-Blue","31","0","176734384667","2026-01-02 08:52:16","2026-01-02 08:52:16","1090.00","5");
INSERT INTO `product_variants` VALUES("204","97","Steel-Blue","32","3","176734384668","2026-01-02 08:52:16","2026-01-02 08:52:16","1090.00","5");
INSERT INTO `product_variants` VALUES("205","97","Steel-Blue","33","1","176734384669","2026-01-02 08:52:16","2026-01-02 08:53:36","1090.00","5");
INSERT INTO `product_variants` VALUES("206","97","Steel-Blue","34","2","176734384671","2026-01-02 08:52:16","2026-01-02 08:52:16","1090.00","5");
INSERT INTO `product_variants` VALUES("207","97","Steel-Blue","36","4","176734384672","2026-01-02 08:52:16","2026-01-02 08:52:16","1090.00","5");
INSERT INTO `product_variants` VALUES("208","97","Steel-Blue","38","4","176734384673","2026-01-02 08:52:16","2026-01-02 08:52:16","1090.00","5");
INSERT INTO `product_variants` VALUES("209","98","White-Fade","31","4","176734417951","2026-01-02 09:02:52","2026-01-02 09:02:52","1090.00","5");
INSERT INTO `product_variants` VALUES("210","98","White-Fade","32","0","176734417952","2026-01-02 09:02:52","2026-01-02 09:02:52","1090.00","5");
INSERT INTO `product_variants` VALUES("211","98","White-Fade","33","2","176734417953","2026-01-02 09:02:52","2026-01-02 09:02:52","1090.00","5");
INSERT INTO `product_variants` VALUES("212","98","White-Fade","34","0","176734417954","2026-01-02 09:02:52","2026-01-02 09:02:52","1090.00","5");
INSERT INTO `product_variants` VALUES("213","98","White-Fade","36","1","176734417956","2026-01-02 09:02:52","2026-01-02 09:02:52","1090.00","5");
INSERT INTO `product_variants` VALUES("214","98","White-Fade","38","1","176734417957","2026-01-02 09:02:52","2026-01-02 09:02:52","1090.00","5");
INSERT INTO `product_variants` VALUES("215","98","Dark-Fade","31","0","176734417958","2026-01-02 09:02:52","2026-01-02 09:02:52","1090.00","5");
INSERT INTO `product_variants` VALUES("216","98","Dark-Fade","32","0","176734417959","2026-01-02 09:02:52","2026-01-02 09:02:52","1090.00","5");
INSERT INTO `product_variants` VALUES("217","98","Dark-Fade","33","0","176734417941","2026-01-02 09:02:52","2026-01-02 09:02:52","1090.00","5");
INSERT INTO `product_variants` VALUES("218","98","Dark-Fade","34","0","176734417942","2026-01-02 09:02:52","2026-01-02 09:02:52","1090.00","5");
INSERT INTO `product_variants` VALUES("219","98","Dark-Fade","36","5","176734417944","2026-01-02 09:02:52","2026-01-02 09:02:52","1090.00","5");
INSERT INTO `product_variants` VALUES("220","98","Dark-Fade","38","2","176734417945","2026-01-02 09:02:52","2026-01-02 09:02:52","1090.00","5");
INSERT INTO `product_variants` VALUES("221","99","","8","0","176734509482","2026-01-02 09:11:34","2026-01-02 09:11:34","590.00","5");
INSERT INTO `product_variants` VALUES("222","99","","10","7","176734509451","2026-01-02 09:11:34","2026-01-02 09:11:34","590.00","5");
INSERT INTO `product_variants` VALUES("223","99","","12","6","176734509437","2026-01-02 09:11:34","2026-01-02 09:38:16","590.00","5");
INSERT INTO `product_variants` VALUES("224","99","","14","5","176734509406","2026-01-02 09:11:34","2026-01-02 09:11:34","590.00","5");
INSERT INTO `product_variants` VALUES("225","100","","2-3","4","176734574596","2026-01-02 09:22:25","2026-01-02 09:22:25","590.00","5");
INSERT INTO `product_variants` VALUES("226","100","","4","4","176734574534","2026-01-02 09:22:25","2026-01-02 09:22:25","590.00","5");
INSERT INTO `product_variants` VALUES("227","100","","5-6","9","176734574510","2026-01-02 09:22:25","2026-01-02 09:22:25","590.00","5");
INSERT INTO `product_variants` VALUES("228","100","","7","9","176734574572","2026-01-02 09:22:25","2026-01-02 09:22:25","590.00","5");
INSERT INTO `product_variants` VALUES("229","101","","2-3","4","176734604621","2026-01-02 09:28:07","2026-01-02 09:28:07","590.00","5");
INSERT INTO `product_variants` VALUES("230","101","","4","4","176734604622176734604623","2026-01-02 09:28:07","2026-01-02 09:28:07","590.00","5");
INSERT INTO `product_variants` VALUES("231","101","","5-6","8","176734604624","2026-01-02 09:28:07","2026-01-02 09:28:07","590.00","5");
INSERT INTO `product_variants` VALUES("232","101","","7","8","176734604625","2026-01-02 09:28:07","2026-01-02 09:28:07","590.00","5");
INSERT INTO `product_variants` VALUES("233","102","","2-3","5","176734635721","2026-01-02 09:33:13","2026-01-02 09:39:21","590.00","5");
INSERT INTO `product_variants` VALUES("234","102","","4","5","176734635722","2026-01-02 09:33:13","2026-01-02 09:33:13","590.00","5");
INSERT INTO `product_variants` VALUES("235","102","","5-6","10","176734635724","2026-01-02 09:33:13","2026-01-02 09:33:13","590.00","5");
INSERT INTO `product_variants` VALUES("236","102","","7","10","176734635725","2026-01-02 09:33:13","2026-01-02 09:33:13","590.00","5");
INSERT INTO `product_variants` VALUES("237","103","","8","3","176734666246","2026-01-02 09:37:42","2026-01-02 09:37:42","590.00","5");
INSERT INTO `product_variants` VALUES("238","103","","10","7","176734666284","2026-01-02 09:37:42","2026-01-02 09:37:42","590.00","5");
INSERT INTO `product_variants` VALUES("239","103","","12","13","176734666253","2026-01-02 09:37:42","2026-01-02 09:37:42","590.00","5");
INSERT INTO `product_variants` VALUES("240","103","","14","4","176734666208","2026-01-02 09:37:42","2026-01-02 09:37:42","590.00","5");



-- 
-- Table structure for table `products` --
--

DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int DEFAULT '0',
  `price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `min_stock` int DEFAULT '5',
  `supplier_id` int DEFAULT NULL,
  `supplier` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `category_id` int DEFAULT NULL,
  `brand_id` int DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `size` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `color` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `has_variants` tinyint(1) DEFAULT '0',
  `avg_daily_sales` decimal(10,2) DEFAULT '0.00',
  `reorder_enabled` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `supplier_id` (`supplier_id`),
  KEY `fk_product_category` (`category_id`),
  KEY `fk_product_brand` (`brand_id`),
  CONSTRAINT `fk_product_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `products` --
--

INSERT INTO `products` VALUES("54","176595814091","Elegant Off-Shoulder Ruffle Hem Black Dress","","Clothes","1","2990.00","2990.00","1",NULL,NULL,"shelf-2-Bag","2025-12-17 07:55:40","2025-12-17 07:55:40","5","4","uploads/products/694261fc43331_1765958140.png","176595814091",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("55","176595858538","Elegant V-Neck Floral Print A-Line Maxi Dress with Pockets",NULL,"Clothes","7","3460.00","3460.00","1",NULL,NULL,"shelf-2-Bag","2025-12-17 08:03:05","2025-12-23 15:27:16","5","4","uploads/products/694263b9da21a_1765958585.png","176595858538",NULL,NULL,"1","0.03","1");
INSERT INTO `products` VALUES("56","176595882991","Elegant of shoulder a line Maxi Dress",NULL,"Clothes","4","2490.00","2490.00","1",NULL,NULL,"shelf-2-Bag","2025-12-17 08:07:09","2025-12-23 13:18:56","5","4","uploads/products/694264adcb891_1765958829.png","176595882991",NULL,NULL,"1","0.03","1");
INSERT INTO `products` VALUES("57","176595904099","Elengza New women Hollow out collar color block tie waist side Ruched hem sleeve","","Clothes","2","2990.00","2990.00","1",NULL,NULL,"shelf-2-Bag","2025-12-17 08:10:40","2025-12-17 08:10:40","5","4","uploads/products/6942658070316_1765959040.png","176595904099",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("58","176595922369","Modelyn Women\'s solid color sleeveless Ruffle lace Trim Bodycon Dress Long Eveni",NULL,"Clothes","3","3399.00","3399.00","1",NULL,NULL,"shelf-2-Bag","2025-12-17 08:13:43","2025-12-17 09:01:29","5","4","uploads/products/6942663707126_1765959223.png","176595922369",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("59","176595949717","Maxi Dress","","Clothes","2","3690.00","3690.00","1",NULL,NULL,"shelf-2-Bag","2025-12-17 08:18:17","2025-12-17 08:18:17","5","4","uploads/products/69426749276a8_1765959497.png","176595949717",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("60","176595961986","Women\'s Elegant Black and white Abstract print pencil dress","","Clothes","2","2990.00","2990.00","1",NULL,NULL,"shelf-2-Bag","2025-12-17 08:20:19","2025-12-17 08:20:19","5","4","uploads/products/694267c369e0c_1765959619.png","176595961986",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("61","176596003579","Women\'s Elegant A-Line Short Sleeve Dress with Geometric Print & Waist Tie Belt",NULL,"Clothes","9","2890.00","2890.00","1",NULL,NULL,"shelf-2-Bag","2025-12-17 08:27:15","2025-12-23 13:42:52","5","4","uploads/products/694269630beaa_1765960035.png","176596003579",NULL,NULL,"1","0.03","1");
INSERT INTO `products` VALUES("62","176596053994","womens Elegant sleeveless jamsuit with stand collar",NULL,"Clothes","4","3690.00","3690.00","1",NULL,NULL,"shelf-3-Bag","2025-12-17 08:35:39","2025-12-17 10:15:40","5","4","uploads/products/69426b5b1490d_1765960539.png","176596053994",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("63","176612707856","Two-Piece Crop Top and Wide-Leg Pant Set",NULL,"Clothes","5","2790.00","2790.00","1",NULL,NULL,"shelf-2-Bag","2025-12-17 08:40:23","2025-12-31 12:03:55","5","4","uploads/products/69426c77cf4a4_1765960823.png","176612707856",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("64","176612728431","Selianne french Elegant Casual Vacation square Neck Lotus Leaf Flying sleeves po",NULL,"Clothes","3","2998.00","2998.00","1",NULL,NULL,"shelf-2-Bag","2025-12-17 08:45:22","2025-12-29 20:07:34","5","4","uploads/products/69426da20eb8a_1765961122.png","176612728431",NULL,NULL,"1","0.03","1");
INSERT INTO `products` VALUES("65","176596193133","MIUSOL solid Asymmetrical Sleeve Ruffle Trim Cocktail Party Fitted Dress",NULL,"Clothes","5","4299.00","4299.00","1",NULL,NULL,"shelf-2-Bag","2025-12-17 08:58:51","2026-01-01 13:23:03","5","4","uploads/products/694270cb70ebd_1765961931.png","176596193133",NULL,NULL,"1","0.07","1");
INSERT INTO `products` VALUES("66","176596231026","Amorya Solid Scallop Trim Dress With Belt","","Clothes","1","3189.00","3189.00","1",NULL,NULL,"shelf-2-Bag","2025-12-17 09:05:10","2025-12-17 09:05:10","5","4","uploads/products/6942724698fc4_1765962310.png","176596231026",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("67","176596269500","Alexandranx Elegant Minimalist Daily Wear Unique Solid Color Short Sleeve Fitted",NULL,"Clothes","2","3690.00","3690.00","1",NULL,NULL,"shelf-2-Bag","2025-12-17 09:11:35","2025-12-18 09:39:19","5","4","uploads/products/694273c725a51_1765962695.png","176596269500",NULL,NULL,"1","0.07","1");
INSERT INTO `products` VALUES("68","176596323844","Floral print Teired ruched elastic waist vacation skirt",NULL,"Clothes","10","1290.00","1290.00","1",NULL,NULL,"shelf-3-Bag","2025-12-17 09:20:38","2025-12-23 13:15:14","5","4","uploads/products/694275e6c1ab2_1765963238.png","176596323844",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("69","176596345273","Elegant Black Mesh Bodycon Midi Dress with Deep V-Neck & Lace Detailing",NULL,"Clothes","2","2490.00","2490.00","1",NULL,NULL,"shelf-3-Bag","2025-12-17 09:24:12","2025-12-17 09:34:46","5","4","uploads/products/6942793653d08_1765964086.png","176596345273",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("70","176596374853","The Power Play Sheath Dress - Ruby Red Cutout Neck",NULL,"Clothes","5","2990.00","2990.00","1",NULL,NULL,"shelf-1-Bag","2025-12-17 09:29:08","2025-12-30 11:30:47","5","4","uploads/products/694277e400f91_1765963748.png","176596374853",NULL,NULL,"1","0.10","1");
INSERT INTO `products` VALUES("71","176596387884","Elengza New Fashionable Solid RED Mini collar Sleeveless Sexy hollow-out Mesh","","Clothes","2","2989.97","2989.97","1",NULL,NULL,"shelf-3-Bag","2025-12-17 09:31:18","2025-12-17 09:31:18","5","4","uploads/products/6942786601f57_1765963878.png","176596387884",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("72","176596447168","Elenzga women\'s spring/summer French chic Elegant minimalist commuting/office","","Clothes","1","3590.00","3590.00","1",NULL,NULL,"shelf-1-Bag","2025-12-17 09:41:11","2025-12-24 12:24:21","5","4","uploads/products/69427ab747f18_1765964471.png","176596447168",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("73","176596462840","Women\'S Elegant Off-Shoulder Polka Dot Jumpsuit",NULL,"Clothes","1","2890.00","2890.00","1",NULL,NULL,"shelf-3-Bag","2025-12-17 09:43:48","2025-12-22 09:54:49","5","4","uploads/products/69427b544b6ea_1765964628.png","176596462840",NULL,NULL,"1","0.03","1");
INSERT INTO `products` VALUES("74","176596479954","Women\'s Denim Maxi dress",NULL,"Clothes","2","2790.00","2790.00","1",NULL,NULL,"shelf-3-Bag","2025-12-17 09:46:39","2025-12-17 09:48:08","5","4","uploads/products/69427bff02521_1765964799.png","176596479954",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("75","176596510800","Women\'s solid color simple daily slit skirt",NULL,"Clothes","4","1820.00","1820.00","1",NULL,NULL,"shelf-3-Bag","2025-12-17 09:51:48","2025-12-19 06:38:47","5","4","uploads/products/69427d3477f0a_1765965108.png","176596510800",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("76","176596724238","ChicMe Sexy Party Midi Dress",NULL,"Clothes","5","2920.00","2920.00","1",NULL,NULL,"shelf-3-Bag","2025-12-17 10:27:22","2025-12-23 12:53:00","5","4","uploads/products/6942858a8835d_1765967242.png","176596724238",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("77","176596736569","Privé Guipure Lace Panel Bodycon Dress","","Clothes","1","2980.00","2980.00","1",NULL,NULL,"shelf-3-Bag","2025-12-17 10:29:25","2025-12-17 10:29:25","5","4","uploads/products/69428605e0b1d_1765967365.png","176596736569",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("80","176596822262","The \'Chevron Cruise\' 3-Piece Leisure Set",NULL,"Clothes","8","3390.00","3390.00","1",NULL,NULL,"shelf-3-Bag","2025-12-17 10:43:42","2025-12-23 13:41:27","5","4","uploads/products/6942895ebfb5d_1765968222.png","176596822262",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("81","176596830267","Classic Red Bodycon Party Dress","","Clothes","1","3070.00","3070.00","1",NULL,NULL,"shelf-3-Bag","2025-12-17 10:45:02","2025-12-17 10:45:02","5","4","uploads/products/694289ae67720_1765968302.png","176596830267",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("83","176649716845","Women\'s high waisted Yoga pants",NULL,"Clothes","13","1490.00","1490.00","1",NULL,NULL,"shelf-3-Bag","2025-12-18 14:38:05","2026-01-01 13:28:23","5","4","uploads/products/694411cd17701_1766068685.png","176649716845",NULL,NULL,"1","0.03","1");
INSERT INTO `products` VALUES("84","176607240566","Off-the-Shoulder Party dress","","Clothes","3","3190.00","3190.00","1",NULL,NULL,"shelf-1-Bag","2025-12-18 15:40:05","2025-12-18 15:40:05","5","4","uploads/products/69442055c3bb3_1766072405.png","176607240566",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("85","176607266924","Women\'S Purple Cable Cardigan with Cherry & Leaf Embellishments",NULL,"Clothes","2","0.00","4190.00","5",NULL,NULL,"shelf-1-Bag","2025-12-18 15:44:29","2025-12-23 13:44:07","5","4","uploads/products/6944215d5e161_1766072669.png","176607266924",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("86","176607285796","Cherry Embroidered crop knit cardigan","","Clothes","1","2990.00","2990.00","1",NULL,NULL,"shelf-1-Bag","2025-12-18 15:47:37","2025-12-31 08:26:11","5","4","uploads/products/69442219d75fa_1766072857.png","176607285796",NULL,NULL,"1","0.03","1");
INSERT INTO `products` VALUES("87","176607294941","Chic Color Block Floral Cardigan - V-Neck","","Clothes","1","3790.00","3790.00","1",NULL,NULL,"shelf-1-Bag","2025-12-18 15:49:09","2025-12-18 15:49:09","5","4","uploads/products/694422752d4e0_1766072949.png","176607294941",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("88","176612520943","Women\'s Mesh Leggings, Tights, Breathable","","Clothes","13","990.00","990.00","2",NULL,NULL,"shelf-3-Bag","2025-12-19 06:20:09","2026-01-01 13:24:54","5","4","uploads/products/6944ee990e2de_1766125209.png","176612520943",NULL,NULL,"1","0.13","1");
INSERT INTO `products` VALUES("89","176612648036","Women\'s Maxi skirt tiny floral casual Elastic waist with pockets ruffeled hem la","","Clothes","1","1490.00","1490.00","1",NULL,NULL,"shelf-3-Bag","2025-12-19 06:41:20","2025-12-19 06:41:20","5","4","uploads/products/6944f390160c8_1766126480.png","176612648036",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("90","176650189607","Summer Vacation Elegant Square Neck Short Sleeve Slim Top",NULL,"Clothes","10","3690.00","3690.00","1",NULL,NULL,"shelf-4-Bag","2025-12-23 14:58:25","2025-12-24 11:58:20","5","4","uploads/products/694aae11082ed_1766501905.png","176650189607",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("91","176655882848","Miaspire 2pcs Women Solid Color Crew Neck 3/4 Sleeve Top And Pants Set","","Clothes","1","4130.00","4130.00","1",NULL,NULL,"shelf-4-Bag","2025-12-24 06:47:08","2025-12-24 06:47:08","5","4","uploads/products/694b8c6c39e7a_1766558828.png","176655882848",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("92","176656208166","Plus Size Colorblock Print Batwing Sleeve Dress, Casual Resort Wear","","Clothes","1","2900.00","2900.00","1",NULL,NULL,"shelf-4-Bag","2025-12-24 07:41:21","2026-01-01 13:25:50","5","3","uploads/products/694b9921be74a_1766562081.png","176656208166",NULL,NULL,"1","0.07","1");
INSERT INTO `products` VALUES("93","176656292004","Arave Women\'s Casual Versatile Long Fur Coat, Grey, Autumn/Winter","","Clothes","1","8597.00","8597.00","1",NULL,NULL,"shelf-4-Bag","2025-12-24 07:55:20","2025-12-24 07:55:20","5","4","uploads/products/694b9c6874503_1766562920.png","176656292004",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("94","176656694051","Elenzga Women\'s Elegant Black & White Printed Sleeveless Halter Neck Dress","","Clothes","1","2290.00","2290.00","1",NULL,NULL,"shelf-4-Bag","2025-12-24 09:02:25","2025-12-25 11:58:53","5","4","uploads/products/694bac210cf3c_1766566945.png","176656694051",NULL,NULL,"1","0.03","1");
INSERT INTO `products` VALUES("95","176656723935","Elenzga Plus Size Women\'s Printed Fabric Waist Cincher Jumpsuit, Elegant Design","","Clothes","1","3615.33","3615.33","1",NULL,NULL,"shelf-4-Bag","2025-12-24 09:07:19","2025-12-24 09:07:19","5","4","uploads/products/694bad475fb37_1766567239.png","176656723935",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("96","176734287229","The Essential Textured Chino kaki jeans",NULL,"Clothes","26","1550.00","1400.00","5",NULL,NULL,"Shelf-3C","2026-01-02 08:35:36","2026-01-02 12:18:47","5","5","uploads/products/69578358de471_1767342936.png","176734287229",NULL,NULL,"1","0.03","1");
INSERT INTO `products` VALUES("97","176734393661","The CRUX Denim Blue Slim-Fit Men\'s Jeans","","Clothes","26","1090.00","930.00","5",NULL,NULL,"shelf-3B and 3D","2026-01-02 08:52:16","2026-01-02 09:42:06","5","5","uploads/products/69578740960af_1767343936.png","176734393661",NULL,NULL,"1","0.03","1");
INSERT INTO `products` VALUES("98","176734457264","The CRUX Men\'s Slim-Fit Jeans","","Clothes","15","1090.00","930.00","5",NULL,NULL,"shelf-3B and 3D","2026-01-02 09:02:52","2026-01-02 09:02:52","5","5","uploads/products/695789bc719cc_1767344572.png","176734457264",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("99","176734509499","Marvel Spider-Man Side Panel Graphic Tee","","Clothes","18","590.00","400.00","5",NULL,NULL,"Cardboard-Box","2026-01-02 09:11:34","2026-01-02 09:38:16","5",NULL,"uploads/products/69578bc6dc8c1_1767345094.png","176734509499",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("100","176734574510","Kids Mickey Mouse HELLO Graphic Tee","","Clothes","26","590.00","400.00","5",NULL,NULL,"","2026-01-02 09:22:25","2026-01-02 09:22:25","5",NULL,"uploads/products/69578e516ad15_1767345745.png","176734574510",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("101","176734604620","Disney Frozen II Character Print T-Shirt in Mustard Yellow","","Clothes","24","590.00","400.00","5",NULL,NULL,"Cardboard-Box","2026-01-02 09:28:07","2026-01-02 09:28:07","5",NULL,"uploads/products/69578fa70cb36_1767346087.png","176734604620",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("102","176734635723","Kids\' Classic Disney Mickey Mouse Face Graphic T-Shirt | White Cartoon Tee","","Clothes","30","590.00","400.00","5",NULL,NULL,"Cardboard-Box","2026-01-02 09:33:13","2026-01-02 09:39:21","5",NULL,"uploads/products/695790d9c600c_1767346393.png","176734635723",NULL,NULL,"1","0.00","1");
INSERT INTO `products` VALUES("103","176734665966","Kids\' Classic Disney Mickey Mouse Face Graphic T-Shirt | White Cartoon Tee","","Clothes","27","590.00","400.00","5",NULL,NULL,"","2026-01-02 09:37:42","2026-01-02 09:37:42","5",NULL,"uploads/products/695791e65f197_1767346662.png","176734665966",NULL,NULL,"1","0.00","1");



-- 
-- Table structure for table `reorder_settings` --
--

DROP TABLE IF EXISTS `reorder_settings`;

CREATE TABLE `reorder_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `reorder_point` int DEFAULT '0',
  `reorder_quantity` int DEFAULT '0',
  `lead_time_days` int DEFAULT '7',
  `safety_stock` int DEFAULT '0',
  `auto_generate_po` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `last_calculated` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product` (`product_id`),
  CONSTRAINT `reorder_settings_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1033 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `reorder_settings` --
--

INSERT INTO `reorder_settings` VALUES("121","54","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("122","55","1","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("123","56","1","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("124","57","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("125","58","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("126","59","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("127","60","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("128","61","1","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("129","62","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("130","63","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("131","64","1","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("132","65","1","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("133","66","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("134","67","1","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("135","68","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("136","69","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("137","70","1","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("138","71","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("139","72","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("140","73","1","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("141","74","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("142","75","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("143","76","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("144","77","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("147","80","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("148","81","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 09:39:19","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("177","83","1","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 17:13:00","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("178","84","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 17:13:00","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("179","85","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 17:13:00","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("180","86","1","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 17:13:00","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("181","87","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-18 17:13:00","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("245","88","1","10","7","0","0","1","2026-01-02 09:42:06","2025-12-19 07:20:52","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("246","89","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-19 07:20:52","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("511","90","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-25 09:51:35","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("512","91","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-25 09:51:35","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("513","92","1","10","7","0","0","1","2026-01-02 09:42:06","2025-12-25 09:51:35","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("514","93","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-25 09:51:35","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("515","94","1","10","7","0","0","1","2026-01-02 09:42:06","2025-12-25 09:51:35","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("516","95","0","10","7","0","0","1","2026-01-02 09:42:06","2025-12-25 09:51:35","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("985","96","1","10","7","0","0","1","2026-01-02 09:42:06","2026-01-02 08:45:05","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("1026","97","1","10","7","0","0","1","2026-01-02 09:42:06","2026-01-02 09:42:06","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("1027","98","0","10","7","0","0","1","2026-01-02 09:42:06","2026-01-02 09:42:06","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("1028","99","0","10","7","0","0","1","2026-01-02 09:42:06","2026-01-02 09:42:06","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("1029","100","0","10","7","0","0","1","2026-01-02 09:42:06","2026-01-02 09:42:06","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("1030","101","0","10","7","0","0","1","2026-01-02 09:42:06","2026-01-02 09:42:06","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("1031","102","0","10","7","0","0","1","2026-01-02 09:42:06","2026-01-02 09:42:06","2026-01-02 09:42:06");
INSERT INTO `reorder_settings` VALUES("1032","103","0","10","7","0","0","1","2026-01-02 09:42:06","2026-01-02 09:42:06","2026-01-02 09:42:06");



-- 
-- Table structure for table `route_optimization_settings` --
--

DROP TABLE IF EXISTS `route_optimization_settings`;

CREATE TABLE `route_optimization_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `route_id` int DEFAULT NULL,
  `algorithm` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'nearest_neighbor',
  `time_windows` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `vehicle_capacity` int DEFAULT NULL,
  `constraints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `route_id` (`route_id`),
  CONSTRAINT `route_optimization_settings_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `route_optimization_settings_chk_1` CHECK (json_valid(`time_windows`)),
  CONSTRAINT `route_optimization_settings_chk_2` CHECK (json_valid(`constraints`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `route_optimization_settings` --
--




-- 
-- Table structure for table `route_performance` --
--

DROP TABLE IF EXISTS `route_performance`;

CREATE TABLE `route_performance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `route_id` int NOT NULL,
  `driver_id` int DEFAULT NULL,
  `vehicle_id` int DEFAULT NULL,
  `planned_distance` decimal(10,2) DEFAULT NULL COMMENT 'in km',
  `actual_distance` decimal(10,2) DEFAULT NULL COMMENT 'in km',
  `planned_duration` int DEFAULT NULL COMMENT 'in minutes',
  `actual_duration` int DEFAULT NULL COMMENT 'in minutes',
  `fuel_consumed` decimal(10,2) DEFAULT NULL COMMENT 'in liters',
  `fuel_cost` decimal(10,2) DEFAULT NULL,
  `labor_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `deliveries_planned` int DEFAULT '0',
  `deliveries_completed` int DEFAULT '0',
  `deliveries_failed` int DEFAULT '0',
  `on_time_percentage` decimal(5,2) DEFAULT NULL,
  `customer_rating` decimal(3,2) DEFAULT NULL COMMENT 'average rating 1-5',
  `carbon_footprint` decimal(10,2) DEFAULT NULL COMMENT 'CO2 in kg',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `driver_id` (`driver_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `idx_route` (`route_id`),
  KEY `idx_completed_at` (`completed_at`),
  CONSTRAINT `route_performance_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `route_performance_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `route_performance_ibfk_3` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `route_performance` --
--




-- 
-- Table structure for table `route_stops` --
--

DROP TABLE IF EXISTS `route_stops`;

CREATE TABLE `route_stops` (
  `id` int NOT NULL AUTO_INCREMENT,
  `route_id` int DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci NOT NULL,
  `coordinates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `stop_number` int DEFAULT NULL,
  `driver_id` int DEFAULT NULL,
  `distance_from_previous` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `route_id` (`route_id`),
  CONSTRAINT `route_stops_ibfk_1` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `route_stops_chk_1` CHECK (json_valid(`coordinates`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `route_stops` --
--




-- 
-- Table structure for table `route_templates` --
--

DROP TABLE IF EXISTS `route_templates`;

CREATE TABLE `route_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `recurrence` enum('daily','weekly','monthly','custom') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recurrence_pattern` json DEFAULT NULL COMMENT 'e.g., {"days": ["monday", "wednesday"]}',
  `warehouse_location` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `warehouse_lat` decimal(10,8) DEFAULT NULL,
  `warehouse_lon` decimal(11,8) DEFAULT NULL,
  `addresses` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'newline separated addresses',
  `driver_count` int DEFAULT '1',
  `country_code` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT 'et',
  `created_by` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `route_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `route_templates` --
--




-- 
-- Table structure for table `routes` --
--

DROP TABLE IF EXISTS `routes`;

CREATE TABLE `routes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `warehouse_location` text COLLATE utf8mb4_general_ci,
  `warehouse_coords` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `driver_count` int DEFAULT '1',
  `country_code` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'et',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `routes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `routes_chk_1` CHECK (json_valid(`warehouse_coords`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `routes` --
--




-- 
-- Table structure for table `stock_movements` --
--

DROP TABLE IF EXISTS `stock_movements`;

CREATE TABLE `stock_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `driver_id` int DEFAULT NULL,
  `movement_type` enum('IN','OUT') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reference` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `variant_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `variant_id` (`variant_id`),
  CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=264 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `stock_movements` --
--

INSERT INTO `stock_movements` VALUES("68","54",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 07:55:40","76");
INSERT INTO `stock_movements` VALUES("69","55",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:03:05","77");
INSERT INTO `stock_movements` VALUES("70","56",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:07:09","78");
INSERT INTO `stock_movements` VALUES("71","57",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:10:40","79");
INSERT INTO `stock_movements` VALUES("72","57",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:10:40","80");
INSERT INTO `stock_movements` VALUES("73","58",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:13:43","81");
INSERT INTO `stock_movements` VALUES("74","59",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2025-12-17 08:18:17","82");
INSERT INTO `stock_movements` VALUES("75","60",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2025-12-17 08:20:19","83");
INSERT INTO `stock_movements` VALUES("76","61",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2025-12-17 08:27:15","84");
INSERT INTO `stock_movements` VALUES("77","61",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:27:15","85");
INSERT INTO `stock_movements` VALUES("78","61",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:27:15","86");
INSERT INTO `stock_movements` VALUES("79","62",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:35:39","87");
INSERT INTO `stock_movements` VALUES("80","63",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:40:23","88");
INSERT INTO `stock_movements` VALUES("81","64",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:45:22","89");
INSERT INTO `stock_movements` VALUES("82","64",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:45:22","90");
INSERT INTO `stock_movements` VALUES("83","64",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:45:22","91");
INSERT INTO `stock_movements` VALUES("84","65",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:58:51","93");
INSERT INTO `stock_movements` VALUES("85","65",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:58:51","94");
INSERT INTO `stock_movements` VALUES("86","65",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 08:58:51","95");
INSERT INTO `stock_movements` VALUES("87","66",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 09:05:10","97");
INSERT INTO `stock_movements` VALUES("88","67",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2025-12-17 09:11:35","98");
INSERT INTO `stock_movements` VALUES("89","68",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 09:20:38","99");
INSERT INTO `stock_movements` VALUES("90","69",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 09:24:12","100");
INSERT INTO `stock_movements` VALUES("91","69",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 09:24:12","101");
INSERT INTO `stock_movements` VALUES("92","70",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2025-12-17 09:29:08","102");
INSERT INTO `stock_movements` VALUES("93","70",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 09:29:08","103");
INSERT INTO `stock_movements` VALUES("94","70",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2025-12-17 09:29:08","104");
INSERT INTO `stock_movements` VALUES("95","71",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 09:31:18","105");
INSERT INTO `stock_movements` VALUES("96","71",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 09:31:18","106");
INSERT INTO `stock_movements` VALUES("97","72",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 09:41:11","109");
INSERT INTO `stock_movements` VALUES("98","73",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2025-12-17 09:43:48","110");
INSERT INTO `stock_movements` VALUES("99","74",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 09:46:39","111");
INSERT INTO `stock_movements` VALUES("100","75",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 09:51:48","113");
INSERT INTO `stock_movements` VALUES("101","76",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2025-12-17 10:27:22","118");
INSERT INTO `stock_movements` VALUES("102","77",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 10:29:25","119");
INSERT INTO `stock_movements` VALUES("109","80",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 10:43:42","126");
INSERT INTO `stock_movements` VALUES("110","80",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 10:43:42","127");
INSERT INTO `stock_movements` VALUES("111","81",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-17 10:45:02","128");
INSERT INTO `stock_movements` VALUES("112","67",NULL,NULL,NULL,"OUT","2","Sale","","2025-12-18 08:52:50","98");
INSERT INTO `stock_movements` VALUES("113","67",NULL,NULL,NULL,"IN","2","Return","","2025-12-18 08:56:56","98");
INSERT INTO `stock_movements` VALUES("115","83",NULL,NULL,NULL,"IN","4","Initial stock",NULL,"2025-12-18 14:38:05","133");
INSERT INTO `stock_movements` VALUES("116","83",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2025-12-18 14:38:05","134");
INSERT INTO `stock_movements` VALUES("117","83",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2025-12-18 14:38:05","135");
INSERT INTO `stock_movements` VALUES("118","83",NULL,NULL,NULL,"IN","3","Initial stock",NULL,"2025-12-18 14:38:05","136");
INSERT INTO `stock_movements` VALUES("119","83",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-18 14:38:05","137");
INSERT INTO `stock_movements` VALUES("120","84",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-18 15:40:05","141");
INSERT INTO `stock_movements` VALUES("121","84",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-18 15:40:05","142");
INSERT INTO `stock_movements` VALUES("122","84",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-18 15:40:05","143");
INSERT INTO `stock_movements` VALUES("123","85",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-18 15:44:29","144");
INSERT INTO `stock_movements` VALUES("124","86",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-18 15:47:37","145");
INSERT INTO `stock_movements` VALUES("125","86",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-18 15:47:37","146");
INSERT INTO `stock_movements` VALUES("126","87",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-18 15:49:09","147");
INSERT INTO `stock_movements` VALUES("127","88",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-19 06:20:09","148");
INSERT INTO `stock_movements` VALUES("128","88",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-19 06:20:09","149");
INSERT INTO `stock_movements` VALUES("129","88",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-19 06:20:09","150");
INSERT INTO `stock_movements` VALUES("130","88",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-19 06:20:09","151");
INSERT INTO `stock_movements` VALUES("131","88",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2025-12-19 06:20:09","152");
INSERT INTO `stock_movements` VALUES("132","89",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-19 06:41:20","156");
INSERT INTO `stock_movements` VALUES("133","61",NULL,NULL,NULL,"OUT","1","Sale","104007","2025-12-22 08:39:09","92");
INSERT INTO `stock_movements` VALUES("134","73",NULL,NULL,NULL,"OUT","1","Sale","103462","2025-12-22 09:54:34","140");
INSERT INTO `stock_movements` VALUES("135","83",NULL,NULL,NULL,"OUT","1","Sale","104682","2025-12-22 11:51:39","133");
INSERT INTO `stock_movements` VALUES("136","88",NULL,NULL,NULL,"OUT","1","Other","103797","2025-12-22 11:56:23","150");
INSERT INTO `stock_movements` VALUES("137","61",NULL,NULL,NULL,"IN","1","Return","104007","2025-12-22 14:07:58","92");
INSERT INTO `stock_movements` VALUES("138","55",NULL,NULL,NULL,"OUT","1","Sale","102844","2025-12-22 14:17:28","116");
INSERT INTO `stock_movements` VALUES("139","56",NULL,NULL,NULL,"OUT","1","Sale","102844 ","2025-12-22 14:20:56","78");
INSERT INTO `stock_movements` VALUES("140","80",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 12:37:49","138");
INSERT INTO `stock_movements` VALUES("141","61",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 12:40:47","85");
INSERT INTO `stock_movements` VALUES("142","61",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 12:42:27","85");
INSERT INTO `stock_movements` VALUES("143","68",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 12:45:59","114");
INSERT INTO `stock_movements` VALUES("144","88",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 12:49:23","148");
INSERT INTO `stock_movements` VALUES("145","76",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 12:49:54","118");
INSERT INTO `stock_movements` VALUES("146","88",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 12:52:17","149");
INSERT INTO `stock_movements` VALUES("147","88",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 12:53:43","148");
INSERT INTO `stock_movements` VALUES("148","61",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 12:55:03","92");
INSERT INTO `stock_movements` VALUES("149","55",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 12:59:11","116");
INSERT INTO `stock_movements` VALUES("150","68",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 13:00:27","115");
INSERT INTO `stock_movements` VALUES("151","88",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 13:01:13","149");
INSERT INTO `stock_movements` VALUES("152","88",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 13:02:29","151");
INSERT INTO `stock_movements` VALUES("153","88",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 13:12:22","152");
INSERT INTO `stock_movements` VALUES("154","88",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 13:13:37","152");
INSERT INTO `stock_movements` VALUES("155","55",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 13:19:25","116");
INSERT INTO `stock_movements` VALUES("156","88",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 13:20:50","152");
INSERT INTO `stock_movements` VALUES("157","88",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 13:33:07","149");
INSERT INTO `stock_movements` VALUES("158","88",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 13:34:34","149");
INSERT INTO `stock_movements` VALUES("159","63",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 13:37:22","157");
INSERT INTO `stock_movements` VALUES("160","80",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 13:41:27","138");
INSERT INTO `stock_movements` VALUES("161","61",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-23 13:42:52","92");
INSERT INTO `stock_movements` VALUES("162","90",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-23 14:58:25","165");
INSERT INTO `stock_movements` VALUES("163","90",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2025-12-23 14:58:25","166");
INSERT INTO `stock_movements` VALUES("164","90",NULL,NULL,NULL,"IN","4","Initial stock",NULL,"2025-12-23 14:58:25","167");
INSERT INTO `stock_movements` VALUES("165","90",NULL,NULL,NULL,"IN","3","Initial stock",NULL,"2025-12-23 14:58:25","168");
INSERT INTO `stock_movements` VALUES("166","91",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-24 06:47:08","170");
INSERT INTO `stock_movements` VALUES("167","92",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-24 07:41:21","171");
INSERT INTO `stock_movements` VALUES("168","92",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-24 07:41:21","172");
INSERT INTO `stock_movements` VALUES("169","93",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-24 07:55:20","173");
INSERT INTO `stock_movements` VALUES("170","94",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-24 09:02:25","174");
INSERT INTO `stock_movements` VALUES("171","94",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-24 09:02:25","175");
INSERT INTO `stock_movements` VALUES("172","95",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2025-12-24 09:07:19","176");
INSERT INTO `stock_movements` VALUES("173","94",NULL,NULL,NULL,"OUT","1","Sale","104820","2025-12-25 11:51:31","174");
INSERT INTO `stock_movements` VALUES("174","92",NULL,NULL,NULL,"OUT","1","Sale","104822","2025-12-25 13:59:50","171");
INSERT INTO `stock_movements` VALUES("175","70",NULL,NULL,NULL,"OUT","1","Other","Content creation","2025-12-26 07:11:17","102");
INSERT INTO `stock_movements` VALUES("176","70",NULL,NULL,NULL,"OUT","1","Other","Content creation","2025-12-26 07:11:47","104");
INSERT INTO `stock_movements` VALUES("177","70",NULL,NULL,NULL,"OUT","1","Other","Content creation","2025-12-26 07:12:15","103");
INSERT INTO `stock_movements` VALUES("178","64",NULL,NULL,NULL,"OUT","1","Sale","104908","2025-12-28 09:22:21","89");
INSERT INTO `stock_movements` VALUES("179","88",NULL,NULL,NULL,"OUT","2","Sale","104963","2025-12-29 13:08:24","151");
INSERT INTO `stock_movements` VALUES("180","70",NULL,NULL,NULL,"IN","1","Adjustment","Content creation","2025-12-30 08:19:25","104");
INSERT INTO `stock_movements` VALUES("181","70",NULL,NULL,NULL,"IN","1","Adjustment","Content creation","2025-12-30 08:19:42","102");
INSERT INTO `stock_movements` VALUES("182","70",NULL,NULL,NULL,"IN","1","Adjustment","Content creation","2025-12-30 11:30:47","103");
INSERT INTO `stock_movements` VALUES("183","86",NULL,NULL,NULL,"OUT","1","Sale","105043","2025-12-31 08:12:11","146");
INSERT INTO `stock_movements` VALUES("184","92",NULL,NULL,NULL,"OUT","1","Other","105020 ","2025-12-31 08:54:54","172");
INSERT INTO `stock_movements` VALUES("185","63",NULL,NULL,NULL,"IN","1","Purchase Order","","2025-12-31 12:03:55","88");
INSERT INTO `stock_movements` VALUES("186","65",NULL,NULL,NULL,"OUT","1","Other","Content creation","2025-12-31 12:07:43","117");
INSERT INTO `stock_movements` VALUES("187","88",NULL,NULL,NULL,"OUT","1","Other","Content Creation ","2025-12-31 12:17:12","148");
INSERT INTO `stock_movements` VALUES("188","65",NULL,NULL,NULL,"OUT","1","Other","Content Creation ","2025-12-31 12:29:13","93");
INSERT INTO `stock_movements` VALUES("189","65",NULL,NULL,NULL,"IN","1","Other","Content creation","2026-01-01 13:22:48","117");
INSERT INTO `stock_movements` VALUES("190","65",NULL,NULL,NULL,"IN","1","Other","Content creation","2026-01-01 13:23:03","93");
INSERT INTO `stock_movements` VALUES("191","88",NULL,NULL,NULL,"IN","1","Other","Content creation","2026-01-01 13:24:54","148");
INSERT INTO `stock_movements` VALUES("192","92",NULL,NULL,NULL,"IN","1","Return","105020","2026-01-01 13:25:50","172");
INSERT INTO `stock_movements` VALUES("193","83",NULL,NULL,NULL,"IN","1","Return","103924","2026-01-01 13:28:23","136");
INSERT INTO `stock_movements` VALUES("194","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","177");
INSERT INTO `stock_movements` VALUES("195","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","178");
INSERT INTO `stock_movements` VALUES("196","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","179");
INSERT INTO `stock_movements` VALUES("197","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","180");
INSERT INTO `stock_movements` VALUES("198","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","181");
INSERT INTO `stock_movements` VALUES("199","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","182");
INSERT INTO `stock_movements` VALUES("200","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","183");
INSERT INTO `stock_movements` VALUES("201","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","184");
INSERT INTO `stock_movements` VALUES("202","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","185");
INSERT INTO `stock_movements` VALUES("203","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","186");
INSERT INTO `stock_movements` VALUES("204","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","187");
INSERT INTO `stock_movements` VALUES("205","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","188");
INSERT INTO `stock_movements` VALUES("206","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","189");
INSERT INTO `stock_movements` VALUES("207","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","190");
INSERT INTO `stock_movements` VALUES("208","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","191");
INSERT INTO `stock_movements` VALUES("209","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","192");
INSERT INTO `stock_movements` VALUES("210","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","193");
INSERT INTO `stock_movements` VALUES("211","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","194");
INSERT INTO `stock_movements` VALUES("212","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","195");
INSERT INTO `stock_movements` VALUES("213","96",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:35:36","196");
INSERT INTO `stock_movements` VALUES("214","96",NULL,"7",NULL,"OUT","1","Sale","105127","2026-01-02 08:43:30","188");
INSERT INTO `stock_movements` VALUES("215","97",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:52:16","197");
INSERT INTO `stock_movements` VALUES("216","97",NULL,NULL,NULL,"IN","3","Initial stock",NULL,"2026-01-02 08:52:16","198");
INSERT INTO `stock_movements` VALUES("217","97",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2026-01-02 08:52:16","199");
INSERT INTO `stock_movements` VALUES("218","97",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2026-01-02 08:52:16","200");
INSERT INTO `stock_movements` VALUES("219","97",NULL,NULL,NULL,"IN","3","Initial stock",NULL,"2026-01-02 08:52:16","201");
INSERT INTO `stock_movements` VALUES("220","97",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2026-01-02 08:52:16","202");
INSERT INTO `stock_movements` VALUES("221","97",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 08:52:16","203");
INSERT INTO `stock_movements` VALUES("222","97",NULL,NULL,NULL,"IN","3","Initial stock",NULL,"2026-01-02 08:52:16","204");
INSERT INTO `stock_movements` VALUES("223","97",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2026-01-02 08:52:16","205");
INSERT INTO `stock_movements` VALUES("224","97",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2026-01-02 08:52:16","206");
INSERT INTO `stock_movements` VALUES("225","97",NULL,NULL,NULL,"IN","4","Initial stock",NULL,"2026-01-02 08:52:16","207");
INSERT INTO `stock_movements` VALUES("226","97",NULL,NULL,NULL,"IN","4","Initial stock",NULL,"2026-01-02 08:52:16","208");
INSERT INTO `stock_movements` VALUES("227","97",NULL,"7",NULL,"OUT","1","Damaged","","2026-01-02 08:53:36","205");
INSERT INTO `stock_movements` VALUES("228","98",NULL,NULL,NULL,"IN","4","Initial stock",NULL,"2026-01-02 09:02:52","209");
INSERT INTO `stock_movements` VALUES("229","98",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 09:02:52","210");
INSERT INTO `stock_movements` VALUES("230","98",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2026-01-02 09:02:52","211");
INSERT INTO `stock_movements` VALUES("231","98",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 09:02:52","212");
INSERT INTO `stock_movements` VALUES("232","98",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2026-01-02 09:02:52","213");
INSERT INTO `stock_movements` VALUES("233","98",NULL,NULL,NULL,"IN","1","Initial stock",NULL,"2026-01-02 09:02:52","214");
INSERT INTO `stock_movements` VALUES("234","98",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 09:02:52","215");
INSERT INTO `stock_movements` VALUES("235","98",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 09:02:52","216");
INSERT INTO `stock_movements` VALUES("236","98",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 09:02:52","217");
INSERT INTO `stock_movements` VALUES("237","98",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 09:02:52","218");
INSERT INTO `stock_movements` VALUES("238","98",NULL,NULL,NULL,"IN","5","Initial stock",NULL,"2026-01-02 09:02:52","219");
INSERT INTO `stock_movements` VALUES("239","98",NULL,NULL,NULL,"IN","2","Initial stock",NULL,"2026-01-02 09:02:52","220");
INSERT INTO `stock_movements` VALUES("240","99",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 09:11:34","221");
INSERT INTO `stock_movements` VALUES("241","99",NULL,NULL,NULL,"IN","7","Initial stock",NULL,"2026-01-02 09:11:34","222");
INSERT INTO `stock_movements` VALUES("242","99",NULL,NULL,NULL,"IN","5","Initial stock",NULL,"2026-01-02 09:11:34","223");
INSERT INTO `stock_movements` VALUES("243","99",NULL,NULL,NULL,"IN","5","Initial stock",NULL,"2026-01-02 09:11:34","224");
INSERT INTO `stock_movements` VALUES("244","100",NULL,NULL,NULL,"IN","4","Initial stock",NULL,"2026-01-02 09:22:25","225");
INSERT INTO `stock_movements` VALUES("245","100",NULL,NULL,NULL,"IN","4","Initial stock",NULL,"2026-01-02 09:22:25","226");
INSERT INTO `stock_movements` VALUES("246","100",NULL,NULL,NULL,"IN","9","Initial stock",NULL,"2026-01-02 09:22:25","227");
INSERT INTO `stock_movements` VALUES("247","100",NULL,NULL,NULL,"IN","9","Initial stock",NULL,"2026-01-02 09:22:25","228");
INSERT INTO `stock_movements` VALUES("248","101",NULL,NULL,NULL,"IN","4","Initial stock",NULL,"2026-01-02 09:28:07","229");
INSERT INTO `stock_movements` VALUES("249","101",NULL,NULL,NULL,"IN","4","Initial stock",NULL,"2026-01-02 09:28:07","230");
INSERT INTO `stock_movements` VALUES("250","101",NULL,NULL,NULL,"IN","8","Initial stock",NULL,"2026-01-02 09:28:07","231");
INSERT INTO `stock_movements` VALUES("251","101",NULL,NULL,NULL,"IN","8","Initial stock",NULL,"2026-01-02 09:28:07","232");
INSERT INTO `stock_movements` VALUES("252","102",NULL,NULL,NULL,"IN","0","Initial stock",NULL,"2026-01-02 09:33:13","233");
INSERT INTO `stock_movements` VALUES("253","102",NULL,NULL,NULL,"IN","5","Initial stock",NULL,"2026-01-02 09:33:13","234");
INSERT INTO `stock_movements` VALUES("254","102",NULL,NULL,NULL,"IN","10","Initial stock",NULL,"2026-01-02 09:33:13","235");
INSERT INTO `stock_movements` VALUES("255","102",NULL,NULL,NULL,"IN","10","Initial stock",NULL,"2026-01-02 09:33:13","236");
INSERT INTO `stock_movements` VALUES("256","103",NULL,NULL,NULL,"IN","3","Initial stock",NULL,"2026-01-02 09:37:42","237");
INSERT INTO `stock_movements` VALUES("257","103",NULL,NULL,NULL,"IN","7","Initial stock",NULL,"2026-01-02 09:37:42","238");
INSERT INTO `stock_movements` VALUES("258","103",NULL,NULL,NULL,"IN","13","Initial stock",NULL,"2026-01-02 09:37:42","239");
INSERT INTO `stock_movements` VALUES("259","103",NULL,NULL,NULL,"IN","4","Initial stock",NULL,"2026-01-02 09:37:42","240");
INSERT INTO `stock_movements` VALUES("260","99",NULL,"7",NULL,"IN","1","Adjustment","","2026-01-02 09:38:16","223");
INSERT INTO `stock_movements` VALUES("261","102",NULL,"7",NULL,"IN","5","Adjustment","","2026-01-02 09:39:21","233");
INSERT INTO `stock_movements` VALUES("262","96",NULL,"7",NULL,"OUT","1","Sale","105137","2026-01-02 12:18:25","181");
INSERT INTO `stock_movements` VALUES("263","96",NULL,"7",NULL,"OUT","1","Sale","105137","2026-01-02 12:18:47","196");



-- 
-- Table structure for table `suppliers` --
--

DROP TABLE IF EXISTS `suppliers`;

CREATE TABLE `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `website` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_terms` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `default_lead_time` int DEFAULT '7',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `suppliers` --
--

INSERT INTO `suppliers` VALUES("1","TechSupplier Inc","John Smith","john@techsupplier.com","+1-555-0101",NULL,NULL,"Net 30",NULL,"1","2025-11-28 19:04:57","2025-11-28 19:04:57","7");
INSERT INTO `suppliers` VALUES("2","DisplayCo","Sarah Johnson","sarah@displayco.com","+1-555-0102",NULL,NULL,"Net 15",NULL,"1","2025-11-28 19:04:57","2025-11-28 19:04:57","7");
INSERT INTO `suppliers` VALUES("3","KeyTech Ltd","Mike Brown","mike@keytech.com","+1-555-0103",NULL,NULL,"Immediate",NULL,"1","2025-11-28 19:04:57","2025-11-28 19:04:57","7");



-- 
-- Table structure for table `tax_fee_configs` --
--

DROP TABLE IF EXISTS `tax_fee_configs`;

CREATE TABLE `tax_fee_configs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `rate_type` enum('percentage','fixed') COLLATE utf8mb4_general_ci DEFAULT 'percentage',
  `rate_value` decimal(10,4) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `tax_fee_configs` --
--

INSERT INTO `tax_fee_configs` VALUES("1","Value Tax","15% value tax on imported goods","percentage","0.1500","1","2025-12-06 14:08:22","2025-12-06 14:08:22");
INSERT INTO `tax_fee_configs` VALUES("2","Shipment Fee","10% shipment fee","percentage","0.1000","1","2025-12-06 14:08:22","2025-12-06 14:08:22");
INSERT INTO `tax_fee_configs` VALUES("3","Processing Fee","5% processing fee","percentage","0.0500","1","2025-12-06 14:08:22","2025-12-06 14:08:22");
INSERT INTO `tax_fee_configs` VALUES("4","Delivery Fee","Fixed delivery fee","fixed","200.0000","1","2025-12-06 14:08:22","2025-12-06 14:08:22");



-- 
-- Table structure for table `tracking_links` --
--

DROP TABLE IF EXISTS `tracking_links`;

CREATE TABLE `tracking_links` (
  `id` int NOT NULL AUTO_INCREMENT,
  `package_id` int NOT NULL,
  `tracking_token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `view_count` int DEFAULT '0',
  `last_viewed_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracking_token` (`tracking_token`),
  KEY `package_id` (`package_id`),
  KEY `idx_token` (`tracking_token`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `tracking_links_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `delivery_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `tracking_links` --
--




-- 
-- Table structure for table `traffic_cache` --
--

DROP TABLE IF EXISTS `traffic_cache`;

CREATE TABLE `traffic_cache` (
  `id` int NOT NULL AUTO_INCREMENT,
  `origin_lat` decimal(10,8) NOT NULL,
  `origin_lon` decimal(11,8) NOT NULL,
  `destination_lat` decimal(10,8) NOT NULL,
  `destination_lon` decimal(11,8) NOT NULL,
  `distance` decimal(10,2) DEFAULT NULL COMMENT 'in km',
  `duration` int DEFAULT NULL COMMENT 'in minutes',
  `traffic_level` enum('low','moderate','heavy','severe') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cached_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_route` (`origin_lat`,`origin_lon`,`destination_lat`,`destination_lon`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `traffic_cache` --
--




-- 
-- Table structure for table `users` --
--

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('admin','manager','staff') COLLATE utf8mb4_general_ci DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 
-- Dumping data for table `users` --
--

INSERT INTO `users` VALUES("7","admin","admin@inventory.com","$2y$12$N.GgrbvOADF9Dsj3aS6lmOmzUBuND/381cp0HkLsQ1ZN.nCa42E.S","Dagim","Kenea","admin","1","2026-01-02 11:13:28","2025-12-03 20:53:14","2026-01-02 11:13:28");
INSERT INTO `users` VALUES("8","king","king@inventory.com","$2y$12$Zf4UohU0Zbf86ocUh0D0C.jOzTiZvyaNhm9/cx6nTVtNLqDpPVs0e","King","Sis","admin","1","2026-01-02 05:56:53","2025-12-03 20:53:14","2026-01-02 05:56:53");
INSERT INTO `users` VALUES("18","mimi","hiwotsisay@gmail.com","$2y$12$bh18H1yLYhgiMvj3zvQMD.8cPdshejQiEwz59oj/et0.9NEM.6zJG","Hiwot","Sisay","staff","1","2025-12-31 14:05:49","2025-12-08 12:28:23","2025-12-31 14:05:49");
INSERT INTO `users` VALUES("19","abrahamsisay","abrahamsisaysis@gmail.com","$2y$12$7liBoRS.0E0NGhJ2oVDgmO/7SSC8XcOv.xmrc5IkOi2tRIMVh8BDS","Abraham","Sisay","admin","1","2025-12-31 05:47:53","2025-12-10 13:51:20","2025-12-31 05:47:53");
INSERT INTO `users` VALUES("21","Biniab","biniyam.abraha23@gmail.com","$2y$12$KWfSxvbQSFeOvRJGiGyxueRpijKCAPl60gdZeiJtqC5DDBmVsSNhm","Biniyam ","Abraha","staff","1","2025-12-24 16:46:38","2025-12-22 18:40:47","2026-01-02 09:41:39");
INSERT INTO `users` VALUES("22","afomia","afomiahabtamu69@gmail.com","$2y$12$0EmFyJBIkxetuK4AUtHttux78vIBLVPbj3a.Ik8m/Hxrs7UObGNlG","AFOMIA","HABTAMU ","admin","1","2025-12-30 13:02:32","2025-12-23 06:25:46","2026-01-02 09:41:56");
INSERT INTO `users` VALUES("23","aman","aman@gmail.com","$2y$12$7SCRO8iPz5U48clTYDJdTu784W/B7fVOGCNaZjRZgHYePp9iqh9/C","Aman","Aman","staff","1","2026-01-01 06:30:40","2026-01-01 06:07:39","2026-01-01 06:30:40");



-- 
-- Table structure for table `vehicle_assignments` --
--

DROP TABLE IF EXISTS `vehicle_assignments`;

CREATE TABLE `vehicle_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `driver_id` int NOT NULL,
  `vehicle_id` int NOT NULL,
  `route_id` int DEFAULT NULL,
  `assignment_date` date NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `driver_id` (`driver_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `route_id` (`route_id`),
  KEY `idx_assignment_date` (`assignment_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `vehicle_assignments_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicle_assignments_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicle_assignments_ibfk_3` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `vehicle_assignments` --
--




-- 
-- Table structure for table `vehicles` --
--

DROP TABLE IF EXISTS `vehicles`;

CREATE TABLE `vehicles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vehicle_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_type` enum('van','truck','motorcycle','car','refrigerated') COLLATE utf8mb4_unicode_ci DEFAULT 'van',
  `make` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year` year DEFAULT NULL,
  `capacity_weight` decimal(10,2) DEFAULT NULL COMMENT 'in kg',
  `capacity_volume` decimal(10,2) DEFAULT NULL COMMENT 'in cubic meters',
  `fuel_type` enum('petrol','diesel','electric','hybrid') COLLATE utf8mb4_unicode_ci DEFAULT 'petrol',
  `fuel_efficiency` decimal(6,2) DEFAULT NULL COMMENT 'km per liter or km per kWh',
  `status` enum('active','maintenance','retired') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_number` (`vehicle_number`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`vehicle_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 
-- Dumping data for table `vehicles` --
--




SET FOREIGN_KEY_CHECKS=1;