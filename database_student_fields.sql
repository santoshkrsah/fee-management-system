-- ================================================================
-- Add Additional Student Fields
-- ================================================================
-- Adds WhatsApp Number and Aadhar Number fields to students table
-- ================================================================

-- Add new columns to students table if they don't exist
ALTER TABLE `students`
ADD COLUMN IF NOT EXISTS `whatsapp_number` VARCHAR(15)
COMMENT 'Alternate contact / WhatsApp number (optional)',
ADD COLUMN IF NOT EXISTS `aadhar_number` VARCHAR(12) UNIQUE
COMMENT 'Aadhar identification number (optional, unique)',
MODIFY COLUMN `roll_number` VARCHAR(50) NOT NULL DEFAULT ''
COMMENT 'Roll number (now mandatory)';

-- Create index for aadhar_number for faster searching
CREATE INDEX IF NOT EXISTS `idx_aadhar_number` ON `students` (`aadhar_number`);

-- Create index for whatsapp_number for faster searching
CREATE INDEX IF NOT EXISTS `idx_whatsapp_number` ON `students` (`whatsapp_number`);

-- Update last_name column to allow NULL (make it optional)
ALTER TABLE `students`
MODIFY COLUMN `last_name` VARCHAR(100);

-- ================================================================
-- End of Additional Student Fields Migration
-- ================================================================
