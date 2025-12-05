-- TCT-YMS Database Schema
-- Version 1.0.0
-- MySQL 8.x / MariaDB Compatible

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- CORE TABLES
-- ============================================================

-- Roles table
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `permissions` JSON,
    `is_system` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100),
    `last_name` VARCHAR(100),
    `role_id` INT UNSIGNED NOT NULL,
    `phone` VARCHAR(20),
    `avatar` VARCHAR(255),
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login_at` DATETIME,
    `password_reset_token` VARCHAR(100),
    `password_reset_expires` DATETIME,
    `preferences` JSON,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`),
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_username` (`username`),
    INDEX `idx_users_role` (`role_id`),
    INDEX `idx_users_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- YARD CONFIGURATION TABLES
-- ============================================================

-- Yard zones (logical groupings of yard areas)
CREATE TABLE IF NOT EXISTS `yard_zones` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `description` TEXT,
    `color` VARCHAR(7) DEFAULT '#6B7280',
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yard rows (physical rows in the yard)
CREATE TABLE IF NOT EXISTS `yard_rows` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `zone_id` INT UNSIGNED,
    `name` VARCHAR(50) NOT NULL,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `slots_count` INT UNSIGNED DEFAULT 10,
    `grid_row` INT DEFAULT 0,
    `grid_col` INT DEFAULT 0,
    `orientation` ENUM('horizontal', 'vertical') DEFAULT 'horizontal',
    `description` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`zone_id`) REFERENCES `yard_zones`(`id`) ON DELETE SET NULL,
    INDEX `idx_yard_rows_zone` (`zone_id`),
    INDEX `idx_yard_rows_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yard slots (individual parking positions)
CREATE TABLE IF NOT EXISTS `yard_slots` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `row_id` INT UNSIGNED NOT NULL,
    `slot_number` INT NOT NULL,
    `label` VARCHAR(50),
    `slot_type` ENUM('standard', 'oversized', 'hazmat', 'reefer', 'reserved') DEFAULT 'standard',
    `grid_x` INT DEFAULT 0,
    `grid_y` INT DEFAULT 0,
    `is_available` TINYINT(1) DEFAULT 1,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`row_id`) REFERENCES `yard_rows`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_row_slot` (`row_id`, `slot_number`),
    INDEX `idx_yard_slots_available` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dock doors
CREATE TABLE IF NOT EXISTS `dock_doors` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `door_number` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100),
    `zone_id` INT UNSIGNED,
    `door_type` ENUM('inbound', 'outbound', 'both') DEFAULT 'both',
    `grid_x` INT DEFAULT 0,
    `grid_y` INT DEFAULT 0,
    `has_dock_leveler` TINYINT(1) DEFAULT 1,
    `has_dock_seal` TINYINT(1) DEFAULT 1,
    `max_trailer_height` DECIMAL(5,2),
    `status` ENUM('available', 'occupied', 'maintenance', 'disabled') DEFAULT 'available',
    `current_trailer_id` INT UNSIGNED,
    `notes` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`zone_id`) REFERENCES `yard_zones`(`id`) ON DELETE SET NULL,
    INDEX `idx_dock_doors_status` (`status`),
    INDEX `idx_dock_doors_zone` (`zone_id`),
    INDEX `idx_dock_doors_number` (`door_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CARRIER AND CUSTOMER TABLES
-- ============================================================

-- Carriers
CREATE TABLE IF NOT EXISTS `carriers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(50) UNIQUE,
    `mc_number` VARCHAR(20),
    `dot_number` VARCHAR(20),
    `scac_code` VARCHAR(10),
    `contact_name` VARCHAR(100),
    `contact_phone` VARCHAR(20),
    `contact_email` VARCHAR(255),
    `address` TEXT,
    `city` VARCHAR(100),
    `state` VARCHAR(50),
    `zip` VARCHAR(20),
    `country` VARCHAR(50) DEFAULT 'USA',
    `notes` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_carriers_name` (`name`),
    INDEX `idx_carriers_code` (`code`),
    INDEX `idx_carriers_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customers (optional - for 3PL operations)
CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(50) UNIQUE,
    `contact_name` VARCHAR(100),
    `contact_phone` VARCHAR(20),
    `contact_email` VARCHAR(255),
    `address` TEXT,
    `city` VARCHAR(100),
    `state` VARCHAR(50),
    `zip` VARCHAR(20),
    `country` VARCHAR(50) DEFAULT 'USA',
    `notes` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_customers_name` (`name`),
    INDEX `idx_customers_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TRAILER STATUS CONFIGURATION
-- ============================================================

-- Trailer statuses (configurable)
CREATE TABLE IF NOT EXISTS `trailer_statuses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `color` VARCHAR(7) DEFAULT '#6B7280',
    `bg_color` VARCHAR(7) DEFAULT '#F3F4F6',
    `icon` VARCHAR(50),
    `description` TEXT,
    `is_yard_status` TINYINT(1) DEFAULT 1,
    `is_door_status` TINYINT(1) DEFAULT 0,
    `is_final_status` TINYINT(1) DEFAULT 0,
    `dwell_warning_hours` INT UNSIGNED DEFAULT 4,
    `dwell_critical_hours` INT UNSIGNED DEFAULT 8,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_trailer_statuses_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Status transitions (state machine configuration)
CREATE TABLE IF NOT EXISTS `status_transitions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `from_status_id` INT UNSIGNED NOT NULL,
    `to_status_id` INT UNSIGNED NOT NULL,
    `requires_role` VARCHAR(50),
    `requires_door` TINYINT(1) DEFAULT 0,
    `requires_slot` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`from_status_id`) REFERENCES `trailer_statuses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`to_status_id`) REFERENCES `trailer_statuses`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_status_transition` (`from_status_id`, `to_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TRAILER TABLES
-- ============================================================

-- Trailers (main trailer tracking table)
CREATE TABLE IF NOT EXISTS `trailers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trailer_number` VARCHAR(50) NOT NULL,
    `carrier_id` INT UNSIGNED,
    `customer_id` INT UNSIGNED,
    `trailer_type` ENUM('dry_van', 'reefer', 'flatbed', 'tanker', 'intermodal', 'other') DEFAULT 'dry_van',
    `trailer_length` INT UNSIGNED DEFAULT 53,
    `status_id` INT UNSIGNED NOT NULL,
    `location_type` ENUM('yard_slot', 'dock_door', 'gate', 'external') DEFAULT 'yard_slot',
    `yard_slot_id` INT UNSIGNED,
    `dock_door_id` INT UNSIGNED,
    `seal_number` VARCHAR(50),
    `is_loaded` TINYINT(1) DEFAULT 0,
    `load_type` VARCHAR(100),
    `po_numbers` TEXT,
    `reference_numbers` TEXT,
    `temperature_setting` DECIMAL(5,2),
    `arrival_time` DATETIME,
    `departure_time` DATETIME,
    `current_status_since` DATETIME,
    `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    `notes` TEXT,
    `created_by` INT UNSIGNED,
    `updated_by` INT UNSIGNED,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME,
    FOREIGN KEY (`carrier_id`) REFERENCES `carriers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`status_id`) REFERENCES `trailer_statuses`(`id`),
    FOREIGN KEY (`yard_slot_id`) REFERENCES `yard_slots`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`dock_door_id`) REFERENCES `dock_doors`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_trailers_number` (`trailer_number`),
    INDEX `idx_trailers_status` (`status_id`),
    INDEX `idx_trailers_carrier` (`carrier_id`),
    INDEX `idx_trailers_customer` (`customer_id`),
    INDEX `idx_trailers_arrival` (`arrival_time`),
    INDEX `idx_trailers_location` (`location_type`),
    INDEX `idx_trailers_slot` (`yard_slot_id`),
    INDEX `idx_trailers_door` (`dock_door_id`),
    INDEX `idx_trailers_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update dock_doors foreign key for current_trailer_id
ALTER TABLE `dock_doors` ADD FOREIGN KEY (`current_trailer_id`) REFERENCES `trailers`(`id`) ON DELETE SET NULL;

-- Trailer history (audit trail)
CREATE TABLE IF NOT EXISTS `trailer_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trailer_id` INT UNSIGNED NOT NULL,
    `event_type` ENUM('status_change', 'location_change', 'door_assign', 'door_release', 'gate_in', 'gate_out', 'update', 'note') NOT NULL,
    `from_status_id` INT UNSIGNED,
    `to_status_id` INT UNSIGNED,
    `from_location` VARCHAR(100),
    `to_location` VARCHAR(100),
    `from_door_id` INT UNSIGNED,
    `to_door_id` INT UNSIGNED,
    `notes` TEXT,
    `user_id` INT UNSIGNED,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`trailer_id`) REFERENCES `trailers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`from_status_id`) REFERENCES `trailer_statuses`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`to_status_id`) REFERENCES `trailer_statuses`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`from_door_id`) REFERENCES `dock_doors`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`to_door_id`) REFERENCES `dock_doors`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_trailer_history_trailer` (`trailer_id`),
    INDEX `idx_trailer_history_type` (`event_type`),
    INDEX `idx_trailer_history_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- GATE MANAGEMENT TABLES
-- ============================================================

-- Gate events
CREATE TABLE IF NOT EXISTS `gate_events` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trailer_id` INT UNSIGNED NOT NULL,
    `event_type` ENUM('check_in', 'check_out') NOT NULL,
    `driver_name` VARCHAR(100),
    `driver_license` VARCHAR(50),
    `driver_phone` VARCHAR(20),
    `tractor_number` VARCHAR(50),
    `carrier_id` INT UNSIGNED,
    `seal_number` VARCHAR(50),
    `is_loaded` TINYINT(1) DEFAULT 0,
    `load_description` TEXT,
    `photo_path` VARCHAR(255),
    `destination` VARCHAR(255),
    `appointment_time` DATETIME,
    `gate_lane` VARCHAR(20),
    `notes` TEXT,
    `processed_by` INT UNSIGNED,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`trailer_id`) REFERENCES `trailers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`carrier_id`) REFERENCES `carriers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_gate_events_trailer` (`trailer_id`),
    INDEX `idx_gate_events_type` (`event_type`),
    INDEX `idx_gate_events_created` (`created_at`),
    INDEX `idx_gate_events_driver` (`driver_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MOVE TASKING TABLES
-- ============================================================

-- Move tasks
CREATE TABLE IF NOT EXISTS `move_tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trailer_id` INT UNSIGNED NOT NULL,
    `task_type` ENUM('move_to_slot', 'move_to_door', 'move_from_door', 'yard_jockey') DEFAULT 'move_to_slot',
    `priority` ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    `status` ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    `from_location_type` ENUM('yard_slot', 'dock_door', 'gate', 'external'),
    `from_slot_id` INT UNSIGNED,
    `from_door_id` INT UNSIGNED,
    `from_location_text` VARCHAR(100),
    `to_location_type` ENUM('yard_slot', 'dock_door', 'gate', 'external'),
    `to_slot_id` INT UNSIGNED,
    `to_door_id` INT UNSIGNED,
    `to_location_text` VARCHAR(100),
    `assigned_to` INT UNSIGNED,
    `assigned_at` DATETIME,
    `started_at` DATETIME,
    `completed_at` DATETIME,
    `cancelled_at` DATETIME,
    `cancelled_reason` TEXT,
    `estimated_minutes` INT UNSIGNED DEFAULT 10,
    `actual_minutes` INT UNSIGNED,
    `instructions` TEXT,
    `notes` TEXT,
    `created_by` INT UNSIGNED,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`trailer_id`) REFERENCES `trailers`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`from_slot_id`) REFERENCES `yard_slots`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`from_door_id`) REFERENCES `dock_doors`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`to_slot_id`) REFERENCES `yard_slots`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`to_door_id`) REFERENCES `dock_doors`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_move_tasks_trailer` (`trailer_id`),
    INDEX `idx_move_tasks_status` (`status`),
    INDEX `idx_move_tasks_priority` (`priority`),
    INDEX `idx_move_tasks_assigned` (`assigned_to`),
    INDEX `idx_move_tasks_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CONFIGURATION TABLES
-- ============================================================

-- System settings (key-value store for config)
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT,
    `type` ENUM('string', 'integer', 'boolean', 'json', 'array') DEFAULT 'string',
    `group` VARCHAR(50) DEFAULT 'general',
    `label` VARCHAR(255),
    `description` TEXT,
    `is_public` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_settings_group` (`group`),
    INDEX `idx_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feature flags
CREATE TABLE IF NOT EXISTS `feature_flags` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `display_name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `is_enabled` TINYINT(1) DEFAULT 1,
    `required_role` VARCHAR(50),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log (audit trail for system)
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(100),
    `entity_id` INT UNSIGNED,
    `old_values` JSON,
    `new_values` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_activity_user` (`user_id`),
    INDEX `idx_activity_action` (`action`),
    INDEX `idx_activity_entity` (`entity_type`, `entity_id`),
    INDEX `idx_activity_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- REPORTING/ANALYTICS SUPPORT TABLES
-- ============================================================

-- Daily yard snapshots (for historical reporting)
CREATE TABLE IF NOT EXISTS `yard_snapshots` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `snapshot_date` DATE NOT NULL,
    `snapshot_hour` INT UNSIGNED DEFAULT 0,
    `total_trailers` INT UNSIGNED DEFAULT 0,
    `trailers_at_doors` INT UNSIGNED DEFAULT 0,
    `trailers_in_yard` INT UNSIGNED DEFAULT 0,
    `available_doors` INT UNSIGNED DEFAULT 0,
    `available_slots` INT UNSIGNED DEFAULT 0,
    `arrivals_count` INT UNSIGNED DEFAULT 0,
    `departures_count` INT UNSIGNED DEFAULT 0,
    `moves_count` INT UNSIGNED DEFAULT 0,
    `avg_dwell_hours` DECIMAL(10,2) DEFAULT 0,
    `status_breakdown` JSON,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_snapshot_date_hour` (`snapshot_date`, `snapshot_hour`),
    INDEX `idx_snapshots_date` (`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- IMPORT/EXPORT TABLES
-- ============================================================

-- Import logs
CREATE TABLE IF NOT EXISTS `import_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `import_type` VARCHAR(50) NOT NULL,
    `filename` VARCHAR(255),
    `total_rows` INT UNSIGNED DEFAULT 0,
    `successful_rows` INT UNSIGNED DEFAULT 0,
    `failed_rows` INT UNSIGNED DEFAULT 0,
    `errors` JSON,
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    `imported_by` INT UNSIGNED,
    `started_at` DATETIME,
    `completed_at` DATETIME,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`imported_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_import_logs_type` (`import_type`),
    INDEX `idx_import_logs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MIGRATIONS TABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT NOT NULL,
    `executed_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
