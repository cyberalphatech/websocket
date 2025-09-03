-- --------------------------------------------------------
-- 主机:                           127.0.0.1
-- 服务器版本:                        5.5.15 - MySQL Community Server (GPL)
-- 服务器操作系统:                      Win32
-- HeidiSQL 版本:                  12.2.0.6576
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- 导出 realtime 的数据库结构
CREATE DATABASE IF NOT EXISTS `realtime` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `realtime`;

-- 导出  表 realtime.access_day 结构
CREATE TABLE IF NOT EXISTS `access_day` (
  `id` int(11) NOT NULL,
  `serial` varchar(12) COLLATE utf8mb4_bin DEFAULT NULL,
  `name` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL,
  `start_time1` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `end_time1` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `start_time2` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `end_time2` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `start_time3` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `end_time3` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `start_time4` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `end_time4` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `start_time5` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `end_time5` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- 数据导出被取消选择。

-- 导出  表 realtime.access_week 结构
CREATE TABLE IF NOT EXISTS `access_week` (
  `id` int(11) NOT NULL,
  `serial` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL,
  `name` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL,
  `monday` int(20) NOT NULL,
  `tuesday` int(20) NOT NULL,
  `wednesday` int(20) NOT NULL,
  `thursday` int(20) NOT NULL,
  `friday` int(20) NOT NULL,
  `saturday` int(20) NOT NULL,
  `sunday` int(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- 数据导出被取消选择。

-- 导出  表 realtime.device 结构
CREATE TABLE IF NOT EXISTS `device` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serial_num` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- 数据导出被取消选择。

-- 导出  表 realtime.enrollinfo 结构
CREATE TABLE IF NOT EXISTS `enrollinfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enroll_id` bigint(20) NOT NULL,
  `backupnum` int(11) DEFAULT NULL,
  `imagepath` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `signatures` mediumtext COLLATE utf8mb4_bin,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=520 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- 数据导出被取消选择。

-- 导出  表 realtime.machine_command 结构
CREATE TABLE IF NOT EXISTS `machine_command` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serial` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `content` mediumtext COLLATE utf8mb4_bin,
  `status` int(11) NOT NULL DEFAULT '0',
  `send_status` int(11) NOT NULL DEFAULT '0',
  `err_count` int(11) NOT NULL DEFAULT '0',
  `run_time` datetime DEFAULT NULL,
  `gmt_crate` datetime NOT NULL,
  `gmt_modified` datetime NOT NULL,
  PRIMARY KEY (`id`,`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5239 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- 数据导出被取消选择。

-- 导出  表 realtime.person 结构
CREATE TABLE IF NOT EXISTS `person` (
  `id` bigint(12) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `roll_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=147259 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- 数据导出被取消选择。

-- 导出  表 realtime.records 结构
CREATE TABLE IF NOT EXISTS `records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enroll_id` bigint(20) NOT NULL,
  `records_time` datetime NOT NULL,
  `mode` int(11) NOT NULL,
  `intOut` int(11) NOT NULL,
  `event` int(11) NOT NULL,
  `device_serial_num` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `temperature` double DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- 数据导出被取消选择。

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
