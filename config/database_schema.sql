-- ============================================
-- Sadaa (صدى) - Database Schema
-- Quran Management Application
-- ============================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Create database
-- CREATE DATABASE IF NOT EXISTS sadaa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE sadaa;

-- ============================================
-- Languages Table
-- ============================================
CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) UNIQUE NOT NULL,
    name JSON NOT NULL,
    quran_edition VARCHAR(50),
    is_rtl BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    is_source BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default languages
INSERT INTO languages (code, name, quran_edition, is_rtl, is_active, is_source, sort_order) VALUES
('ar', '{"ar": "العربية", "fr": "Arabe", "en": "Arabic"}', 'quran-uthmani', TRUE, TRUE, TRUE, 1),
('fr', '{"ar": "الفرنسية", "fr": "Français", "en": "French"}', 'fr.hamidullah', FALSE, TRUE, FALSE, 2),
('en', '{"ar": "الإنجليزية", "fr": "Anglais", "en": "English"}', 'en.sahih', FALSE, TRUE, FALSE, 3);

-- ============================================
-- Types Table (État d'esprit, Type, Sciences)
-- ============================================
CREATE TABLE IF NOT EXISTS types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name JSON NOT NULL,
    description JSON,
    icon VARCHAR(100) DEFAULT 'mdi:tag',
    color VARCHAR(20) DEFAULT '#C99B35',
    slug VARCHAR(100) UNIQUE NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default types
INSERT INTO types (name, description, icon, slug, sort_order) VALUES
('{"ar": "حالة نفسية", "fr": "État d''esprit", "en": "State of Mind"}', '{"ar": "الحالات النفسية والروحية", "fr": "États psychologiques et spirituels", "en": "Psychological and spiritual states"}', 'mdi:emoticon-outline', 'etat-esprit', 1),
('{"ar": "نوع", "fr": "Type", "en": "Type"}', '{"ar": "أنواع الأشخاص المذكورين في القرآن", "fr": "Types de personnes mentionnées dans le Coran", "en": "Types of people mentioned in the Quran"}', 'mdi:account-group', 'type', 2),
('{"ar": "علوم", "fr": "Sciences", "en": "Sciences"}', '{"ar": "المواضيع العلمية في القرآن", "fr": "Thèmes scientifiques dans le Coran", "en": "Scientific themes in the Quran"}', 'mdi:atom', 'sciences', 3);

-- ============================================
-- Books Table
-- ============================================
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title JSON NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description JSON,
    icon VARCHAR(100) DEFAULT 'mdi:book-open-page-variant',
    language VARCHAR(10) DEFAULT 'ar',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default book
INSERT INTO books (title, slug, description, language) VALUES
('{"ar": "القرآن الكريم", "fr": "Le Coran", "en": "The Quran"}', 'quran', '{"ar": "كتاب الله المنزل", "fr": "Le Livre Saint de l''Islam", "en": "The Holy Book of Islam"}', 'ar');

-- ============================================
-- Categories Table
-- ============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_id INT NOT NULL,
    name JSON NOT NULL,
    description JSON,
    icon VARCHAR(100) DEFAULT 'mdi:tag-outline',
    color VARCHAR(20) DEFAULT '#C99B35',
    slug VARCHAR(100) UNIQUE NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample categories
INSERT INTO categories (type_id, name, description, icon, slug, sort_order) VALUES
-- Type: État d'esprit
(1, '{"ar": "الحزن", "fr": "Tristesse", "en": "Sadness"}', '{"ar": "آيات للحزين", "fr": "Versets pour les moments de tristesse", "en": "Verses for moments of sadness"}', 'mdi:emoticon-sad-outline', 'tristesse', 1),
(1, '{"ar": "الخوف", "fr": "Peur", "en": "Fear"}', '{"ar": "آيات للخائف", "fr": "Versets pour surmonter la peur", "en": "Verses to overcome fear"}', 'mdi:emoticon-confused-outline', 'peur', 2),
-- Type: Personnes
(2, '{"ar": "المؤمنون", "fr": "Croyants", "en": "Believers"}', '{"ar": "آيات عن المؤمنين", "fr": "Versets sur les croyants", "en": "Verses about believers"}', 'mdi:account-heart', 'croyants', 1),
(2, '{"ar": "الكافرون", "fr": "Mécréants", "en": "Disbelievers"}', '{"ar": "آيات عن الكافرين", "fr": "Versets sur les mécréants", "en": "Verses about disbelievers"}', 'mdi:account-off', 'mecreants', 2),
-- Type: Sciences
(3, '{"ar": "علم الأرض", "fr": "Géologie", "en": "Geology"}', '{"ar": "آيات عن الأرض", "fr": "Versets sur la terre et les montagnes", "en": "Verses about earth and mountains"}', 'mdi:earth', 'geologie', 1),
(3, '{"ar": "علم الأحياء", "fr": "Biologie", "en": "Biology"}', '{"ar": "آيات عن الحياة", "fr": "Versets sur la vie et la création", "en": "Verses about life and creation"}', 'mdi:dna', 'biologie', 2),
(3, '{"ar": "علم الكون", "fr": "Sciences de l''univers", "en": "Universe Sciences"}', '{"ar": "آيات عن الكون", "fr": "Versets sur l''univers", "en": "Verses about the universe"}', 'mdi:rocket-launch', 'sciences-univers', 3);

-- ============================================
-- Surahs Table
-- ============================================
CREATE TABLE IF NOT EXISTS surahs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    number INT NOT NULL,
    name JSON NOT NULL,
    revelation_type ENUM('meccan', 'medinan') DEFAULT 'meccan',
    ayah_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_book_surah (book_id, number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Ayahs (Verses) Table
-- ============================================
CREATE TABLE IF NOT EXISTS ayahs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    surah_id INT NOT NULL,
    ayah_number INT NOT NULL,
    text JSON NOT NULL,
    juz INT,
    hizb INT,
    page INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (surah_id) REFERENCES surahs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_surah_ayah (surah_id, ayah_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Assignment Groups Table
-- ============================================
CREATE TABLE IF NOT EXISTS assignment_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    surah_id INT NOT NULL,
    title VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (surah_id) REFERENCES surahs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Ayah Categories (Many-to-Many via Groups)
-- ============================================
CREATE TABLE IF NOT EXISTS ayah_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ayah_id INT NOT NULL,
    category_id INT NOT NULL,
    assignment_group_id INT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ayah_id) REFERENCES ayahs(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_group_id) REFERENCES assignment_groups(id) ON DELETE CASCADE,
    UNIQUE KEY unique_ayah_category_group (ayah_id, category_id, assignment_group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tags Table
-- ============================================
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    color VARCHAR(20) DEFAULT '#C99B35',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Assignment Group Tags (Many-to-Many)
-- ============================================
CREATE TABLE IF NOT EXISTS assignment_group_tags (
    assignment_group_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (assignment_group_id, tag_id),
    FOREIGN KEY (assignment_group_id) REFERENCES assignment_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Imports Table (Track API imports)
-- ============================================
CREATE TABLE IF NOT EXISTS imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('quran', 'book') NOT NULL DEFAULT 'quran',
    source VARCHAR(100) DEFAULT 'alquran.cloud',
    status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending',
    languages JSON,
    surahs_imported INT DEFAULT 0,
    total_surahs INT DEFAULT 114,
    ayahs_imported INT DEFAULT 0,
    error_message TEXT,
    quran_edition VARCHAR(100) DEFAULT NULL COMMENT 'Edition du Coran (ex: quran-uthmani)',
    quran_version VARCHAR(50) DEFAULT NULL COMMENT 'Version de l''édition',
    translation_references JSON DEFAULT NULL COMMENT 'Références des traductions par langue',
    metadata JSON DEFAULT NULL COMMENT 'Autres métadonnées de l''import',
    notes TEXT DEFAULT NULL COMMENT 'Notes sur l''import',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Settings Table
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('app_name', 'Sadaa'),
('app_tagline', '{"ar": "صدى الحكمة للروح", "fr": "Écho de sagesse pour l''âme", "en": "Echo of wisdom for the soul"}'),
('primary_color', '#C99B35'),
('default_language', 'fr'),
('admin_password', '$2y$10$q1EkXztCr87mt3hMmO7v5O.U7djoTp/5wF/0zlRbQHH2ZICqshdqy'); -- Default: admin123

-- ============================================
-- Indexes for Performance
-- ============================================
CREATE INDEX idx_categories_type ON categories(type_id);
CREATE INDEX idx_surahs_book ON surahs(book_id);
CREATE INDEX idx_ayahs_surah ON ayahs(surah_id);
CREATE INDEX idx_ayah_categories_ayah ON ayah_categories(ayah_id);
CREATE INDEX idx_ayah_categories_category ON ayah_categories(category_id);
CREATE INDEX idx_imports_status ON imports(status);
