--
-- Table structure for table `event_configuration`
--

CREATE TABLE IF NOT EXISTS `event_configuration` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `event_registration_start` int(11) NOT NULL,
  `event_registration_end` int(11) NOT NULL,
  `event_date` varchar(20) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `event_registration`
--

CREATE TABLE IF NOT EXISTS `event_registration` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `college` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `event_date` varchar(20) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `created` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
