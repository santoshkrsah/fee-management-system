-- Migration: Add WhatsApp and Aadhaar columns to students table
-- Date: 2026-04-06
-- Description: Add whatsapp_number and aadhar_number columns to support new student contact features

ALTER TABLE `students`
ADD COLUMN `whatsapp_number` VARCHAR(15) DEFAULT NULL AFTER `contact_number`,
ADD COLUMN `aadhar_number` VARCHAR(12) DEFAULT NULL AFTER `whatsapp_number`,
ADD UNIQUE KEY `idx_aadhar_unique` (`aadhar_number`);
