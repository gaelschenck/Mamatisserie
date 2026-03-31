# TODO — Les Délices de Chloé V2

Ligne directrice du projet. Cocher les tâches au fur et à mesure.

---

## Phase 1 — Initialisation du projet

- [ ] Créer le projet Symfony (`symfony new delices-chloe --version="7.*"`)
- [ ] Configurer `.env.local` (APP_SECRET, MAILER_DSN)
- [ ] Configurer Doctrine pour SQLite (`DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db`)
- [ ] Installer les bundles nécessaires :
  - [ ] `doctrine/orm`
  - [ ] `symfony/security-bundle`
  - [ ] `symfony/mailer`
  - [ ] `symfony/form`
  - [ ] `symfony/validator`
  - [ ] `vich/uploader-bundle`
  - [ ] `knplabs/knp-paginator-bundle` (pagination admin)
- [ ] Installer et configurer Tailwind CSS (via `npm`)
- [ ] Mettre en place la structure de dossiers `templates/public/` et `templates/admin/`
- [ ] Créer `public/robots.txt` avec `Disallow: /admin`

---

## Phase 2 — Modèle de données (Entités & Migrations)

- [ ] Créer l'entité `Category` (id, name, slug, position, isVisible)
- [ ] Créer l'entité `SubCategory` (id, name, slug, category, position, isVisible)
- [ ] Créer l'entité `Product` (id, name, description, photo, category, subCategory, isVisible, isFeatured, viewCount, displayOrder, createdAt, updatedAt)
- [ ] Créer l'entité `User` (id, username, email, password, roles)
- [ ] Créer l'entité `AboutContent` (id, title, text, photo, updatedAt)
- [ ] Générer et exécuter les migrations Doctrine
- [ ] Configurer VichUploaderBundle pour l'upload de photos produits avec redimensionnement automatique
- [ ] Créer la commande `app:create-user` pour initialiser les 2 comptes admin en CLI

---

## Phase 3 — Sécurité & Authentification

- [ ] Configurer `security.yaml` (provider, firewall `/admin`, encoder bcrypt)
- [ ] Créer `SecurityController` (routes `/admin/login`, `/admin/logout`)
- [ ] Créer le template `templates/security/login.html.twig` avec lien "Mot de passe oublié ?" (responsive mobile)
- [ ] Vérifier que `/admin/*` redirige vers le login si non authentifié
- [ ] Vérifier que le login fonctionne depuis mobile
- [ ] Créer l'entité `ResetPasswordToken` (id, user, token, expiresAt)
- [ ] Générer la migration pour `ResetPasswordToken`
- [ ] Créer `ResetPasswordController` :
  - [ ] Route GET/POST `/admin/mot-de-passe-oublie` (demande par email)
  - [ ] Route GET/POST `/admin/reinitialiser/{token}` (saisie nouveau mot de passe)
- [ ] Créer le template `templates/security/forgot_password.html.twig`
- [ ] Créer le template `templates/security/reset_password.html.twig`
- [ ] Créer le template email `templates/emails/reset_password.html.twig`
- [ ] Tester le flux complet (demande → email → lien → changement → login)

---

## Phase 4 — Interface publique (Front)

- [ ] Créer le layout `base.html.twig` (header, nav, footer)
- [ ] Mettre en place la navigation par catégories (dynamique depuis BDD)
- [ ] Intégrer le sélecteur de palette dans le header (icône discrète)
- [ ] **Page d'accueil** : hero visuel + section "Coups de cœur" (produits `isFeatured = true`), présentation rapide, lien vers catégories
- [ ] **Page catégorie** (`/categorie/{slug}`) :
  - [ ] Grille de cartes produits style Instagram / Pinterest
  - [ ] Filtrage par sous-catégorie (via onglets ou menu)
  - [ ] Respect de l'ordre `displayOrder` défini en admin
  - [ ] Ne montrer que les produits `isVisible = true`
  - [ ] Incrémenter `viewCount` à chaque ouverture de la fiche (appel JS discret)
- [ ] **Modale / Lightbox** au clic sur une carte (nom, description, photo agrandie, lien "Me contacter pour ce produit")
- [ ] **Page À propos** (`/a-propos`) : affichage du contenu `AboutContent`
- [ ] **Page Contact** (`/contact`) : formulaire nom/email/message (pré-rempli si venant d'une fiche produit), envoi par email
- [ ] **Page Mentions légales** (`/mentions-legales`) : page statique (RGPD obligatoire)
- [ ] Confirmation visuelle après envoi du formulaire de contact
- [ ] S'assurer que toutes les pages sont responsive (mobile-first)

---

## Phase 5 — Interface d'administration (Back)

- [ ] **Dashboard** (`/admin`) : résumé (nb produits, nb catégories, raccourcis, top 5 produits les plus vus)
- [ ] **Gestion des catégories** :
  - [ ] Liste avec statut visible/masqué et ordre
  - [ ] Formulaire d'ajout
  - [ ] Formulaire d'édition
  - [ ] Suppression (avec vérification : catégorie vide uniquement)
  - [ ] Réorganisation de l'ordre d'affichage
- [ ] **Gestion des sous-catégories** :
  - [ ] Liste filtrée par catégorie
  - [ ] Formulaire d'ajout / édition / suppression
- [ ] **Gestion des produits** :
  - [ ] Liste paginée avec filtres (catégorie, statut) + colonne vues
  - [ ] Formulaire d'ajout avec upload photo
  - [ ] Formulaire d'édition (remplacement photo possible)
  - [ ] Toggle visible/masqué rapide (sans quitter la liste)
  - [ ] Toggle coup de cœur / mis en avant (sans quitter la liste)
  - [ ] Réorganisation de l'ordre d'affichage (`displayOrder`)
  - [ ] Suppression avec confirmation
- [ ] **Édition À propos** :
  - [ ] Formulaire d'édition du texte, titre et photo
- [ ] S'assurer que l'interface admin est utilisable depuis un smartphone

---

## Phase 6 — Design & UI

### Système de palettes (thèmes)
- [ ] Définir les 4 palettes en variables CSS (`data-theme` sur `<html>`)
  - [ ] **Lagune** (défaut) — cyan `#00B4D8`, turquoise `#0077B6`, blanc nacré, accents dorés clairs
  - [ ] **Boulangerie** — or chaud `#C8872A`, ambre `#8B5E3C`, crème `#FDF3E3`, croûte `#6B3D14`
  - [ ] **Chocolat** — brun profond `#3E1C00`, cacao `#6B3A2A`, ivoire `#FFF8F0`, or `#D4A843`
  - [ ] **Gourmande** — pistache `#8DB87B`, framboise `#C0345A`, blanc doux `#FFF5F7`, vert tendre `#C8E6C9`
- [ ] Implémenter le sélecteur de thème (4 pastilles colorées, header public)
- [ ] Persister le choix en `localStorage` (aucune donnée serveur)
- [ ] S'assurer que les 4 thèmes s'appliquent à toutes les pages (cartes, modale, nav, formulaires)
- [ ] Définir un thème sobre et neutre pour l'interface admin (indépendant des palettes)

### UI générale
- [ ] Mettre en place la typographie (Google Fonts — élégante pour les titres, neutre pour le corps)
- [ ] Styliser les cartes produits (ombre, hover, transition douce)
- [ ] Styliser la modale/lightbox (photo plein cadre, fermeture au clic extérieur)
- [ ] Styliser la section "Coups de cœur" en page d'accueil (mise en avant visuelle)
- [ ] Animations légères sur les transitions de page / apparition des cartes
- [ ] Styliser le formulaire de contact
- [ ] Styliser la page À propos
- [ ] Styliser la page Mentions légales
- [ ] Styliser l'interface admin (sobre, fonctionnel, mobile-friendly)
- [ ] Optimiser les images à l'upload (redimensionnement automatique, max 1200px)

---

## Phase 7 — Déploiement (Freebox)

- [ ] Vérifier la configuration PHP de la Freebox (version, extensions activées)
- [ ] Configurer le serveur web (Apache ou Nginx selon la Freebox)
- [ ] Pointer le document root vers `public/`
- [ ] Déposer les fichiers (FTP ou SSH)
- [ ] Vérifier les permissions sur `var/` et `public/uploads/`
- [ ] Exécuter les migrations en production
- [ ] Configurer le SMTP pour l'envoi d'emails
- [ ] Tester le formulaire de contact en production
- [ ] Tester l'accès admin depuis mobile en production
- [ ] Vérifier que `/admin` n'est pas indexé (robots.txt)

---

## Phase 8 — Finalisation

- [ ] Tests manuels complets (parcours visiteur, parcours admin)
- [ ] Vérification de la sécurité (CSRF sur tous les formulaires, accès admin protégé)
- [ ] Former Chloé à l'utilisation de l'admin
- [ ] Créer un guide utilisateur simplifié pour Chloé (gestion produits, catégories)
