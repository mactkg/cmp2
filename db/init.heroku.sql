CREATE TABLE events (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `date` datetime NOT NULL,
  `place` varchar(255) NOT NULL,
  `text_md` text NOT NULL,
  `passkey` char(25) NOT NULL,
  `img_url` text,
  `deadline` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `first_talk_id` int(11),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4; 

CREATE TABLE talks (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `team_name` varchar(255) NOT NULL,
  `members_json` text NOT NULL,
  `text_md` text NOT NULL,
  `links_json` text,
  `passkey` char(25) NOT NULL,
  `img_url` text,
  `status` enum('ready','talking','pause','later','done') NOT NULL,
  `sequence_from_id` int(11) DEFAULT NULL,
  `sequence_to_id` int(11) DEFAULT NULL,
  `event_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  UNIQUE KEY `sequence_from_id` (`sequence_from_id`),
  UNIQUE KEY `sequence_to_id` (`sequence_to_id`),
  FOREIGN KEY (`sequence_from_id`) REFERENCES `talks` (`id`),
  FOREIGN KEY (`sequence_to_id`) REFERENCES `talks` (`id`),
  FOREIGN KEY (`event_id`) REFERENCES `events` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4;

ALTER TABLE fmfes.events ADD
  FOREIGN KEY (`first_talk_id`)
  REFERENCES `talks` (`id`);
