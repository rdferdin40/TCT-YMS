-- TCT-YMS Database Seeders
-- Initial data for a new installation

SET NAMES utf8mb4;

-- ============================================================
-- ROLES
-- ============================================================

INSERT INTO `roles` (`name`, `display_name`, `description`, `permissions`, `is_system`) VALUES
('admin', 'Administrator', 'Full system access with all permissions', '["*"]', 1),
('supervisor', 'Supervisor', 'Manages yard operations, tasks, and reports', '["dashboard.view","yard_map.view","yard_map.edit","trailers.view","trailers.edit","trailers.create","trailers.delete","moves.view","moves.create","moves.assign","moves.complete","dock_doors.view","dock_doors.manage","gate.view","gate.checkin","gate.checkout","reports.view","reports.export","users.view"]', 1),
('dispatcher', 'Dispatcher', 'Handles gate operations and trailer assignments', '["dashboard.view","yard_map.view","yard_map.edit","trailers.view","trailers.edit","trailers.create","moves.view","moves.create","dock_doors.view","dock_doors.assign","gate.view","gate.checkin","gate.checkout","reports.view"]', 1),
('spotter', 'Spotter', 'Mobile yard worker who executes move tasks', '["dashboard.view","yard_map.view","moves.view","moves.claim","moves.complete","trailers.view"]', 1),
('viewer', 'Viewer', 'Read-only access to yard map and dashboards', '["dashboard.view","yard_map.view","trailers.view","dock_doors.view","reports.view"]', 1);

-- ============================================================
-- TRAILER STATUSES
-- ============================================================

INSERT INTO `trailer_statuses` (`name`, `display_name`, `color`, `bg_color`, `icon`, `description`, `is_yard_status`, `is_door_status`, `is_final_status`, `dwell_warning_hours`, `dwell_critical_hours`, `sort_order`, `is_active`) VALUES
('arrived', 'Arrived', '#3B82F6', '#EFF6FF', 'truck', 'Trailer has arrived at the facility', 1, 0, 0, 2, 4, 1, 1),
('staged', 'Staged', '#8B5CF6', '#F5F3FF', 'package', 'Trailer is staged in yard awaiting assignment', 1, 0, 0, 4, 8, 2, 1),
('at_door', 'At Door', '#F59E0B', '#FFFBEB', 'door-open', 'Trailer is positioned at a dock door', 0, 1, 0, 2, 4, 3, 1),
('loading', 'Loading', '#10B981', '#ECFDF5', 'arrow-up', 'Trailer is being loaded', 0, 1, 0, 4, 8, 4, 1),
('unloading', 'Unloading', '#06B6D4', '#ECFEFF', 'arrow-down', 'Trailer is being unloaded', 0, 1, 0, 4, 8, 5, 1),
('loaded', 'Loaded', '#22C55E', '#F0FDF4', 'check-circle', 'Trailer is loaded and ready', 1, 0, 0, 2, 4, 6, 1),
('empty', 'Empty', '#6B7280', '#F9FAFB', 'box', 'Trailer is empty', 1, 0, 0, 8, 24, 7, 1),
('hold', 'On Hold', '#EF4444', '#FEF2F2', 'pause-circle', 'Trailer is on hold - do not move', 1, 0, 0, 24, 48, 8, 1),
('departed', 'Departed', '#1F2937', '#F3F4F6', 'truck', 'Trailer has left the facility', 0, 0, 1, 0, 0, 9, 1);

-- ============================================================
-- STATUS TRANSITIONS (State Machine)
-- ============================================================

INSERT INTO `status_transitions` (`from_status_id`, `to_status_id`, `requires_role`, `requires_door`, `requires_slot`, `is_active`) VALUES
-- From Arrived
(1, 2, NULL, 0, 1, 1),  -- arrived -> staged
(1, 3, NULL, 1, 0, 1),  -- arrived -> at_door
(1, 9, NULL, 0, 0, 1),  -- arrived -> departed (quick turnaround)

-- From Staged
(2, 3, NULL, 1, 0, 1),  -- staged -> at_door
(2, 8, NULL, 0, 0, 1),  -- staged -> hold
(2, 9, NULL, 0, 0, 1),  -- staged -> departed

-- From At Door
(3, 4, NULL, 0, 0, 1),  -- at_door -> loading
(3, 5, NULL, 0, 0, 1),  -- at_door -> unloading
(3, 2, NULL, 0, 1, 1),  -- at_door -> staged (returned to yard)

-- From Loading
(4, 6, NULL, 0, 0, 1),  -- loading -> loaded
(4, 3, NULL, 0, 0, 1),  -- loading -> at_door (paused)

-- From Unloading
(5, 7, NULL, 0, 0, 1),  -- unloading -> empty
(5, 3, NULL, 0, 0, 1),  -- unloading -> at_door (paused)

-- From Loaded
(6, 2, NULL, 0, 1, 1),  -- loaded -> staged
(6, 9, NULL, 0, 0, 1),  -- loaded -> departed

-- From Empty
(7, 2, NULL, 0, 1, 1),  -- empty -> staged
(7, 3, NULL, 1, 0, 1),  -- empty -> at_door
(7, 9, NULL, 0, 0, 1),  -- empty -> departed

-- From Hold
(8, 2, 'supervisor', 0, 0, 1),  -- hold -> staged (supervisor only)
(8, 9, 'supervisor', 0, 0, 1);  -- hold -> departed (supervisor only)

-- ============================================================
-- YARD ZONES
-- ============================================================

INSERT INTO `yard_zones` (`name`, `code`, `description`, `color`, `sort_order`, `is_active`) VALUES
('Main Yard', 'MAIN', 'Primary staging area for trailers', '#3B82F6', 1, 1),
('Dock Area', 'DOCK', 'Area near dock doors', '#F59E0B', 2, 1),
('Drop Lot', 'DROP', 'Overflow parking and drop trailer area', '#8B5CF6', 3, 1),
('Outbound', 'OUT', 'Outbound staging for departures', '#10B981', 4, 1);

-- ============================================================
-- YARD ROWS
-- ============================================================

INSERT INTO `yard_rows` (`zone_id`, `name`, `code`, `slots_count`, `grid_row`, `grid_col`, `orientation`, `is_active`) VALUES
(1, 'Row A', 'A', 12, 0, 0, 'horizontal', 1),
(1, 'Row B', 'B', 12, 1, 0, 'horizontal', 1),
(1, 'Row C', 'C', 12, 2, 0, 'horizontal', 1),
(1, 'Row D', 'D', 10, 3, 0, 'horizontal', 1),
(3, 'Drop Row 1', 'DR1', 8, 4, 0, 'horizontal', 1),
(3, 'Drop Row 2', 'DR2', 8, 5, 0, 'horizontal', 1),
(4, 'Outbound Row', 'OB1', 6, 6, 0, 'horizontal', 1);

-- ============================================================
-- YARD SLOTS (Generated for each row)
-- ============================================================

-- Row A (12 slots)
INSERT INTO `yard_slots` (`row_id`, `slot_number`, `label`, `slot_type`, `grid_x`, `grid_y`, `is_available`)
SELECT 1, n, CONCAT('A', n), 'standard', n-1, 0, 1 FROM (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) numbers;

-- Row B (12 slots)
INSERT INTO `yard_slots` (`row_id`, `slot_number`, `label`, `slot_type`, `grid_x`, `grid_y`, `is_available`)
SELECT 2, n, CONCAT('B', n), 'standard', n-1, 1, 1 FROM (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) numbers;

-- Row C (12 slots)
INSERT INTO `yard_slots` (`row_id`, `slot_number`, `label`, `slot_type`, `grid_x`, `grid_y`, `is_available`)
SELECT 3, n, CONCAT('C', n), 'standard', n-1, 2, 1 FROM (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12) numbers;

-- Row D (10 slots)
INSERT INTO `yard_slots` (`row_id`, `slot_number`, `label`, `slot_type`, `grid_x`, `grid_y`, `is_available`)
SELECT 4, n, CONCAT('D', n), 'standard', n-1, 3, 1 FROM (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10) numbers;

-- Drop Row 1 (8 slots)
INSERT INTO `yard_slots` (`row_id`, `slot_number`, `label`, `slot_type`, `grid_x`, `grid_y`, `is_available`)
SELECT 5, n, CONCAT('DR1-', n), 'standard', n-1, 4, 1 FROM (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8) numbers;

-- Drop Row 2 (8 slots)
INSERT INTO `yard_slots` (`row_id`, `slot_number`, `label`, `slot_type`, `grid_x`, `grid_y`, `is_available`)
SELECT 6, n, CONCAT('DR2-', n), 'standard', n-1, 5, 1 FROM (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8) numbers;

-- Outbound Row (6 slots)
INSERT INTO `yard_slots` (`row_id`, `slot_number`, `label`, `slot_type`, `grid_x`, `grid_y`, `is_available`)
SELECT 7, n, CONCAT('OB', n), 'standard', n-1, 6, 1 FROM (SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) numbers;

-- ============================================================
-- DOCK DOORS
-- ============================================================

INSERT INTO `dock_doors` (`door_number`, `name`, `zone_id`, `door_type`, `grid_x`, `grid_y`, `has_dock_leveler`, `has_dock_seal`, `status`, `is_active`) VALUES
('D01', 'Door 1', 2, 'both', 0, 0, 1, 1, 'available', 1),
('D02', 'Door 2', 2, 'both', 1, 0, 1, 1, 'available', 1),
('D03', 'Door 3', 2, 'both', 2, 0, 1, 1, 'available', 1),
('D04', 'Door 4', 2, 'both', 3, 0, 1, 1, 'available', 1),
('D05', 'Door 5', 2, 'both', 4, 0, 1, 1, 'available', 1),
('D06', 'Door 6', 2, 'both', 5, 0, 1, 1, 'available', 1),
('D07', 'Door 7', 2, 'inbound', 6, 0, 1, 1, 'available', 1),
('D08', 'Door 8', 2, 'inbound', 7, 0, 1, 1, 'available', 1),
('D09', 'Door 9', 2, 'outbound', 8, 0, 1, 1, 'available', 1),
('D10', 'Door 10', 2, 'outbound', 9, 0, 1, 1, 'available', 1),
('D11', 'Door 11', 2, 'outbound', 10, 0, 1, 1, 'available', 1),
('D12', 'Door 12', 2, 'outbound', 11, 0, 1, 1, 'available', 1);

-- ============================================================
-- SAMPLE CARRIERS
-- ============================================================

INSERT INTO `carriers` (`name`, `code`, `mc_number`, `scac_code`, `contact_name`, `contact_phone`, `city`, `state`, `is_active`) VALUES
('Swift Transportation', 'SWIFT', 'MC-12345', 'SWFT', 'John Smith', '555-0100', 'Phoenix', 'AZ', 1),
('J.B. Hunt', 'JBHT', 'MC-23456', 'JBHT', 'Jane Doe', '555-0101', 'Lowell', 'AR', 1),
('Werner Enterprises', 'WERN', 'MC-34567', 'WERN', 'Bob Wilson', '555-0102', 'Omaha', 'NE', 1),
('Schneider National', 'SNDR', 'MC-45678', 'SNDR', 'Mary Johnson', '555-0103', 'Green Bay', 'WI', 1),
('XPO Logistics', 'XPO', 'MC-56789', 'XPOL', 'Tom Brown', '555-0104', 'Greenwich', 'CT', 1),
('Old Dominion', 'ODFL', 'MC-67890', 'ODFL', 'Lisa Davis', '555-0105', 'Thomasville', 'NC', 1),
('FedEx Freight', 'FDXF', 'MC-78901', 'FXFE', 'Mike Garcia', '555-0106', 'Memphis', 'TN', 1),
('Landstar', 'LAND', 'MC-89012', 'LSTR', 'Sarah Miller', '555-0107', 'Jacksonville', 'FL', 1),
('Owner Operator', 'OWNR', NULL, NULL, NULL, NULL, 'Laredo', 'TX', 1),
('Local Carrier', 'LOCAL', NULL, NULL, NULL, NULL, 'Laredo', 'TX', 1);

-- ============================================================
-- SYSTEM SETTINGS
-- ============================================================

INSERT INTO `settings` (`key`, `value`, `type`, `group`, `label`, `description`, `is_public`) VALUES
-- General Settings
('site_name', 'TCT Yard Management System', 'string', 'general', 'Site Name', 'The name of your yard facility', 1),
('site_logo', NULL, 'string', 'general', 'Site Logo', 'Custom logo URL', 1),
('timezone', 'America/Chicago', 'string', 'general', 'Timezone', 'Default timezone for the system', 0),
('date_format', 'M d, Y', 'string', 'general', 'Date Format', 'Display format for dates', 0),
('time_format', 'g:i A', 'string', 'general', 'Time Format', 'Display format for times', 0),

-- Yard Settings
('default_dwell_warning_hours', '4', 'integer', 'yard', 'Dwell Warning Hours', 'Hours before showing dwell warning', 0),
('default_dwell_critical_hours', '8', 'integer', 'yard', 'Dwell Critical Hours', 'Hours before showing critical dwell alert', 0),
('auto_refresh_interval', '60', 'integer', 'yard', 'Auto Refresh Interval', 'Seconds between dashboard auto-refresh', 0),
('max_trailers_per_slot', '1', 'integer', 'yard', 'Max Trailers Per Slot', 'Maximum trailers allowed in a single slot', 0),

-- Gate Settings
('require_driver_name', 'true', 'boolean', 'gate', 'Require Driver Name', 'Require driver name on check-in', 0),
('require_seal_number', 'false', 'boolean', 'gate', 'Require Seal Number', 'Require seal number on check-in', 0),
('allow_photo_upload', 'true', 'boolean', 'gate', 'Allow Photo Upload', 'Enable photo uploads at gate', 0),

-- Notification Settings
('email_notifications', 'true', 'boolean', 'notifications', 'Email Notifications', 'Enable email notifications', 0),
('dwell_alert_emails', '', 'string', 'notifications', 'Dwell Alert Emails', 'Comma-separated emails for dwell alerts', 0);

-- ============================================================
-- FEATURE FLAGS
-- ============================================================

INSERT INTO `feature_flags` (`name`, `display_name`, `description`, `is_enabled`, `required_role`) VALUES
('dark_mode', 'Dark Mode', 'Enable dark mode theme toggle', 1, NULL),
('csv_import', 'CSV Import', 'Enable CSV data import functionality', 1, 'admin'),
('csv_export', 'CSV Export', 'Enable CSV data export functionality', 1, NULL),
('photo_upload', 'Photo Upload', 'Enable photo uploads at gate check-in', 1, NULL),
('move_task_pool', 'Move Task Pool', 'Allow spotters to claim tasks from pool', 1, NULL),
('advanced_reports', 'Advanced Reports', 'Enable advanced reporting features', 1, 'supervisor'),
('api_access', 'API Access', 'Enable REST API endpoints', 0, 'admin'),
('yard_map_drag_drop', 'Yard Map Drag & Drop', 'Enable drag and drop on yard map', 1, NULL);
