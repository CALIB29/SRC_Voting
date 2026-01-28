-- Migration: add user-related fields to students for voting flows
-- Target DB: src_db

ALTER TABLE `students`
  ADD COLUMN `email` varchar(150) NULL AFTER `gender`,
  ADD COLUMN `password` varchar(255) NULL AFTER `email`,
  ADD COLUMN `is_approved` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password`,
  ADD COLUMN `has_voted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_approved`;

-- Optionally add indexes for frequent filters
CREATE INDEX idx_students_is_approved ON `students`(`is_approved`);
CREATE INDEX idx_students_has_voted ON `students`(`has_voted`);
