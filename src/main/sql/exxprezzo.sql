/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table config
# ------------------------------------------------------------

DROP TABLE IF EXISTS `config`;

CREATE TABLE `config` (
  `key` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `value` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;

INSERT INTO `config` (`key`, `value`)
VALUES
	('timeZone','Europe/Oslo'),
	('urlManager','QueryString');

/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table hostGroup
# ------------------------------------------------------------

DROP TABLE IF EXISTS `hostGroup`;

CREATE TABLE `hostGroup` (
  `hostGroupId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hostName` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `type` enum('primary','slave','redirect') CHARACTER SET ascii NOT NULL DEFAULT 'slave',
  PRIMARY KEY (`hostGroupId`,`hostName`),
  UNIQUE KEY `hostName` (`hostName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `hostGroup` WRITE;
/*!40000 ALTER TABLE `hostGroup` DISABLE KEYS */;

INSERT INTO `hostGroup` (`hostGroupId`, `hostName`, `type`)
VALUES
	(1,'%','primary');

/*!40000 ALTER TABLE `hostGroup` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table hostGroupAlias
# ------------------------------------------------------------

DROP TABLE IF EXISTS `hostGroupAlias`;

CREATE TABLE `hostGroupAlias` (
  `hostGroupId` int(11) unsigned NOT NULL,
  `hostName` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  PRIMARY KEY (`hostGroupId`,`hostName`),
  UNIQUE KEY `hostName` (`hostName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table layout
# ------------------------------------------------------------

DROP TABLE IF EXISTS `layout`;

CREATE TABLE `layout` (
  `layoutId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `theme` varchar(255) CHARACTER SET ascii NOT NULL,
  `name` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `defaultBox` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  PRIMARY KEY (`layoutId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `layout` WRITE;
/*!40000 ALTER TABLE `layout` DISABLE KEYS */;

INSERT INTO `layout` (`layoutId`, `theme`, `name`, `defaultBox`)
VALUES
	(1,'tinymce','default','content');

/*!40000 ALTER TABLE `layout` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table moduleInstance
# ------------------------------------------------------------

DROP TABLE IF EXISTS `moduleInstance`;

CREATE TABLE `moduleInstance` (
  `moduleInstanceId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `root` varchar(255) CHARACTER SET latin1 DEFAULT '',
  `hostGroup` int(11) unsigned NOT NULL,
  `param` text CHARACTER SET latin1,
  PRIMARY KEY (`moduleInstanceId`),
  UNIQUE KEY `mountpoint` (`root`,`hostGroup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `moduleInstance` WRITE;
/*!40000 ALTER TABLE `moduleInstance` DISABLE KEYS */;

INSERT INTO `moduleInstance` (`moduleInstanceId`, `module`, `root`, `hostGroup`, `param`)
VALUES
	(1,'CMS','',1,'exxprezzo://localhost/core#cms'),
	(2,'Menu',NULL,1,'exxprezzo://localhost/core#menu');

/*!40000 ALTER TABLE `moduleInstance` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table moduleParam
# ------------------------------------------------------------

DROP TABLE IF EXISTS `moduleParam`;

CREATE TABLE `moduleParam` (
  `moduleInstanceId` int(11) unsigned NOT NULL,
  `key` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `value` blob NOT NULL,
  PRIMARY KEY (`moduleInstanceId`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `moduleParam` WRITE;
/*!40000 ALTER TABLE `moduleParam` DISABLE KEYS */;

INSERT INTO `moduleParam` (`moduleInstanceId`, `key`, `value`)
VALUES
	(1,'','');

/*!40000 ALTER TABLE `moduleParam` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table page
# ------------------------------------------------------------

DROP TABLE IF EXISTS `page`;

CREATE TABLE `page` (
  `pageId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `moduleInstanceId` int(11) DEFAULT NULL,
  `function` varchar(255) CHARACTER SET ascii DEFAULT '',
  `layoutId` int(11) unsigned NOT NULL,
  `preferredFunctionTemplate` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  PRIMARY KEY (`pageId`),
  UNIQUE KEY `moduleInstanceId` (`moduleInstanceId`,`pageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `page` WRITE;
/*!40000 ALTER TABLE `page` DISABLE KEYS */;

INSERT INTO `page` (`pageId`, `moduleInstanceId`, `function`, `layoutId`, `preferredFunctionTemplate`)
VALUES
	(1,NULL,NULL,1,NULL);

/*!40000 ALTER TABLE `page` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table widget
# ------------------------------------------------------------

DROP TABLE IF EXISTS `widget`;

CREATE TABLE `widget` (
  `widgetId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pageId` int(11) unsigned DEFAULT NULL,
  `moduleInstanceId` int(11) unsigned NOT NULL,
  `function` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `preferredFunctionTemplate` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `box` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT '',
  `priority` int(11) unsigned NOT NULL,
  `param` text CHARACTER SET latin1,
  PRIMARY KEY (`widgetId`),
  KEY `pageId` (`pageId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `widget` WRITE;
/*!40000 ALTER TABLE `widget` DISABLE KEYS */;

INSERT INTO `widget` (`widgetId`, `pageId`, `moduleInstanceId`, `function`, `preferredFunctionTemplate`, `box`, `priority`, `param`)
VALUES
	(1,1,2,'menu',NULL,'menu',0,NULL);

/*!40000 ALTER TABLE `widget` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
