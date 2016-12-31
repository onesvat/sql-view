-- Adminer 4.2.5 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `caches`;
CREATE TABLE `caches` (
  `cch_id` int(11) NOT NULL AUTO_INCREMENT,
  `cch_query` int(11) NOT NULL,
  `cch_result` text COLLATE utf8_unicode_ci NOT NULL,
  `cch_created_date` timestamp NOT NULL,
  PRIMARY KEY (`cch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `connections`;
CREATE TABLE `connections` (
  `cnn_id` int(11) NOT NULL AUTO_INCREMENT,
  `cnn_name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `cnn_type` enum('mysql','postgresql') COLLATE utf8_unicode_ci NOT NULL,
  `cnn_status` enum('active','passive') COLLATE utf8_unicode_ci NOT NULL,
  `cnn_connection` text COLLATE utf8_unicode_ci NOT NULL,
  `cnn_access_date` timestamp NULL DEFAULT NULL,
  `cnn_created_date` timestamp NOT NULL,
  PRIMARY KEY (`cnn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `queries`;
CREATE TABLE `queries` (
  `que_id` int(11) NOT NULL AUTO_INCREMENT,
  `que_database` int(11) NOT NULL,
  `que_string` text COLLATE utf8_unicode_ci NOT NULL,
  `que_cache` int(11) NOT NULL,
  `que_created_date` timestamp NOT NULL,
  PRIMARY KEY (`que_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `usr_id` int(11) NOT NULL AUTO_INCREMENT,
  `usr_status` enum('active') COLLATE utf8_unicode_ci NOT NULL,
  `usr_type` enum('user','admin') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user',
  `usr_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `usr_password` char(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `prm_id` int(11) NOT NULL AUTO_INCREMENT,
  `prm_usr_id` int(11) NOT NULL,
  `prm_connection_id` int(11) NOT NULL,
  `prm_table_name` varchar(255) NOT NULL,
  `prm_field_name` varchar(255) NOT NULL,
  PRIMARY KEY (`prm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



DROP TABLE IF EXISTS `active_connections`;
CREATE TABLE `active_connections` (
  `atc_id` int(11) NOT NULL AUTO_INCREMENT,
  `atc_active_cnn_id` int(11) NOT NULL,
  `atc_usr_id` int(11) NOT NULL,
  PRIMARY KEY (`atc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- 2016-12-26 15:53:40