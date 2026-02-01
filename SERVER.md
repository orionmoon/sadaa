# Guide de Démarrage du Serveur - Sadaa

## Serveur de Développement (local)

Le serveur PHP intégré ne lit pas les fichiers `.htaccess`. Utilisez le routeur PHP inclus :

**Commande depuis la racine du projet :**
```bash
php -S localhost:8001 -t public router.php
```

Le serveur sera accessible sur : **http://localhost:8001**

**Important** : Cette commande démarre le serveur avec `public/` comme document root, mais utilise le routeur à la racine pour gérer les URLs admin.

## Serveur de Production (Apache)

En production avec Apache, les règles `.htaccess` fonctionnent automatiquement.
Aucun routeur PHP n'est nécessaire.

**Configuration Apache requise** :
```apache
DocumentRoot /var/www/sadaa/public
<Directory /var/www/sadaa/public>
    AllowOverride All
    Require all granted
</Directory>

# Alias pour l'admin (en dehors de public/)
Alias /admin /var/www/sadaa/admin
<Directory /var/www/sadaa/admin>
    AllowOverride All
    Require all granted
</Directory>
```

Assurez-vous que `mod_rewrite` est activé :
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## URLs Disponibles

### Pages Publiques

- **Homepage** : `http://localhost:8001/` ou `http://localhost:8001/index`
- **Catégories** :
  - `http://localhost:8001/category/croyants`
  - `http://localhost:8001/category/mecreants`
  - `http://localhost:8001/category/mankind`
  - `http://localhost:8001/category/history`
  - `http://localhost:8001/category/Israelites`
- **Sitemap** : `http://localhost:8001/sitemap.xml`
- **Robots** : `http://localhost:8001/robots.txt`

### Pages d'Administration

- **Dashboard** : `http://localhost:8001/admin/`
- **Paramètres** : `http://localhost:8001/admin/settings.php`
- **Catégories** : `http://localhost:8001/admin/categories.php`
- **Types** : `http://localhost:8001/admin/types.php`

### Backward Compatibility

Les anciennes URLs redirigent automatiquement (301) :
- `http://localhost:8001/surah.php?category=3` → `/category/croyants`

## Structure des URLs

| Type | Ancienne URL | Nouvelle URL |
|------|-------------|--------------|
| Homepage | `/` ou `/index.php` | `/` ou `/index` |
| Catégorie | `/surah.php?category=5` | `/category/mankind` |
| Sitemap | `/sitemap.xml.php` | `/sitemap.xml` |
| Admin | `/admin/` | `/admin/` |

## Fichiers Clés

- **Routeur Dev** : `router.php` (racine) - Routeur pour le serveur PHP intégré, gère /public et /admin
- **Routeur Public** : `public/router.php` - Version simplifiée (legacy, non utilisée)
- **Production** : `public/.htaccess` - Règles de réécriture Apache
- **Sitemap** : `public/sitemap.xml.php` - Générateur de sitemap
- **SEO** : `public/robots.txt` - Directives pour les crawlers

## Vérification Rapide

Testez que tout fonctionne :

```bash
# Pages publiques
curl -I http://localhost:8001/
curl -I http://localhost:8001/category/mankind
curl -I http://localhost:8001/sitemap.xml
curl -I http://localhost:8001/robots.txt

# Pages admin
curl -I http://localhost:8001/admin/
curl -I http://localhost:8001/admin/settings.php

# Ressources statiques
curl -I http://localhost:8001/css/style.css
curl -I http://localhost:8001/js/app.js

# Backward compatibility (doit retourner 301 Location: /category/...)
curl -I 'http://localhost:8001/surah.php?category=3'
```

**Résultats attendus** :
- Pages publiques : `HTTP/1.1 200 OK`
- Pages admin : `HTTP/1.1 200 OK`
- Ressources statiques : `HTTP/1.1 200 OK`
- Backward compatibility : `HTTP/1.1 301 Moved Permanently`

## Structure du Projet

```
sadaa/
├── admin/              # Interface d'administration (en dehors de public)
│   ├── index.php
│   ├── settings.php
│   └── ...
├── config/             # Configuration et base de données
│   ├── db.php
│   └── i18n.php
├── public/             # Document root public
│   ├── index.php       # Homepage
│   ├── surah.php       # Pages catégories
│   ├── sitemap.xml.php # Générateur de sitemap
│   ├── robots.txt      # Directives SEO
│   ├── .htaccess       # Règles Apache (production)
│   ├── css/
│   ├── js/
│   └── assets/
├── router.php          # Routeur de développement (racine)
└── SERVER.md           # Ce fichier

```

## Dépannage

### Le CSS/JS ne charge pas
- Vérifiez que vous avez bien ajouté `-t public` dans la commande
- Commande correcte : `php -S localhost:8001 -t public router.php`

### Les pages admin donnent 404
- Le routeur doit être à la racine du projet (pas dans `public/`)
- Redémarrez le serveur depuis la racine avec la commande complète

### Les pretty URLs ne fonctionnent pas
- En développement : assurez-vous d'utiliser `router.php`
- En production : vérifiez que `mod_rewrite` est activé et `.htaccess` est lu
