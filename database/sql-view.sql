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
  `usr_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `usr_password` char(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`usr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- 2016-12-25 13:35:41