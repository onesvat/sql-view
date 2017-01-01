-- Adminer 4.2.2 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `active_connections`;
CREATE TABLE `active_connections` (
  `atc_usr_id` int(11) NOT NULL,
  `atc_active_cnn_id` int(11) NOT NULL,
  UNIQUE KEY `atc_usr_id` (`atc_usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `connections`;
CREATE TABLE `connections` (
  `cnn_id` int(11) NOT NULL AUTO_INCREMENT,
  `cnn_status` enum('active','passive') COLLATE utf8_unicode_ci NOT NULL,
  `cnn_name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `cnn_type` enum('mysql','postgresql') COLLATE utf8_unicode_ci NOT NULL,
  `cnn_connection` text COLLATE utf8_unicode_ci NOT NULL,
  `cnn_access_date` timestamp NULL DEFAULT NULL,
  `cnn_created_date` timestamp NOT NULL,
  PRIMARY KEY (`cnn_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `databases`;
CREATE TABLE `databases` (
  `dtb_id` int(11) NOT NULL AUTO_INCREMENT,
  `dtb_user` int(11) NOT NULL,
  `dtb_name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `dtb_type` enum('mysql','postgresql') COLLATE utf8_unicode_ci NOT NULL,
  `dtb_connection` text COLLATE utf8_unicode_ci NOT NULL,
  `dtb_access_date` timestamp NOT NULL,
  `dtb_created_date` timestamp NOT NULL,
  PRIMARY KEY (`dtb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `prm_id` int(11) NOT NULL AUTO_INCREMENT,
  `prm_user` int(11) NOT NULL,
  `prm_connection` int(11) NOT NULL,
  `prm_permission_type` enum('full','partial','none') NOT NULL,
  `prm_permission` text,
  PRIMARY KEY (`prm_id`),
  UNIQUE KEY `prm_user_prm_connection` (`prm_user`,`prm_connection`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `queries`;
CREATE TABLE `queries` (
  `que_id` int(11) NOT NULL AUTO_INCREMENT,
  `que_favorite` enum('yes','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `que_connection` int(11) NOT NULL,
  `que_string` text COLLATE utf8_unicode_ci NOT NULL,
  `que_hash` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `que_cache` int(11) NOT NULL,
  `que_result` longtext COLLATE utf8_unicode_ci NOT NULL,
  `que_result_hash` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `que_updated_date` timestamp NOT NULL,
  `que_created_date` timestamp NOT NULL,
  PRIMARY KEY (`que_id`),
  UNIQUE KEY `que_hash_que_connection` (`que_hash`,`que_connection`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `usr_id` int(11) NOT NULL AUTO_INCREMENT,
  `usr_type` enum('user','admin') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user',
  `usr_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `usr_password` char(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- 2017-01-01 09:46:11