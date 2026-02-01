<?php
/**
 * English translations
 */

return [
    // Navigation
    'nav' => [
        'dashboard' => 'Dashboard',
        'books' => 'Books',
        'types' => 'Types',
        'categories' => 'Categories',
        'assignments' => 'Assignments',
        'import' => 'Import Quran',
        'history' => 'History',
        'backup' => 'Backup',
        'settings' => 'Settings',
        'logout' => 'Logout',
    ],

    // Authentication
    'auth' => [
        'login' => 'Login',
        'password' => 'Password',
        'wrong_password' => 'Incorrect password',
        'administration' => 'Administration',
    ],

    // Common actions
    'actions' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'add' => 'Add',
        'create' => 'Create',
        'update' => 'Update',
        'search' => 'Search',
        'filter' => 'Filter',
        'export' => 'Export',
        'import' => 'Import',
        'close' => 'Close',
        'confirm' => 'Confirm',
        'back' => 'Back',
        'next' => 'Next',
        'previous' => 'Previous',
        'start' => 'Start',
        'download' => 'Download',
    ],

    // Common labels
    'labels' => [
        'name' => 'Name',
        'description' => 'Description',
        'icon' => 'Icon',
        'color' => 'Color',
        'order' => 'Order',
        'status' => 'Status',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'type' => 'Type',
        'category' => 'Category',
        'language' => 'Language',
        'date' => 'Date',
        'actions' => 'Actions',
        'total' => 'Total',
        'yes' => 'Yes',
        'no' => 'No',
        'book' => 'Book',
    ],

    // Messages
    'messages' => [
        'success' => 'Operation successful',
        'error' => 'An error occurred',
        'confirm_delete' => 'Are you sure you want to delete this item?',
        'no_results' => 'No results found',
        'loading' => 'Loading...',
        'saving' => 'Saving...',
        'saved' => 'Saved',
        'deleted' => 'Deleted',
        'required_field' => 'This field is required',
    ],

    // Dashboard
    'dashboard' => [
        'title' => 'Dashboard',
        'welcome' => 'Welcome to Sadaa',
        'stats' => [
            'types' => 'Types',
            'categories' => 'Categories',
            'surahs' => 'Surahs',
            'ayahs' => 'Verses',
            'books' => 'Books',
            'languages' => 'Languages',
        ],
        'recent_imports' => 'Recent Imports',
        'quick_actions' => 'Quick Actions',
    ],

    // Books
    'books' => [
        'title' => 'Book Management',
        'add' => 'Add Book',
        'edit' => 'Edit Book',
        'book_title' => 'Book Title',
        'author' => 'Author',
        'chapters' => 'Chapters',
        'verses' => 'Verses',
    ],

    // Types
    'types' => [
        'title' => 'Type Management',
        'add' => 'Add Type',
        'edit' => 'Edit Type',
        'no_types' => 'No types available',
    ],

    // Categories
    'categories' => [
        'title' => 'Category Management',
        'add' => 'Add Category',
        'edit' => 'Edit Category',
        'select_type' => 'Select a type',
        'no_categories' => 'No categories available',
    ],

    // Assignments
    'assignments' => [
        'title' => 'Verse Assignments',
        'assign' => 'Assign',
        'unassign' => 'Unassign',
        'select_surah' => 'Select a surah',
        'select_category' => 'Select a category',
        'assigned_verses' => 'Assigned Verses',
    ],

    // Import
    'import' => [
        'title' => 'Import Quran',
        'select_languages' => 'Select languages',
        'import_all' => 'Import All',
        'import_selected' => 'Import Selected',
        'progress' => 'Progress',
        'importing' => 'Importing...',
        'complete' => 'Import Complete',
        'failed' => 'Import Failed',
    ],

    // History
    'history' => [
        'title' => 'Import History',
        'date' => 'Date',
        'type' => 'Type',
        'status' => 'Status',
        'details' => 'Details',
    ],

    // Settings
    'settings' => [
        'title' => 'Settings',
        'general' => 'General',
        'languages' => 'Languages',
        'app_name' => 'Application Name',
        'app_tagline' => 'Tagline',
        'manage_languages' => 'Manage Languages',
        'add_language' => 'Add Language',
        'language_code' => 'Language Code',
        'language_name' => 'Language Name',
        'rtl' => 'Right to Left (RTL)',
        'source_language' => 'Source Language',
        'quran_edition' => 'Quran Edition',
    ],

    // Backup & Restore
    'backup' => [
        'title' => 'Backup & Restore',
        'export' => 'Export Database',
        'import' => 'Restore Database',
        'export_desc' => 'Download a complete copy of your database.',
        'import_desc' => 'Restore your database from a backup file.',
        'format' => 'Format',
        'tables_included' => 'Tables Included',
        'download' => 'Download Backup',
        'restore' => 'Restore',
        'select_file' => 'Select a file',
        'accepted_formats' => 'Accepted formats',
        'import_warning' => 'Warning: This action will replace all existing data!',
        'confirm_import' => 'Are you sure you want to restore? All current data will be replaced.',
        'confirm_delete' => 'Are you sure you want to delete this backup?',
        'export_success' => 'Export successful',
        'export_error' => 'Export error',
        'import_success' => 'Restore successful',
        'import_error' => 'Restore error',
        'upload_error' => 'File upload error',
        'invalid_format' => 'Invalid file format',
        'invalid_json' => 'Invalid or corrupted JSON file',
        'recent_backups' => 'Recent Backups',
        'filename' => 'Filename',
        'size' => 'Size',
        'date' => 'Date',
    ],

    // Public interface
    'public' => [
        'tagline' => 'Echo of wisdom for the soul',
        'select_intention' => 'Select an intention...',
        'no_category' => 'No category available',
        'change_theme' => 'Change theme',
        'verse' => 'Verse',
        'surah' => 'Surah',
        'play' => 'Play',
        'pause' => 'Pause',
        'next_verse' => 'Next verse',
        'previous_verse' => 'Previous verse',
        'share' => 'Share',
        'share_verse' => 'Share verse',
        'copy' => 'Copy',
        'copied' => 'Copied!',
        'quran' => 'The Holy Quran',
        'read_quran' => 'Read Quran',
        'decrease_font' => 'Decrease font size',
        'increase_font' => 'Increase font size',
        'prev_surah' => 'Previous surah',
        'next_surah' => 'Next surah',
        'prev_page' => 'Previous page',
        'next_page' => 'Next page',
        'previous' => 'Previous',
        'next' => 'Next',
        'meccan' => 'Meccan',
        'medinan' => 'Medinan',
        'verses' => 'verses',
    ],

    // JavaScript translations (for frontend)
    'js' => [
        'select_intention' => 'Select an intention...',
        'no_category' => 'No category available',
        'loading' => 'Loading...',
        'error' => 'An error occurred',
        'copied' => 'Copied!',
        'play' => 'Play',
        'pause' => 'Pause',
        'confirm_delete' => 'Are you sure you want to delete?',
        'meccan' => 'Meccan',
        'medinan' => 'Medinan',
        'verses' => 'verses',
        'share_format' => 'Format',
        'share_theme' => 'Theme',
        'theme_dark' => 'Dark',
        'theme_light' => 'Light',
        'story' => 'Story',
        'square' => 'Square',
    ],
];
