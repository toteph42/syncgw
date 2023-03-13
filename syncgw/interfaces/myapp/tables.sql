-- 
--  Tables definitions and sample data
--
--	@package	sync*gw
--	@subpackage	myApp handler
--	@copyright	(c) 2008 - 2023 Florian Daeumling, Germany. All right reserved
-- 	@license 	https://github.com/toteph42/syncgw/blob/master/LICENSE
--
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------
-- User table
-- --------------------------------------------------------

DROP TABLE IF EXISTS `myapp_usertable`;
CREATE TABLE IF NOT EXISTS `myapp_usertable` (
  `id` 				INT 		NOT NULL AUTO_INCREMENT,
  `username` 		VARCHAR(64) NOT NULL COLLATE utf8_general_ci,
  `password` 		VARCHAR(64) NOT NULL COLLATE utf8_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1 ;

INSERT INTO `myapp_usertable` ( `id`, `username`, `password` ) VALUES
(11, 't1', 'mamma'),
(12, 'debug', 'mamma');

-- --------------------------------------------------------
-- Notes table
-- --------------------------------------------------------

DROP TABLE IF EXISTS `myapp_notestable`;
CREATE TABLE IF NOT EXISTS `myapp_notestable` (
  `id` 				INT 		 NOT NULL AUTO_INCREMENT,
  `user`	 		INT 		 NOT NULL COLLATE utf8_general_ci,  
  `cats`	 		VARCHAR(64)  NULL COLLATE utf8_general_ci,  
  `title`	 		VARCHAR(64)  NULL COLLATE utf8_general_ci,  
  `text`	 		VARCHAR(256) NOT NULL COLLATE utf8_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1 ;

INSERT INTO `myapp_notestable` ( `id`, `user`, `cats`, `title`, `text` ) VALUES
(11, 11, 'Cat1, Cat2', 'Notes title', 'This is a short text.');
