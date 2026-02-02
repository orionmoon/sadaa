<?php
/**
 * French translations - Traductions françaises
 */

return [
    // Navigation
    'nav' => [
        'dashboard' => 'Tableau de bord',
        'books' => 'Livres',
        'types' => 'Types',
        'categories' => 'Catégories',
        'assignments' => 'Assignations',
        'import' => 'Import Coran',
        'history' => 'Historique',
        'backup' => 'Sauvegarde',
        'settings' => 'Paramètres',
        'logout' => 'Déconnexion',
    ],

    // Authentication
    'auth' => [
        'login' => 'Connexion',
        'password' => 'Mot de passe',
        'wrong_password' => 'Mot de passe incorrect',
        'administration' => 'Administration',
    ],

    // Common actions
    'actions' => [
        'save' => 'Enregistrer',
        'cancel' => 'Annuler',
        'delete' => 'Supprimer',
        'edit' => 'Modifier',
        'add' => 'Ajouter',
        'create' => 'Créer',
        'update' => 'Mettre à jour',
        'search' => 'Rechercher',
        'filter' => 'Filtrer',
        'export' => 'Exporter',
        'import' => 'Importer',
        'close' => 'Fermer',
        'confirm' => 'Confirmer',
        'back' => 'Retour',
        'next' => 'Suivant',
        'previous' => 'Précédent',
        'start' => 'Commencer',
        'download' => 'Télécharger',
        'back_home' => 'Retour à l\'accueil',
    ],

    // Common labels
    'labels' => [
        'name' => 'Nom',
        'description' => 'Description',
        'icon' => 'Icône',
        'color' => 'Couleur',
        'order' => 'Ordre',
        'status' => 'Statut',
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'type' => 'Type',
        'category' => 'Catégorie',
        'language' => 'Langue',
        'date' => 'Date',
        'actions' => 'Actions',
        'total' => 'Total',
        'yes' => 'Oui',
        'no' => 'Non',
        'book' => 'Livre',
    ],

    // Messages
    'messages' => [
        'success' => 'Opération réussie',
        'error' => 'Une erreur est survenue',
        'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer cet élément ?',
        'no_results' => 'Aucun résultat trouvé',
        'loading' => 'Chargement...',
        'saving' => 'Enregistrement...',
        'saved' => 'Enregistré',
        'deleted' => 'Supprimé',
        'required_field' => 'Ce champ est requis',
    ],

    // Dashboard
    'dashboard' => [
        'title' => 'Tableau de bord',
        'welcome' => 'Bienvenue sur Sadaa',
        'stats' => [
            'types' => 'Types',
            'categories' => 'Catégories',
            'surahs' => 'Sourates',
            'ayahs' => 'Versets',
            'books' => 'Livres',
            'languages' => 'Langues',
        ],
        'recent_imports' => 'Imports récents',
        'quick_actions' => 'Actions rapides',
    ],

    // Books
    'books' => [
        'title' => 'Gestion des livres',
        'add' => 'Ajouter un livre',
        'edit' => 'Modifier le livre',
        'book_title' => 'Titre du livre',
        'author' => 'Auteur',
        'chapters' => 'Chapitres',
        'verses' => 'Versets',
    ],

    // Types
    'types' => [
        'title' => 'Gestion des types',
        'add' => 'Ajouter un type',
        'edit' => 'Modifier le type',
        'no_types' => 'Aucun type disponible',
    ],

    // Categories
    'categories' => [
        'title' => 'Gestion des catégories',
        'add' => 'Ajouter une catégorie',
        'edit' => 'Modifier la catégorie',
        'select_type' => 'Sélectionner un type',
        'no_categories' => 'Aucune catégorie disponible',
    ],

    // Assignments
    'assignments' => [
        'title' => 'Assignation des versets',
        'assign' => 'Assigner',
        'unassign' => 'Désassigner',
        'select_surah' => 'Sélectionner une sourate',
        'select_category' => 'Sélectionner une catégorie',
        'assigned_verses' => 'Versets assignés',
    ],

    // Import
    'import' => [
        'title' => 'Import du Coran',
        'select_languages' => 'Sélectionner les langues',
        'import_all' => 'Importer tout',
        'import_selected' => 'Importer la sélection',
        'progress' => 'Progression',
        'importing' => 'Importation en cours...',
        'complete' => 'Import terminé',
        'failed' => 'Échec de l\'import',
    ],

    // History
    'history' => [
        'title' => 'Historique des imports',
        'date' => 'Date',
        'type' => 'Type',
        'status' => 'Statut',
        'details' => 'Détails',
    ],

    // Settings
    'settings' => [
        'title' => 'Paramètres',
        'general' => 'Général',
        'languages' => 'Langues',
        'app_name' => 'Nom de l\'application',
        'app_tagline' => 'Slogan',
        'manage_languages' => 'Gérer les langues',
        'add_language' => 'Ajouter une langue',
        'language_code' => 'Code langue',
        'language_name' => 'Nom de la langue',
        'rtl' => 'Droite à gauche (RTL)',
        'source_language' => 'Langue source',
        'quran_edition' => 'Édition Coran',
    ],

    // Backup & Restore
    'backup' => [
        'title' => 'Sauvegarde & Restauration',
        'export' => 'Exporter la base de données',
        'import' => 'Restaurer la base de données',
        'export_desc' => 'Téléchargez une copie complète de votre base de données.',
        'import_desc' => 'Restaurez votre base de données à partir d\'un fichier de sauvegarde.',
        'format' => 'Format',
        'tables_included' => 'Tables incluses',
        'download' => 'Télécharger la sauvegarde',
        'restore' => 'Restaurer',
        'select_file' => 'Sélectionner un fichier',
        'accepted_formats' => 'Formats acceptés',
        'import_warning' => 'Attention : Cette action remplacera toutes les données existantes !',
        'confirm_import' => 'Êtes-vous sûr de vouloir restaurer ? Toutes les données actuelles seront remplacées.',
        'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer cette sauvegarde ?',
        'export_success' => 'Export réussi',
        'export_error' => 'Erreur lors de l\'export',
        'import_success' => 'Restauration réussie',
        'import_error' => 'Erreur lors de la restauration',
        'upload_error' => 'Erreur lors du téléchargement du fichier',
        'invalid_format' => 'Format de fichier non valide',
        'invalid_json' => 'Fichier JSON invalide ou corrompu',
        'recent_backups' => 'Sauvegardes récentes',
        'filename' => 'Nom du fichier',
        'size' => 'Taille',
        'date' => 'Date',
    ],

    // Public interface
    'public' => [
        'tagline' => 'Écho de sagesse pour l\'âme',
        'select_intention' => 'Sélectionnez une intention...',
        'no_category' => 'Aucune catégorie disponible',
        'change_theme' => 'Changer le thème',
        'verse' => 'Verset',
        'surah' => 'Sourate',
        'play' => 'Écouter',
        'pause' => 'Pause',
        'next_verse' => 'Verset suivant',
        'previous_verse' => 'Verset précédent',
        'share' => 'Partager',
        'share_verse' => 'Partager le verset',
        'copy' => 'Copier',
        'copied' => 'Copié !',
        'quran' => 'Le Saint Coran',
        'read_quran' => 'Lire le Coran',
        'decrease_font' => 'Réduire la police',
        'increase_font' => 'Augmenter la police',
        'prev_surah' => 'Sourate précédente',
        'next_surah' => 'Sourate suivante',
        'prev_page' => 'Page précédente',
        'next_page' => 'Page suivante',
        'previous' => 'Précédent',
        'next' => 'Suivant',
        'meccan' => 'Mecquoise',
        'medinan' => 'Médinoise',
        'verses' => 'versets',
    ],

    // JavaScript translations (for frontend)
    'js' => [
        'select_intention' => 'Sélectionnez une intention...',
        'no_category' => 'Aucune catégorie disponible',
        'loading' => 'Chargement...',
        'error' => 'Une erreur est survenue',
        'copied' => 'Copié !',
        'play' => 'Écouter',
        'pause' => 'Pause',
        'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer ?',
        'meccan' => 'Mecquoise',
        'medinan' => 'Médinoise',
        'verses' => 'versets',
        'share_format' => 'Format',
        'share_theme' => 'Thème',
        'theme_dark' => 'Sombre',
        'theme_light' => 'Clair',
        'story' => 'Story',
        'square' => 'Carré',
        'show_arabic' => 'Afficher le texte arabe',
    ],
];
