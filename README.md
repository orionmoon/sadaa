# صدى | Sadaa

Écho de sagesse pour l'âme - Application web de gestion et d'affichage des versets du Coran.

## Fonctionnalités

- 📖 **Gestion du Coran** : Import automatique via AlQuran.cloud API
- 🌐 **Multilingue** : Support de l'arabe, français, anglais et plus
- 🏷️ **Catégorisation** : Classez les versets par types et catégories
- 🎨 **Thèmes** : Mode clair/sombre avec bascule automatique
- 👤 **Administration** : Panel complet de gestion

## Installation

### Prérequis
- PHP 8.0+
- MySQL 5.7+
- Serveur web (Apache/Nginx)

### Étapes

1. **Cloner ou copier le projet** dans votre répertoire :
   ```bash
   cd /Users/abdelaziz/Creations/Code/Projets
   # Le projet est déjà dans le dossier 'sadaa'
   cd sadaa
   ```

2. **Créer la base de données MySQL** (exécuter dans le terminal) :
   ```bash
   # Avec mot de passe (vous serez invité à le saisir)
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS sadaa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   
   # OU sans mot de passe (si MySQL n'a pas de mot de passe root)
   mysql -u root -e "CREATE DATABASE IF NOT EXISTS sadaa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   ```

3. **Importer le schéma de base de données** :
   ```bash
   # Avec mot de passe
   mysql -u root -p sadaa < config/database_schema.sql
   
   # OU sans mot de passe
   mysql -u root sadaa < config/database_schema.sql
   ```

4. **Configurer la connexion** dans `config/db.php` :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'sadaa');
   define('DB_USER', 'root');        // Votre utilisateur MySQL
   define('DB_PASS', '');            // Votre mot de passe MySQL (vide si aucun)
   ```

5. **Démarrer le serveur de développement PHP** :
   ```bash
   cd public
   php -S localhost:8000
   ```

6. **Accéder à l'application** :
   - 🌐 Site public : http://localhost:8000/
   - 🔐 Admin : http://localhost:8000/../admin/ (mot de passe : `admin123`)

7. **Importer le Coran** (dans l'admin) :
   - Allez sur "Import Coran" dans le menu
   - Sélectionnez les langues (Arabe, Français, Anglais...)
   - Cliquez sur "Démarrer l'import"

## Structure du projet

```
sadaa/
├── admin/              # Panel d'administration
│   ├── layout.php      # Layout avec sidebar
│   ├── index.php       # Dashboard
│   ├── types.php       # Gestion des types
│   ├── categories.php  # Gestion des catégories
│   ├── assignments.php # Assignation des versets
│   ├── books.php       # Gestion des livres
│   ├── import.php      # Import du Coran
│   ├── imports.php     # Historique des imports
│   └── settings.php    # Paramètres
├── app/                # Classes PHP
│   └── QuranApi.php    # Client API AlQuran.cloud
├── config/             # Configuration
│   ├── db.php          # Connexion base de données
│   └── database_schema.sql
├── public/             # Interface publique
│   ├── css/
│   │   └── style.css   # Styles principaux
│   ├── index.php       # Page d'accueil
│   ├── surah.php       # Affichage des sourates
│   └── api.php         # API REST
└── README.md
```

## Utilisation

### Import du Coran

1. Connectez-vous à l'admin
2. Allez sur "Import Coran"
3. Sélectionnez les langues souhaitées
4. Cliquez sur "Démarrer l'import"

### Catégorisation

1. Créez des **Types** (ex: État d'esprit, Sciences)
2. Créez des **Catégories** liées aux types
3. Dans **Assignations**, sélectionnez les versets à catégoriser

### API Endpoints

| Endpoint | Description |
|----------|-------------|
| `?action=get_types` | Liste des types |
| `?action=get_categories&type_id=X` | Catégories par type |
| `?action=get_surahs` | Liste des sourates |
| `?action=get_ayahs&surah_number=X` | Versets d'une sourate |
| `?action=get_languages` | Langues actives |

## Sécurité

⚠️ **Important pour la production** :
- Changez le mot de passe admin dans `admin/layout.php`
- Configurez HTTPS
- Restreignez les permissions des fichiers
- Désactivez l'affichage des erreurs PHP

## Licence

MIT

---

صدى - Écho de sagesse pour l'âme ✨
