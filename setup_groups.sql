-- User defined Chat Group Tables

CREATE TABLE IF NOT EXISTS `chat_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `group_members` (
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_role` enum('admin', 'faculty', 'student') NOT NULL,
  `joined_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`group_id`, `user_id`, `user_role`),
  FOREIGN KEY (`group_id`) REFERENCES `chat_groups`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- Also ensuring messages table exists and supports groupId
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `university` varchar(100) DEFAULT NULL,
  `session` varchar(50) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `groupId` int(11) DEFAULT NULL,
  `createdAt` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
