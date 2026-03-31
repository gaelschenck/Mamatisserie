# Les Délices de Chloé — V2

Site vitrine pour une pâtissière, permettant de présenter ses créations par catégories et d'administrer le contenu de manière autonome.

---

## Stack technique

| Élément       | Technologie                          |
|---------------|--------------------------------------|
| Backend       | PHP 8.2+ / Symfony 7.x               |
| Base de données | SQLite 3 (via Doctrine ORM)        |
| Front         | Twig + Tailwind CSS v3               |
| Auth          | Symfony Security (session)           |
| Mail          | Symfony Mailer (SMTP)                |
| Upload images | VichUploaderBundle                   |
| Hébergement   | Freebox (serveur local)              |

---

## Architecture du projet

```
Nouveau projet/
├── src/
│   ├── Controller/
│   │   ├── PublicController.php         # Pages publiques
│   │   ├── ContactController.php        # Formulaire de contact
│   │   ├── SecurityController.php       # Login / Logout
│   │   └── Admin/
│   │       ├── DashboardController.php  # Tableau de bord admin
│   │       ├── ProductController.php    # CRUD produits
│   │       ├── CategoryController.php   # CRUD catégories
│   │       ├── SubCategoryController.php# CRUD sous-catégories
│   │       └── AboutController.php      # Édition page "À propos"
│   ├── Entity/
│   │   ├── Category.php
│   │   ├── SubCategory.php
│   │   ├── Product.php
│   │   ├── User.php
│   │   └── AboutContent.php
│   └── Form/
│       ├── ProductType.php
│       ├── CategoryType.php
│       ├── SubCategoryType.php
│       ├── ContactType.php
│       └── AboutContentType.php
├── templates/
│   ├── base.html.twig
│   ├── public/
│   └── admin/
├── public/
│   ├── uploads/          # Photos uploadées
│   └── robots.txt        # /admin non indexé
├── var/
│   └── data.db           # Base SQLite
└── .env.local            # Configuration locale (SMTP, etc.)
```

---

## Modèle de données

### `Category`
| Champ      | Type    | Description                          |
|------------|---------|--------------------------------------|
| id         | int     | Clé primaire                         |
| name       | string  | Nom de la catégorie (ex: Pâtisserie) |
| slug       | string  | URL-friendly (ex: patisserie)        |
| position   | int     | Ordre d'affichage dans le menu       |
| isVisible  | bool    | Affichée sur le site public ou non   |

### `SubCategory`
| Champ      | Type       | Description                          |
|------------|------------|--------------------------------------|
| id         | int        | Clé primaire                         |
| name       | string     | Nom de la sous-catégorie             |
| slug       | string     | URL-friendly                         |
| category   | ManyToOne  | Catégorie parente                    |
| position   | int        | Ordre d'affichage                    |
| isVisible  | bool       | Affichée ou non                      |

### `Product`
| Champ        | Type       | Description                                              |
|--------------|------------|----------------------------------------------------------|
| id           | int        | Clé primaire                                             |
| name         | string     | Nom du produit                                           |
| description  | text       | Description courte                                       |
| photo        | string     | Nom du fichier image (uploadé, redimensionné auto.)      |
| category     | ManyToOne  | Catégorie                                                |
| subCategory  | ManyToOne  | Sous-catégorie (nullable)                                |
| isVisible    | bool       | Visible sur le site ou masqué                            |
| isFeatured   | bool       | Mis en avant / "Coup de cœur" sur la page d'accueil      |
| displayOrder | int        | Ordre d'affichage dans sa catégorie (défini en admin)    |
| viewCount    | int        | Nombre de fois que la fiche a été ouverte (compteur)     |
| createdAt    | datetime   | Date de création                                         |
| updatedAt    | datetime   | Dernière modification                                    |

### `User`
| Champ    | Type   | Description                                  |
|----------|--------|----------------------------------------------|
| id       | int    | Clé primaire                                 |
| username | string | Identifiant de connexion                     |
| email    | string | Adresse email                                |
| password | string | Mot de passe hashé (bcrypt)                  |
| roles    | json   | `["ROLE_ADMIN"]`                             |

### `AboutContent`
| Champ     | Type     | Description                               |
|-----------|----------|-------------------------------------------|
| id        | int      | Clé primaire (toujours 1, singleton)      |
| title     | string   | Titre de la section                       |
| text      | text     | Texte de présentation (format libre)      |
| photo     | string   | Photo de profil                           |
| updatedAt | datetime | Dernière modification                     |

### `ResetPasswordToken`
| Champ     | Type      | Description                                        |
|-----------|-----------|----------------------------------------------------|
| id        | int       | Clé primaire                                       |
| user      | ManyToOne | Utilisateur concerné                               |
| token     | string    | Token aléatoire sécurisé (unique)                  |
| expiresAt | datetime  | Date d'expiration (1 heure après génération)       |

---

## Installation

### Prérequis
- PHP 8.2+
- Composer
- Node.js + npm (pour Tailwind)
- Extension PHP : `pdo_sqlite`, `intl`, `mbstring`

### Étapes

```bash
# 1. Cloner / déposer le projet
cd /chemin/vers/projet

# 2. Installer les dépendances PHP
composer install

# 3. Configurer l'environnement
cp .env .env.local
# Éditer .env.local : MAILER_DSN, APP_SECRET

# 4. Créer la base de données SQLite et les tables
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Créer les deux comptes admin
php bin/console app:create-user

# 6. Installer et compiler Tailwind
npm install
npm run build

# 7. Lancer le serveur (dev)
symfony serve
```

### Configuration SMTP (Freebox)
Dans `.env.local`, configurer la variable `MAILER_DSN` avec le serveur SMTP disponible :
```
MAILER_DSN=smtp://utilisateur:motdepasse@smtp.fai.fr:587
```

---

## Accès à l'interface d'administration

L'URL `/admin` n'est **pas liée depuis le site public** et est exclue de l'indexation via `robots.txt`.

Pour y accéder : saisir manuellement `https://votre-domaine/admin` dans le navigateur.

> Le site est conçu **mobile-first**. L'interface d'administration est utilisable depuis un smartphone.

---

## Règles métier

- **Catégories** : dynamiques, créables/modifiables/masquables depuis l'admin. Non limitées à pâtisserie/boulangerie/chocolaterie.
- **Sous-catégories** : rattachées à une catégorie. Optionnelles sur un produit.
- **Produits** : une seule photo (redimensionnée automatiquement), statut visible/masqué, flag "coup de cœur", ordre d'affichage configurable, compteur de vues.
- **Coups de cœur** : les produits `isFeatured = true` sont mis en avant sur la page d'accueil. Plusieurs produits peuvent être mis en avant simultanément.
- **Compteur de vues** : incrémenté côté JS à chaque ouverture de la modale, visible uniquement en admin.
- **Contact** : le formulaire envoie directement un email à l'adresse configurée. Aucun stockage en base. Peut être pré-rempli avec le nom d'un produit depuis sa fiche.
- **Mentions légales** : page statique obligatoire (RGPD), lien dans le footer.
- **Admin** : 2 utilisateurs maximum (`ROLE_ADMIN`). Accès `/admin` uniquement après login.
- **À propos** : contenu singleton, éditable depuis l'admin.
- **robots.txt** : `/admin` est déclaré en `Disallow` pour ne pas apparaître dans les moteurs de recherche.

## Système de palettes (thèmes)

Le visiteur peut choisir parmi 4 palettes de couleurs, persistées dans `localStorage`. Aucun cookie, aucune donnée serveur.

| Palette       | Ambiance                     | Couleurs principales                          |
|---------------|------------------------------|-----------------------------------------------|
| **Lagune**    | Défaut — fraîche, moderne    | Cyan `#00B4D8`, turquoise `#0077B6`, blanc nacré |
| **Boulangerie** | Chaude, artisanale          | Or `#C8872A`, ambre `#8B5E3C`, crème `#FDF3E3` |
| **Chocolat**  | Profonde, élégante           | Brun `#3E1C00`, cacao `#6B3A2A`, ivoire `#FFF8F0` |
| **Gourmande** | Douce, colorée, jeune        | Pistache `#8DB87B`, framboise `#C0345A`, blanc rosé |

Implémentation : variable `data-theme` sur `<html>`, CSS custom properties, switcher 4 pastilles dans le header. L'interface admin utilise un thème sobre indépendant.
