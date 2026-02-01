<?php
/**
 * German translations
 */

return [
    // Navigation
    'nav' => [
        'dashboard' => 'Dashboard',
        'books' => 'Bücher',
        'types' => 'Typen',
        'categories' => 'Kategorien',
        'assignments' => 'Zuordnungen',
        'import' => 'Koran importieren',
        'history' => 'Verlauf',
        'backup' => 'Sicherung',
        'settings' => 'Einstellungen',
        'logout' => 'Abmelden',
    ],

    // Authentication
    'auth' => [
        'login' => 'Anmelden',
        'password' => 'Passwort',
        'wrong_password' => 'Falsches Passwort',
        'administration' => 'Administration',
    ],

    // Common actions
    'actions' => [
        'save' => 'Speichern',
        'cancel' => 'Abbrechen',
        'delete' => 'Löschen',
        'edit' => 'Bearbeiten',
        'add' => 'Hinzufügen',
        'create' => 'Erstellen',
        'update' => 'Aktualisieren',
        'search' => 'Suchen',
        'filter' => 'Filtern',
        'export' => 'Exportieren',
        'import' => 'Importieren',
        'close' => 'Schließen',
        'confirm' => 'Bestätigen',
        'back' => 'Zurück',
        'next' => 'Weiter',
        'previous' => 'Zurück',
        'start' => 'Start',
        'download' => 'Herunterladen',
    ],

    // Common labels
    'labels' => [
        'name' => 'Name',
        'description' => 'Beschreibung',
        'icon' => 'Symbol',
        'color' => 'Farbe',
        'order' => 'Reihenfolge',
        'status' => 'Status',
        'active' => 'Aktiv',
        'inactive' => 'Inaktiv',
        'type' => 'Typ',
        'category' => 'Kategorie',
        'language' => 'Sprache',
        'date' => 'Datum',
        'actions' => 'Aktionen',
        'total' => 'Gesamt',
        'yes' => 'Ja',
        'no' => 'Nein',
        'book' => 'Buch',
    ],

    // Messages
    'messages' => [
        'success' => 'Vorgang erfolgreich',
        'error' => 'Ein Fehler ist aufgetreten',
        'confirm_delete' => 'Möchten Sie dieses Element wirklich löschen?',
        'no_results' => 'Keine Ergebnisse gefunden',
        'loading' => 'Laden...',
        'saving' => 'Speichern...',
        'saved' => 'Gespeichert',
        'deleted' => 'Gelöscht',
        'required_field' => 'Dieses Feld ist erforderlich',
    ],

    // Dashboard
    'dashboard' => [
        'title' => 'Dashboard',
        'welcome' => 'Willkommen bei Sadaa',
        'stats' => [
            'types' => 'Typen',
            'categories' => 'Kategorien',
            'surahs' => 'Suren',
            'ayahs' => 'Verse',
            'books' => 'Bücher',
            'languages' => 'Sprachen',
        ],
        'recent_imports' => 'Letzte Importe',
        'quick_actions' => 'Schnellaktionen',
    ],

    // Books
    'books' => [
        'title' => 'Buchverwaltung',
        'add' => 'Buch hinzufügen',
        'edit' => 'Buch bearbeiten',
        'book_title' => 'Buchtitel',
        'author' => 'Autor',
        'chapters' => 'Kapitel',
        'verses' => 'Verse',
    ],

    // Types
    'types' => [
        'title' => 'Typenverwaltung',
        'add' => 'Typ hinzufügen',
        'edit' => 'Typ bearbeiten',
        'no_types' => 'Keine Typen verfügbar',
    ],

    // Categories
    'categories' => [
        'title' => 'Kategorieverwaltung',
        'add' => 'Kategorie hinzufügen',
        'edit' => 'Kategorie bearbeiten',
        'select_type' => 'Typ auswählen',
        'no_categories' => 'Keine Kategorien verfügbar',
    ],

    // Assignments
    'assignments' => [
        'title' => 'Vers-Zuordnungen',
        'assign' => 'Zuweisen',
        'unassign' => 'Zuweisung aufheben',
        'select_surah' => 'Sure auswählen',
        'select_category' => 'Kategorie auswählen',
        'assigned_verses' => 'Zugeordnete Verse',
    ],

    // Import
    'import' => [
        'title' => 'Koran importieren',
        'select_languages' => 'Sprachen auswählen',
        'import_all' => 'Alle importieren',
        'import_selected' => 'Ausgewählte importieren',
        'progress' => 'Fortschritt',
        'importing' => 'Import läuft...',
        'complete' => 'Import abgeschlossen',
        'failed' => 'Import fehlgeschlagen',
    ],

    // History
    'history' => [
        'title' => 'Importverlauf',
        'date' => 'Datum',
        'type' => 'Typ',
        'status' => 'Status',
        'details' => 'Details',
    ],

    // Settings
    'settings' => [
        'title' => 'Einstellungen',
        'general' => 'Allgemein',
        'languages' => 'Sprachen',
        'app_name' => 'Anwendungsname',
        'app_tagline' => 'Slogan',
        'manage_languages' => 'Sprachen verwalten',
        'add_language' => 'Sprache hinzufügen',
        'language_code' => 'Sprachcode',
        'language_name' => 'Sprachname',
        'rtl' => 'Rechts-nach-links (RTL)',
        'source_language' => 'Quellsprache',
        'quran_edition' => 'Koran-Ausgabe',
    ],

    // Backup & Restore
    'backup' => [
        'title' => 'Sicherung & Wiederherstellung',
        'export' => 'Datenbank exportieren',
        'import' => 'Datenbank wiederherstellen',
        'export_desc' => 'Laden Sie eine vollständige Kopie Ihrer Datenbank herunter.',
        'import_desc' => 'Stellen Sie Ihre Datenbank aus einer Sicherungsdatei wieder her.',
        'format' => 'Format',
        'tables_included' => 'Enthaltene Tabellen',
        'download' => 'Sicherung herunterladen',
        'restore' => 'Wiederherstellen',
        'select_file' => 'Datei auswählen',
        'accepted_formats' => 'Akzeptierte Formate',
        'import_warning' => 'Warnung: Diese Aktion ersetzt alle vorhandenen Daten!',
        'confirm_import' => 'Möchten Sie wirklich wiederherstellen? Alle aktuellen Daten werden ersetzt.',
        'confirm_delete' => 'Möchten Sie diese Sicherung wirklich löschen?',
        'export_success' => 'Export erfolgreich',
        'export_error' => 'Exportfehler',
        'import_success' => 'Wiederherstellung erfolgreich',
        'import_error' => 'Wiederherstellungsfehler',
        'upload_error' => 'Dateiupload-Fehler',
        'invalid_format' => 'Ungültiges Dateiformat',
        'invalid_json' => 'Ungültige oder beschädigte JSON-Datei',
        'recent_backups' => 'Letzte Sicherungen',
        'filename' => 'Dateiname',
        'size' => 'Größe',
        'date' => 'Datum',
    ],

    // Public interface
    'public' => [
        'tagline' => 'Echo der Weisheit für die Seele',
        'select_intention' => 'Wählen Sie eine Absicht...',
        'no_category' => 'Keine Kategorie verfügbar',
        'change_theme' => 'Design ändern',
        'verse' => 'Vers',
        'surah' => 'Sure',
        'play' => 'Abspielen',
        'pause' => 'Pause',
        'next_verse' => 'Nächster Vers',
        'previous_verse' => 'Vorheriger Vers',
        'share' => 'Teilen',
        'share_verse' => 'Vers teilen',
        'copy' => 'Kopieren',
        'copied' => 'Kopiert!',
        'quran' => 'Der Heilige Koran',
        'read_quran' => 'Koran lesen',
        'decrease_font' => 'Schrift verkleinern',
        'increase_font' => 'Schrift vergrößern',
        'prev_surah' => 'Vorherige Sure',
        'next_surah' => 'Nächste Sure',
        'prev_page' => 'Vorherige Seite',
        'next_page' => 'Nächste Seite',
        'previous' => 'Zurück',
        'next' => 'Weiter',
        'meccan' => 'Mekkanisch',
        'medinan' => 'Medinensisch',
        'verses' => 'Verse',
    ],

    // JavaScript translations (for frontend)
    'js' => [
        'select_intention' => 'Wählen Sie eine Absicht...',
        'no_category' => 'Keine Kategorie verfügbar',
        'loading' => 'Laden...',
        'error' => 'Ein Fehler ist aufgetreten',
        'copied' => 'Kopiert!',
        'play' => 'Abspielen',
        'pause' => 'Pause',
        'confirm_delete' => 'Möchten Sie wirklich löschen?',
        'meccan' => 'Mekkanisch',
        'medinan' => 'Medinensisch',
        'verses' => 'Verse',
        'share_format' => 'Format',
        'share_theme' => 'Design',
        'theme_dark' => 'Dunkel',
        'theme_light' => 'Hell',
        'story' => 'Story',
        'square' => 'Quadrat',
    ],
];
