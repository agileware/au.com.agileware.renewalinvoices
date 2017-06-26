--
-- Table structure for table `civicrm_renewalinvoices_entity`
--

CREATE TABLE IF NOT EXISTS `civicrm_renewalinvoices_entity` (
  `id` int unsigned NOT NULL AUTO_INCREMENT  ,
  `reminder_id` int unsigned NOT NULL   COMMENT 'FK to Scheduled Reminder ID',
  `relationship_type_id` int unsigned  COMMENT 'FK to Relationship Type id',
  PRIMARY KEY (`id`),
  CONSTRAINT FK_civicrm_renewalinvoices_entity_reminder_id FOREIGN KEY (`reminder_id`)
    REFERENCES `civicrm_action_schedule`(`id`) ON DELETE CASCADE,
  CONSTRAINT FK_civicrm_renewalinvoices_entity_relationship_type_id FOREIGN KEY (`relationship_type_id`)
    REFERENCES `civicrm_relationship_type`(`id`) ON DELETE CASCADE
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
