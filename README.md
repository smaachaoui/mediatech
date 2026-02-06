# MediaTech

Application web de gestion de collections de livres et de films développée avec Symfony.

## Contexte

Ce projet a été réalisé dans le cadre de ma certification Développeur Web et Web Mobile (DWWM). MediaTech est une application complète permettant aux utilisateurs de créer et gérer leurs collections personnelles de livres et de films, tout en interagissant avec une communauté de passionnés.

## Fonctionnalités principales

### Pour les visiteurs
- Navigation libre sur le site
- Recherche de livres et films via APIs externes (Google Books, TMDB)
- Consultation des fiches détaillées des médias
- Visualisation des collections publiques
- Création de compte

### Pour les utilisateurs connectés
- Création et gestion de collections personnelles
- Ajout de livres et films aux collections
- Gestion de la visibilité des collections (publique/privée)
- Publication des collections
- Notation et commentaires sur les collections publiques
- Liste d'envie pour les médias à consulter plus tard
- Gestion du profil (photo, biographie, préférences)

### Pour les administrateurs
- Tableau de bord avec statistiques
- Gestion des utilisateurs (activation/désactivation)
- Modération des commentaires
- Gestion des collections
- Traitement des messages de contact

## Technologies utilisées

### Backend
- **Framework** : Symfony 6.4
- **Langage** : PHP 8.2+
- **ORM** : Doctrine
- **Base de données** : MySQL 8.0

### Frontend
- **Templates** : Twig
- **CSS** : Bootstrap 5.3
- **JavaScript** : ES6+ (modules)

### APIs externes
- **Google Books API** : Recherche et informations sur les livres
- **TMDB API** : Recherche et informations sur les films

### Sécurité
- Authentification avec hashage bcrypt
- Protection CSRF sur tous les formulaires
- Protection anti brute-force (limitation des tentatives de connexion)
- Rate limiting sur les actions sensibles
- Headers de sécurité HTTP
- Validation des données côté serveur

## Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL 8.0
- Symfony CLI (recommandé pour le développement)
- Node.js et npm (pour les assets front-end)

## Installation

### 1. Cloner le projet

```bash
git clone https://github.com/votre-username/mediatech.git
cd mediatech
```

### 2. Installer les dépendances

```bash
composer install
npm install
```

### 3. Configurer les variables d'environnement

Créer un fichier `.env.local` à la racine du projet :

```
DATABASE_URL="mysql://utilisateur:motdepasse@127.0.0.1:3306/mediatech"
GOOGLE_BOOKS_API_KEY="votre_clé_api_google_books"
TMDB_API_KEY="votre_clé_api_tmdb"
```

### 4. Créer la base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Charger les fixtures (optionnel)

```bash
php bin/console doctrine:fixtures:load
```

Cela créera :
- Un compte administrateur : `admin@mediatech.local` / `Admin1234!`
- Des utilisateurs de test
- Des collections et médias de démonstration

### 6. Lancer le serveur de développement

```bash
symfony server:start
```

L'application est accessible sur `http://localhost:8000`

## Structure du projet

```
mediatech/
├── config/              # Configuration Symfony
├── migrations/          # Migrations Doctrine
├── public/              # Point d'entrée web et assets
│   ├── assets/         
│   │   ├── css/        # Fichiers CSS
│   │   ├── js/         # Fichiers JavaScript
│   │   └── img/        # Images
│   └── uploads/        # Fichiers uploadés (photos de profil)
├── src/
│   ├── Controller/     # Contrôleurs
│   ├── Entity/         # Entités Doctrine
│   ├── Form/           # Formulaires
│   ├── Repository/     # Repositories
│   ├── Security/       # Sécurité (Voters, UserChecker)
│   └── Service/        # Services métier
├── templates/          # Templates Twig
└── var/               # Cache et logs
```

## Configuration des APIs

### Google Books API

1. Créer un projet sur [Google Cloud Console](https://console.cloud.google.com)
2. Activer l'API Google Books
3. Créer une clé API
4. Ajouter la clé dans `.env.local` : `GOOGLE_BOOKS_API_KEY="votre_clé"`

### TMDB API

1. Créer un compte sur [TMDB](https://www.themoviedb.org)
2. Générer une clé API dans les paramètres du compte
3. Ajouter la clé dans `.env.local` : `TMDB_API_KEY="votre_clé"`

## Utilisation

### Comptes de test

Après avoir chargé les fixtures, vous pouvez utiliser :

- **Administrateur** : `admin@mediatech.local` / `Admin1234!`
- **Utilisateur** : `alice@mediatech.local` / `Alice1234!`

### Fonctionnalités clés

#### Rechercher des médias

1. Accéder au menu "Catalogue"
2. Saisir un terme de recherche
3. Utiliser les filtres (livres/films/tous)
4. Cliquer sur un média pour voir sa fiche détaillée

#### Créer une collection

1. Se connecter à son compte
2. Accéder à "Mon profil"
3. Cliquer sur "Créer une collection"
4. Choisir le type (livres ou films)
5. Ajouter des médias depuis le catalogue

#### Publier une collection

1. Accéder à "Mes collections"
2. Cliquer sur "Publier" sur une collection contenant au moins un média
3. La collection apparaît dans "Nos Collections"

#### Noter et commenter

1. Accéder à une collection publique
2. Attribuer une note (1 à 5 étoiles)
3. Rédiger un commentaire

## Sécurité

### Protection implémentée

- **Authentification** : Hashage bcrypt, validation des mots de passe forts
- **Protection CSRF** : Tokens sur tous les formulaires
- **Anti brute-force** : Blocage temporaire après 5 tentatives échouées (15 minutes)
- **Rate limiting** : Limitation à 8 commentaires par minute
- **Validation** : Validation serveur sur toutes les données
- **Headers HTTP** : X-Content-Type-Options, X-Frame-Options, Referrer-Policy
- **Sessions** : Configuration sécurisée avec cookies HttpOnly et Secure

### Bonnes pratiques

- Toutes les requêtes Doctrine utilisent des requêtes préparées (protection SQL injection)
- Twig échappe automatiquement les variables (protection XSS)
- Les routes sensibles sont protégées par rôles (ROLE_USER, ROLE_ADMIN)
- Les Voters gèrent les permissions fines

## Documentation

La documentation complète du projet est disponible dans les fichiers suivants :

- **CAHIER_DES_CHARGES_MEDIATECH.md** : Spécifications fonctionnelles et techniques
- **USER_STORIES_MEDIATECH.md** : 47 user stories détaillées
- **PERSONAS_MEDIATECH.md** : Personas utilisateurs (Visiteur, Utilisateur, Admin)
- **DOCUMENTATION_TECHNIQUE_MEDIATECH.md** : Documentation technique complète

Tous ces fichiers sont également disponibles au format Word (.docx).

## Architecture

### Modèle de données

L'application utilise 11 entités principales :

- **User** : Utilisateurs
- **Book** / **Movie** : Médias (livres et films)
- **Collection** : Collections créées par les utilisateurs
- **CollectionBook** / **CollectionMovie** : Tables pivot enrichies
- **Rating** : Notes sur les collections
- **Comment** : Commentaires
- **Genre** : Catégories de médias
- **ContactMessage** : Messages du formulaire de contact
- **LoginAttempt** : Tentatives de connexion (sécurité)

### Services métier

- **GoogleBooksService** : Appels API Google Books avec cache
- **TmdbService** : Appels API TMDB
- **LibraryManager** : Gestion des collections système
- **ProfileService** : Logique métier du profil
- **LoginAttemptService** : Protection anti brute-force

### Sécurité avancée

- **UserChecker** : Vérification du statut des comptes
- **AdminVoter** / **CollectionVoter** : Permissions fines
- **LoginSubscriber** : Traçage des connexions
- **SecurityHeadersSubscriber** : Headers HTTP sécurisés

## Commandes utiles

### Développement

```bash
# Démarrer le serveur
symfony server:start

# Nettoyer le cache
php bin/console cache:clear

# Créer une migration
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate

# Charger les fixtures
php bin/console doctrine:fixtures:load
```

### Production

```bash
# Installation sans dépendances de dev
composer install --no-dev --optimize-autoloader

# Vider et préchauffer le cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Migrations
php bin/console doctrine:migrations:migrate --no-interaction
```

## Déploiement

### Apache

Exemple de configuration VirtualHost :

```apache
<VirtualHost *:80>
    ServerName mediatech.example.com
    DocumentRoot /var/www/mediatech/public

    <Directory /var/www/mediatech/public>
        AllowOverride All
        Require all granted
        FallbackResource /index.php
    </Directory>
</VirtualHost>
```

### Nginx

Exemple de configuration :

```nginx
server {
    listen 80;
    server_name mediatech.example.com;
    root /var/www/mediatech/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }
}
```

### HTTPS

Utiliser Let's Encrypt pour obtenir un certificat SSL :

```bash
sudo certbot --apache -d mediatech.example.com
# ou pour Nginx
sudo certbot --nginx -d mediatech.example.com
```

## Tests

### Validation du schéma

```bash
php bin/console doctrine:schema:validate
```

### Tests manuels

J'ai effectué des tests manuels complets sur tous les parcours utilisateur :
- Inscription et connexion
- Recherche de médias
- Création et gestion de collections
- Publication de collections
- Notation et commentaires
- Espace administrateur

## Améliorations futures

### Fonctionnalités
- Système social complet (amis, followers)
- Filtres avancés et recherche facettée
- Statistiques personnelles pour les utilisateurs
- Collections collaboratives
- Système de recommandations basé sur les préférences
- Export des collections (PDF, CSV)

### Technique
- Tests unitaires et fonctionnels automatisés
- API REST pour une application mobile
- Optimisation des performances (Redis, Varnish)
- Progressive Web App (PWA)
- Internationalisation (i18n)

### Sécurité
- Authentification à deux facteurs (2FA)
- Content Security Policy (CSP)
- Audit de sécurité complet

## Auteur

**Seifeddine MAACHAOUI** - Développeur web

Ce projet a été réalisé dans le cadre de la certification Développeur Web et Web Mobile (DWWM).

## Licence

Ce projet est développé dans un cadre éducatif. Tous droits réservés.

## Mentions obligatoires

### API Google Books

Ce projet utilise l'API Google Books pour récupérer les informations sur les livres.

### API TMDB

Ce produit utilise l'API TMDB mais n'est ni approuvé ni certifié par TMDB.

"This product uses the TMDB API but is not endorsed or certified by TMDB."

---

**Date de création** : Février 2026

**Version** : 1.0.0