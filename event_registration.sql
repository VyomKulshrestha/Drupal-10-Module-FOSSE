-- Event Registration Module - Database Schema
-- For Drupal 10.x
-- 
-- These tables are automatically created when the module is installed.
-- This file is provided for reference and manual installation if needed.

-- --------------------------------------------------------
-- Table structure for event_registration_events
-- Stores event configuration data
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `event_registration_events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key: unique event ID.',
  `registration_start_date` VARCHAR(10) NOT NULL COMMENT 'Event registration start date (YYYY-MM-DD).',
  `registration_end_date` VARCHAR(10) NOT NULL COMMENT 'Event registration end date (YYYY-MM-DD).',
  `event_date` VARCHAR(10) NOT NULL COMMENT 'Event date (YYYY-MM-DD).',
  `event_name` VARCHAR(255) NOT NULL COMMENT 'Name of the event.',
  `category` VARCHAR(100) NOT NULL COMMENT 'Category of the event.',
  `created` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Timestamp when the event was created.',
  PRIMARY KEY (`id`),
  INDEX `category` (`category`),
  INDEX `event_date` (`event_date`),
  INDEX `registration_period` (`registration_start_date`, `registration_end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores event configuration data.';

-- --------------------------------------------------------
-- Table structure for event_registration_registrations
-- Stores event registration data
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `event_registration_registrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key: unique registration ID.',
  `full_name` VARCHAR(255) NOT NULL COMMENT 'Full name of the registrant.',
  `email` VARCHAR(255) NOT NULL COMMENT 'Email address of the registrant.',
  `college_name` VARCHAR(255) NOT NULL COMMENT 'College name of the registrant.',
  `department` VARCHAR(255) NOT NULL COMMENT 'Department of the registrant.',
  `category` VARCHAR(100) NOT NULL COMMENT 'Category of the event.',
  `event_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to event_registration_events.id.',
  `created` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Timestamp when the registration was created.',
  PRIMARY KEY (`id`),
  INDEX `email_event` (`email`, `event_id`),
  INDEX `event_id` (`event_id`),
  INDEX `created` (`created`),
  CONSTRAINT `fk_event_registration_event` 
    FOREIGN KEY (`event_id`) 
    REFERENCES `event_registration_events` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores event registration data.';

-- --------------------------------------------------------
-- Sample Data (Optional - for testing)
-- --------------------------------------------------------

-- Sample event
-- INSERT INTO `event_registration_events` 
-- (`registration_start_date`, `registration_end_date`, `event_date`, `event_name`, `category`, `created`)
-- VALUES 
-- ('2026-02-01', '2026-02-28', '2026-03-15', 'Spring Hackathon 2026', 'hackathon', UNIX_TIMESTAMP());

-- --------------------------------------------------------
-- Notes:
-- --------------------------------------------------------
-- 
-- Categories available:
-- - online_workshop: Online Workshop
-- - hackathon: Hackathon
-- - conference: Conference
-- - one_day_workshop: One-day Workshop
--
-- The tables use InnoDB engine for foreign key support.
-- All text fields use utf8mb4 encoding for full Unicode support.
