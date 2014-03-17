SET NAMES utf8;

-- ----------------------------
--  Table structure for `BlendEvent`
-- ----------------------------
CREATE TABLE IF NOT EXISTS `BlendEvent` (
  `contentobject_id` int(11) NOT NULL,
  `contentclassattribute_id` int(11) NOT NULL,
  `version` int(11) NOT NULL default '1',
  `language_code` varchar(20) NOT NULL,
  `start_time` int(11) default NULL,
  `duration` int(11) default NULL,
  `recurrence_type` tinyint(4) NOT NULL,
  `month` tinyint(4) default NULL,
  `day` tinyint(4) default NULL,
  `year` int(11) default NULL,
  `week` tinyint(4) default NULL,
  `sunday` tinyint(2) default NULL,
  `monday` tinyint(2) default NULL,
  `tuesday` tinyint(2) default NULL,
  `wednesday` tinyint(2) default NULL,
  `thursday` tinyint(2) default NULL,
  `friday` tinyint(2) default NULL,
  `saturday` tinyint(2) default NULL,
  `range_start` bigint(20) default NULL,
  `range_end` bigint(20) default NULL,
  `interval` tinyint(4) default NULL,
  PRIMARY KEY  (`contentobject_id`,`contentclassattribute_id`,`version`,`language_code`),
  KEY `idx_range` (`range_start`,`range_end`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
