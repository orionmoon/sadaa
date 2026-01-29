-- ============================================
-- Migration: Add metadata fields to imports table
-- Date: 2026-01-27
-- ============================================

USE sadaa;

-- Add metadata columns to imports table
ALTER TABLE imports
ADD COLUMN quran_edition VARCHAR(100) DEFAULT NULL COMMENT 'Edition du Coran (ex: quran-uthmani)',
ADD COLUMN quran_version VARCHAR(50) DEFAULT NULL COMMENT 'Version de l''édition',
ADD COLUMN translation_references JSON DEFAULT NULL COMMENT 'Références des traductions par langue {"ar": "quran-uthmani", "fr": "fr.hamidullah"}',
ADD COLUMN metadata JSON DEFAULT NULL COMMENT 'Autres métadonnées de l''import',
ADD COLUMN notes TEXT DEFAULT NULL COMMENT 'Notes sur l''import';

-- Update existing records with default values
UPDATE imports
SET quran_edition = 'quran-uthmani',
    translation_references = JSON_OBJECT('ar', 'quran-uthmani')
WHERE quran_edition IS NULL;
