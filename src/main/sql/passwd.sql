# ************************************************************
# Sequel Pro SQL dump
# Version 3408
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: localhost (MySQL 5.5.25a)
# Database: exxprezzo
# Generation Time: 2012-08-22 06:25:05 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table passwd_group
# ------------------------------------------------------------

DROP TABLE IF EXISTS `passwd_group`;

CREATE TABLE `passwd_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `groupname` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `groupname` (`groupname`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table passwd_groupfield
# ------------------------------------------------------------

DROP TABLE IF EXISTS `passwd_groupfield`;

CREATE TABLE `passwd_groupfield` (
  `group` int(11) unsigned NOT NULL,
  `key` varchar(255) NOT NULL DEFAULT '',
  `value` blob NOT NULL,
  PRIMARY KEY (`group`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table passwd_member
# ------------------------------------------------------------

DROP TABLE IF EXISTS `passwd_member`;

CREATE TABLE `passwd_member` (
  `user` int(11) unsigned NOT NULL,
  `group` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user`,`group`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table passwd_user
# ------------------------------------------------------------

DROP TABLE IF EXISTS `passwd_user`;

CREATE TABLE `passwd_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` blob,
  `lastChange` int(11) DEFAULT NULL,
  `changeLock` int(11) DEFAULT NULL,
  `changeDeadline` int(11) DEFAULT NULL,
  `deadlineWarning` int(11) DEFAULT NULL,
  `passwordExpires` int(11) DEFAULT NULL,
  `accountExpires` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;



# Dump of table passwd_userfield
# ------------------------------------------------------------

DROP TABLE IF EXISTS `passwd_userfield`;

CREATE TABLE `passwd_userfield` (
  `user` int(11) unsigned NOT NULL,
  `key` varchar(255) NOT NULL DEFAULT '',
  `value` blob NOT NULL,
  PRIMARY KEY (`user`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
