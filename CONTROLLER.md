# CONTROLLER.md — Les Délices de Chloé V2

Description de chaque controller, ses routes et ses responsabilités.

---

## Controllers publics (`src/Controller/`)

---

### `PublicController`

**Rôle** : Gère toutes les pages visibles par les visiteurs non connectés.

| Route                    | Méthode | Nom de route           | Description                                                                 |
|--------------------------|---------|------------------------|-----------------------------------------------------------------------------|
| `/`                      | GET     | `app_home`             | Page d'accueil : hero + présentation + liens vers catégories                |
| `/categorie/{slug}`      | GET     | `app_category`         | Affiche les produits d'une catégorie, filtrables par sous-catégorie. Seuls les produits `isVisible = true` sont affichés. |
| `/a-propos`              | GET     | `app_about`            | Affiche le contenu de l'entité `AboutContent` (singleton)                   |

**Templates associés** :
- `templates/public/home.html.twig`
- `templates/public/category.html.twig`
- `templates/public/about.html.twig`

**Notes** :
- Injecte le repository `CategoryRepository` pour construire la navigation dynamique (via `base.html.twig`).
- La page catégorie filtre les sous-catégories disponibles et les passe au template pour construire les onglets de filtrage côté JS.

---

### `ContactController`

**Rôle** : Gère le formulaire de contact et l'envoi d'email.

| Route      | Méthode  | Nom de route    | Description                                               |
|------------|----------|-----------------|-----------------------------------------------------------|
| `/contact` | GET/POST | `app_contact`   | Affiche le formulaire. En POST : valide, envoie l'email, redirige avec confirmation. |

**Formulaire** (`ContactType`) : champs `name`, `email`, `message`.

**Comportement** :
1. Validation Symfony (champs requis, format email).
2. Envoi via `MailerInterface` vers l'adresse configurée dans `services.yaml` (`app.contact_email`).
3. Aucun stockage en base de données.
4. Redirection vers la même page avec un message flash `success` après envoi.
5. Protection CSRF activée sur le formulaire.

**Templates associés** :
- `templates/public/contact.html.twig`

---

### `SecurityController`

**Rôle** : Gère l'authentification de l'espace admin.

| Route           | Méthode  | Nom de route    | Description                                    |
|-----------------|----------|-----------------|------------------------------------------------|
| `/admin/login`  | GET/POST | `app_login`     | Affiche et traite le formulaire de login admin |
| `/admin/logout` | GET      | `app_logout`    | Déconnecte l'utilisateur (géré par Symfony Security) |

**Notes** :
- Utilise `AuthenticationUtils` de Symfony pour récupérer les erreurs et le dernier identifiant saisi.
- La route `/admin/logout` est interceptée par le firewall Symfony (pas de logique dans le controller).
- Le template de login est volontairement sobre et 100% responsive.
- Un lien "Mot de passe oublié ?" est présent sur la page de login et pointe vers `/admin/mot-de-passe-oublie`.

**Templates associés** :
- `templates/security/login.html.twig`

---

### `Admin\ResetPasswordController`

**Rôle** : Gère le flux complet de réinitialisation du mot de passe par email.

| Route                                  | Méthode  | Nom de route                  | Description                                                                 |
|----------------------------------------|----------|-------------------------------|-----------------------------------------------------------------------------|
| `/admin/mot-de-passe-oublie`           | GET/POST | `app_forgot_password_request` | Formulaire de saisie de l'email. Envoie un lien de réinitialisation si l'email correspond à un compte existant. |
| `/admin/reinitialiser/{token}`         | GET/POST | `app_reset_password`          | Vérifie le token, affiche le formulaire de nouveau mot de passe, met à jour et redirige vers le login. |

**Entité de support** : `ResetPasswordToken` (id, user, token, expiresAt)

**Comportement détaillé** :

1. **Demande de réinitialisation** (`/admin/mot-de-passe-oublie`) :
   - L'utilisateur saisit son adresse email.
   - Si l'email existe en base : génération d'un token aléatoire sécurisé (`random_bytes`), stocké en base avec une expiration de **1 heure**.
   - Envoi d'un email contenant le lien `https://domaine/admin/reinitialiser/{token}`.
   - **Dans tous les cas**, affichage du même message de confirmation générique (ne pas révéler si l'email existe ou non — bonne pratique sécurité).

2. **Réinitialisation** (`/admin/reinitialiser/{token}`) :
   - Vérification que le token existe en base et n'est pas expiré.
   - Si invalide ou expiré : message d'erreur flash + redirection vers `/admin/mot-de-passe-oublie`.
   - Si valide : formulaire de saisie du nouveau mot de passe (avec confirmation).
   - En POST : validation, hashage bcrypt, mise à jour de l'utilisateur, suppression du token, redirection vers `/admin/login` avec message flash `success`.

**Règles de sécurité** :
- Le token est un secret à usage unique supprimé après utilisation.
- Expiration à 1 heure.
- Le message de confirmation de la demande est identique que l'email existe ou non.
- Protection CSRF sur les deux formulaires.

**Templates associés** :
- `templates/security/forgot_password.html.twig`
- `templates/security/reset_password.html.twig`
- `templates/emails/reset_password.html.twig` (corps de l'email)

---

## Controllers d'administration (`src/Controller/Admin/`)

> Tous ces controllers sont protégés par `#[IsGranted('ROLE_ADMIN')]`. Tout accès non authentifié redirige vers `/admin/login`.

---

### `Admin\DashboardController`

**Rôle** : Point d'entrée de l'espace admin. Donne une vue d'ensemble du contenu.

| Route    | Méthode | Nom de route      | Description                                         |
|----------|---------|-------------------|-----------------------------------------------------|
| `/admin` | GET     | `admin_dashboard` | Tableau de bord : nombre de produits, catégories, produits masqués, raccourcis d'actions. |

**Templates associés** :
- `templates/admin/dashboard.html.twig`

---

### `Admin\CategoryController`

**Rôle** : CRUD complet des catégories.

| Route                          | Méthode  | Nom de route             | Description                                      |
|--------------------------------|----------|--------------------------|--------------------------------------------------|
| `/admin/categories`            | GET      | `admin_category_index`   | Liste toutes les catégories avec leur statut     |
| `/admin/categories/new`        | GET/POST | `admin_category_new`     | Formulaire de création d'une catégorie           |
| `/admin/categories/{id}/edit`  | GET/POST | `admin_category_edit`    | Formulaire d'édition d'une catégorie             |
| `/admin/categories/{id}/toggle`| POST     | `admin_category_toggle`  | Bascule `isVisible` sans quitter la liste        |
| `/admin/categories/{id}/delete`| POST     | `admin_category_delete`  | Supprime une catégorie (uniquement si vide de produits et sous-catégories) |

**Règles métier** :
- Une catégorie ne peut pas être supprimée si elle contient des produits ou des sous-catégories.
- La position d'affichage peut être modifiée depuis la liste.

**Templates associés** :
- `templates/admin/category/index.html.twig`
- `templates/admin/category/form.html.twig`

---

### `Admin\SubCategoryController`

**Rôle** : CRUD complet des sous-catégories.

| Route                              | Méthode  | Nom de route                | Description                                          |
|------------------------------------|----------|-----------------------------|------------------------------------------------------|
| `/admin/sous-categories`           | GET      | `admin_subcategory_index`   | Liste toutes les sous-catégories (filtrables par catégorie) |
| `/admin/sous-categories/new`       | GET/POST | `admin_subcategory_new`     | Formulaire de création                               |
| `/admin/sous-categories/{id}/edit` | GET/POST | `admin_subcategory_edit`    | Formulaire d'édition                                 |
| `/admin/sous-categories/{id}/delete`| POST    | `admin_subcategory_delete`  | Supprime une sous-catégorie (uniquement si vide de produits) |

**Règles métier** :
- Une sous-catégorie est obligatoirement rattachée à une catégorie.
- Impossible de supprimer une sous-catégorie qui contient des produits.

**Templates associés** :
- `templates/admin/subcategory/index.html.twig`
- `templates/admin/subcategory/form.html.twig`

---

### `Admin\ProductController`

**Rôle** : CRUD complet des produits, incluant la gestion de l'upload photo.

| Route                           | Méthode  | Nom de route            | Description                                         |
|---------------------------------|----------|-------------------------|-----------------------------------------------------|
| `/admin/produits`               | GET      | `admin_product_index`   | Liste paginée des produits, filtrable par catégorie et statut |
| `/admin/produits/new`           | GET/POST | `admin_product_new`     | Formulaire d'ajout avec upload de photo             |
| `/admin/produits/{id}/edit`     | GET/POST | `admin_product_edit`    | Formulaire d'édition, remplacement de photo optionnel |
| `/admin/produits/{id}/toggle`   | POST     | `admin_product_toggle`  | Bascule `isVisible` directement depuis la liste     |
| `/admin/produits/{id}/delete`   | POST     | `admin_product_delete`  | Supprime le produit et son fichier image associé    |

**Règles métier** :
- La photo est obligatoire à la création.
- L'édition permet de garder l'ancienne photo si aucune nouvelle n'est fournie.
- Suppression du fichier physique sur le disque lors de la suppression du produit (géré par VichUploader).
- La sous-catégorie est optionnelle et doit appartenir à la même catégorie que le produit.

**Templates associés** :
- `templates/admin/product/index.html.twig`
- `templates/admin/product/form.html.twig`

---

### `Admin\AboutController`

**Rôle** : Édition du contenu de la page "À propos" (singleton).

| Route               | Méthode  | Nom de route     | Description                                                       |
|---------------------|----------|------------------|-------------------------------------------------------------------|
| `/admin/a-propos`   | GET/POST | `admin_about`    | Charge l'unique entrée `AboutContent` (ou la crée si inexistante) et affiche le formulaire d'édition |

**Règles métier** :
- Il existe toujours exactement un seul enregistrement `AboutContent` (id = 1).
- Si aucun enregistrement n'existe lors du premier accès, il est créé automatiquement.

**Templates associés** :
- `templates/admin/about/edit.html.twig`

---

## Commandes CLI (`src/Command/`)

### `CreateUserCommand` (`app:create-user`)

**Usage** : `php bin/console app:create-user`

Permet de créer un utilisateur admin en ligne de commande (questions interactives : username, email, mot de passe). Utilisé pour initialiser les 2 comptes sans passer par une interface d'inscription publique.
