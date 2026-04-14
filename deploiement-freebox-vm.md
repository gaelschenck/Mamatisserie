# Déploiement d'un projet Symfony sur VM Freebox

Guide complet pour héberger un projet Symfony sur une VM Freebox (Delta/Ultra) avec déploiement automatique via GitHub Actions.

---

## Prérequis

- Freebox Delta ou Ultra (module VMs disponible)
- Dépôt GitHub
- PHP 8.4, Symfony 7, SQLite

---

## 1. Générer la paire de clés SSH (sur le PC de développement)

Dans un terminal **PowerShell** :

```powershell
ssh-keygen -t ed25519 -C "nom-projet-deploy" -f "$env:USERPROFILE\.ssh\nom_projet_deploy"
```
Appuyer sur Entrée 2 fois (pas de passphrase).

Exporter les clés sur le bureau pour les avoir sous la main :
```powershell
Get-Content "$env:USERPROFILE\.ssh\nom_projet_deploy.pub" | Out-File "$env:USERPROFILE\Desktop\cle_publique.txt"
Get-Content "$env:USERPROFILE\.ssh\nom_projet_deploy" | Out-File "$env:USERPROFILE\Desktop\cle_privee.txt"
```
> **Supprimer ces fichiers du bureau une fois les clés configurées.**

---

## 2. Créer la VM sur Freebox OS

1. Aller sur **mafreebox.freebox.fr** → **VMs** → **Nouvelle VM**
2. Paramètres recommandés :
   - OS : **Debian 12**
   - CPU : 1 ou 2
   - RAM : 512 Mo minimum
   - Disque : 4 Go minimum
3. Remplir **obligatoirement** :
   - **Login** : `debian`
   - **Mot de passe** : un mot de passe de secours (accès console Freebox)
   - **Clé SSH publique** : coller le contenu de `cle_publique.txt` (une seule ligne commençant par `ssh-ed25519 AAAA...`)
4. Démarrer la VM et noter l'**IP locale** attribuée (ex. `192.168.0.77`)

> Si la clé SSH n'a pas été prise en compte au démarrage, se connecter via la console Freebox (bouton "Écran") avec le mot de passe, puis :
> ```bash
> mkdir -p ~/.ssh && chmod 700 ~/.ssh
> echo "ssh-ed25519 AAAA... nom-projet-deploy" >> ~/.ssh/authorized_keys
> chmod 600 ~/.ssh/authorized_keys
> ```

---

## 3. Vérifier la connexion SSH locale

Depuis PowerShell sur le PC de développement :

```powershell
ssh -i "$env:USERPROFILE\.ssh\nom_projet_deploy" debian@192.168.0.77
```

La connexion doit s'établir **sans demander de mot de passe**.

---

## 4. Installer PHP 8.4 et Apache sur la VM

Se connecter à la VM puis exécuter les commandes suivantes :

```bash
sudo apt update && sudo apt upgrade -y
```

Ajouter le dépôt sury.org pour PHP 8.4 :
```bash
sudo apt install -y curl
curl -sSLo /tmp/php.gpg https://packages.sury.org/php/apt.gpg
sudo mv /tmp/php.gpg /usr/share/keyrings/php.gpg
echo "deb [signed-by=/usr/share/keyrings/php.gpg] https://packages.sury.org/php/ bookworm main" | sudo tee /etc/apt/sources.list.d/php.list
sudo apt update
```

Installer PHP 8.4 + Apache + rsync :
```bash
sudo apt install -y apache2 php8.4 php8.4-cli php8.4-sqlite3 php8.4-mbstring php8.4-intl php8.4-curl php8.4-xml libapache2-mod-php8.4 rsync
```

Activer le module rewrite :
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Vérifier :
```bash
php -v          # doit afficher PHP 8.4.x
apache2 -v      # doit afficher Apache/2.4.x
```

---

## 5. Configurer Apache pour le projet

Créer le dossier du projet et lui donner les bons droits :
```bash
sudo mkdir -p /var/www/nom-projet
sudo chown debian:debian /var/www/nom-projet
```

Créer le VirtualHost :
```bash
sudo nano /etc/apache2/sites-available/nom-projet.conf
```

Contenu du fichier :
```apache
<VirtualHost *:80>
    DocumentRoot /var/www/nom-projet/public
    <Directory /var/www/nom-projet/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Activer le site :
```bash
sudo a2ensite nom-projet
sudo a2dissite 000-default
sudo systemctl reload apache2
```

> **Pour un deuxième site sur la même VM** : créer un deuxième VirtualHost sur un port différent (ex. 8080) ou avec un `ServerName` différent si un domaine est disponible. Voir section [Héberger plusieurs sites](#héberger-plusieurs-sites).

---

## 6. Créer le fichier .env.local sur la VM

```bash
nano /var/www/nom-projet/.env.local
```

Contenu minimal :
```dotenv
APP_ENV=prod
APP_SECRET=<chaine-aleatoire-32-caracteres>
DATABASE_URL="sqlite:///%kernel.project_dir%/var/app.db"
MAILER_DSN=null://null
MAILER_FROM_EMAIL=noreply@mondomaine.fr
DEFAULT_URI=http://IP_OU_DOMAINE
```

> Générer un `APP_SECRET` aléatoire : `openssl rand -hex 16`

---

## 7. Ouvrir le port SSH depuis internet (Freebox NAT)

Pour que GitHub Actions puisse se connecter à la VM :

1. **mafreebox.freebox.fr** → **Paramètres** → **Mode avancé** → **Redirections de ports**
2. Ajouter une règle :
   - Port de début : `2222`
   - Port de fin : `2222`
   - Port de destination : `22`
   - IP destination : `192.168.0.77` (IP locale de la VM)
   - Protocole : TCP
3. Récupérer l'**IP publique** : ouvrir `https://api.ipify.org` dans le navigateur

Tester depuis le PC :
```powershell
ssh -p 2222 -i "$env:USERPROFILE\.ssh\nom_projet_deploy" debian@IP_PUBLIQUE
```

---

## 8. Configurer les secrets GitHub Actions

**GitHub → dépôt → Settings → Secrets and variables → Actions → New repository secret**

| Nom | Valeur |
|---|---|
| `SSH_PRIVATE_KEY` | Contenu complet de `cle_privee.txt` (de `-----BEGIN` à `-----END`) |
| `REMOTE_HOST` | IP publique (ex. `88.169.x.x`) |
| `REMOTE_USER` | `debian` |
| `REMOTE_PATH` | `/var/www/nom-projet` |

---

## 9. Configurer le workflow GitHub Actions

### `.github/workflows/deploy.yml`

```yaml
name: Deploy

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    name: Déploiement sur serveur
    runs-on: ubuntu-latest

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo_sqlite, sqlite3, intl, mbstring, openssl

      - name: Install dependencies (prod)
        run: composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

      - name: Déploiement via rsync SSH
        env:
          SSH_PRIVATE_KEY: ${{ secrets.SSH_PRIVATE_KEY }}
          REMOTE_HOST: ${{ secrets.REMOTE_HOST }}
          REMOTE_USER: ${{ secrets.REMOTE_USER }}
          REMOTE_PATH: ${{ secrets.REMOTE_PATH }}
        run: |
          mkdir -p ~/.ssh
          echo "$SSH_PRIVATE_KEY" > ~/.ssh/id_rsa
          chmod 600 ~/.ssh/id_rsa
          ssh-keyscan -p 2222 -H "$REMOTE_HOST" >> ~/.ssh/known_hosts
          rsync -azP --delete \
            -e "ssh -p 2222" \
            --exclude='.git' \
            --exclude='.env.local' \
            --exclude='var/cache' \
            --exclude='var/log' \
            --exclude='var/*.db' \
            ./ "$REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH"

      - name: Migrations et cache
        env:
          SSH_PRIVATE_KEY: ${{ secrets.SSH_PRIVATE_KEY }}
          REMOTE_HOST: ${{ secrets.REMOTE_HOST }}
          REMOTE_USER: ${{ secrets.REMOTE_USER }}
          REMOTE_PATH: ${{ secrets.REMOTE_PATH }}
        run: |
          ssh -p 2222 "$REMOTE_USER@$REMOTE_HOST" "
            cd $REMOTE_PATH &&
            php bin/console doctrine:migrations:migrate --no-interaction --env=prod &&
            php bin/console cache:clear --env=prod
          "
```

### Points importants dans `composer.json`

Supprimer le `cache:clear` des auto-scripts (incompatible avec `--no-dev` quand MakerBundle est présent) :

```json
"scripts": {
    "auto-scripts": {
        "assets:install %PUBLIC_DIR%": "symfony-cmd"
    },
    "post-install-cmd": [],
    "post-update-cmd": []
}
```

### PHPStan

Le fichier `phpstan.neon` **ne doit pas être dans `.gitignore`** — il doit être versionné pour que le CI puisse l'utiliser.

---

## 10. Premier déploiement

```bash
git add .
git commit -m "chore: trigger first deploy"
git push origin main
```

Suivre l'avancement sur **GitHub → Actions**.

---

## 11. Créer le compte admin initial (après premier déploiement réussi)

```bash
ssh -p 2222 debian@IP_PUBLIQUE
cd /var/www/nom-projet
php bin/console app:create-admin --env=prod
```

---

## 12. Configurer le DNS dynamique (No-IP — sous-domaine gratuit)

La Freebox ne propose pas de DNS intégré sur tous les modèles. La solution la plus simple est **No-IP** (gratuit).

> **Note :** No-IP gratuit demande de confirmer le hostname **tous les 30 jours** par email, sinon il expire. Pour un usage permanent, envisager No-IP payant ou un vrai domaine (~8€/an chez OVH/Gandi).

### 1. Créer le hostname sur No-IP

1. Aller sur [noip.com](https://www.noip.com) → **Sign Up** (gratuit)
2. Créer un hostname : ex. `doudvan` avec le domaine `ddns.net` → URL finale : `doudvan.ddns.net`
3. Noter l'email et le mot de passe du compte

### 2. Configurer la Freebox

**mafreebox.freebox.fr** → **Paramètres** → **Mode avancé** → **DNS dynamique** :

| Champ | Valeur |
|---|---|
| Fournisseur | `No-IP` |
| Nom d'hôte | `doudvan.ddns.net` |
| Utilisateur | email du compte No-IP |
| Mot de passe | mot de passe No-IP |

Activer et sauvegarder. La Freebox mettra à jour l'IP automatiquement.

### 3. Mettre à jour le VirtualHost Apache sur la VM

```bash
sudo sed -i 's|DocumentRoot /var/www/nom-projet/public|ServerName doudvan.ddns.net\n    DocumentRoot /var/www/nom-projet/public|' /etc/apache2/sites-available/nom-projet.conf
sudo systemctl reload apache2
```

### 4. Mettre à jour `.env.local` sur la VM

```bash
sed -i 's|DEFAULT_URI=.*|DEFAULT_URI=http://doudvan.ddns.net|' /var/www/nom-projet/.env.local
```

### 5. Vider le cache

```bash
cd /var/www/nom-projet && php bin/console cache:clear --env=prod
```

### 6. Vérifier la propagation DNS

```bash
ping doudvan.ddns.net   # doit répondre avec l'IP publique
```

Le site est maintenant accessible via `http://doudvan.ddns.net`.

---

## Héberger plusieurs sites

Pour un deuxième projet sur la **même VM**, deux approches :

### Option A — Port différent (simple, pas de domaine requis)
```apache
# /etc/apache2/sites-available/projet2.conf
<VirtualHost *:8080>
    DocumentRoot /var/www/projet2/public
    <Directory /var/www/projet2/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```
```bash
# Ajouter le port 8080 à Apache
echo "Listen 8080" | sudo tee -a /etc/apache2/ports.conf
sudo a2ensite projet2
sudo systemctl reload apache2
```
Puis ouvrir le port 8080 dans les redirections Freebox (vers le port 8080 de la VM).

### Option B — ServerName (avec domaine ou sous-domaine)
```apache
<VirtualHost *:80>
    ServerName projet2.mondomaine.fr
    DocumentRoot /var/www/projet2/public
    <Directory /var/www/projet2/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## Dépannage rapide

| Symptôme | Cause probable | Solution |
|---|---|---|
| `Permission denied (publickey)` | Clé non enregistrée sur la VM | Ajouter la clé dans `~/.ssh/authorized_keys` via la console Freebox |
| `exit code 255` dans le pipeline | IP publique inaccessible | Vérifier la redirection de port Freebox |
| `no such table: user` dans les tests | Kernel reboot entre requêtes | Ajouter `$this->client->disableReboot()` dans `setUp()` |
| `MakerBundle not found` au deploy | `composer install --no-dev` + `cache:clear` en auto-script | Supprimer `cache:clear` des auto-scripts dans `composer.json` |
| `phpstan.neon does not exist` en CI | Fichier ignoré par `.gitignore` | Retirer `phpstan.neon` du `.gitignore` |
| PHP 8.3 au lieu de 8.4 dans le CI | Version non mise à jour dans `ci.yml` | Changer `php: ['8.3']` en `php: ['8.4']` |
| `Not Found` sur le site (Apache répond mais 404) | `.htaccess` absent de `public/` | Créer `public/.htaccess` avec les règles de rewrite Symfony et le commiter |
| Warning PHPUnit `AbstractWebTest is abstract` fait échouer le CI | `failOnWarning="true"` dans `phpunit.dist.xml` | Ajouter `<exclude>tests/Controller/AbstractWebTest.php</exclude>` dans `phpunit.dist.xml` |
| `rsync: command not found` dans le pipeline | rsync non installé sur la VM | `sudo apt install -y rsync` sur la VM |
