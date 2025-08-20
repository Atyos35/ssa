# 🕵️‍♂️ SSA - Secret Service Agency

Application complète de gestion d'agents secrets avec architecture CQS (Command Query Separation) et interface moderne.

## 🏗️ Architecture

- **Backend**: Symfony 7 + Architecture CQS + PostgreSQL
- **Frontend**: Nuxt.js 3 + Vue 3 + Tailwind CSS
- **Tests**: PHPUnit + Tests Unitaires et d'Intégration
- **Base de données**: PostgreSQL avec Docker
- **Email**: Mailhog pour le développement

## 🚀 Installation Rapide

### Option 1: Script Automatique (Recommandé)

#### Linux/macOS/Windows (Git Bash/WSL)
```bash
chmod +x install.sh
./install.sh
```

#### Windows (PowerShell - Alternative)
```powershell
# Installer Git Bash ou WSL pour utiliser le script .sh
# Ou utiliser les commandes manuelles ci-dessous
```

### Option 2: Installation Manuelle

#### Prérequis
- Docker Desktop
- Node.js 18+
- PHP 8.2+
- Composer

#### Étapes
1. **Cloner le projet**
   ```bash
   git clone <repository-url>
   cd ssa
   ```

2. **Lancer le script d'installation automatique** (Recommandé)
   ```bash
   chmod +x install.sh
   ./install.sh
   ```
   
   **OU** installation manuelle :

3. **Lancer les services Docker**
   ```bash
   cd Backend
   docker-compose up -d
   cd ..
   ```

4. **Configurer le Backend**
   ```bash
   cd Backend
   # Le script install.sh crée automatiquement le .env
   # Sinon, créez-le manuellement avec les variables ci-dessous
   composer install
   php bin/console doctrine:database:create
   php bin/console doctrine:schema:create
   php bin/console doctrine:fixtures:load
   php -S localhost:8000 -t public
   cd ..
   ```

5. **Configurer le Frontend**
   ```bash
   cd Frontend
   # Le script install.sh crée automatiquement le .env
   # Sinon, créez-le manuellement avec les variables ci-dessous
   npm install
   npm run dev
   cd ..
   ```

## 🌐 Accès à l'Application

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000
- **Mailhog**: http://localhost:8025
- **Base de données**: localhost:5432

## 👥 Comptes de Test

- **Agent**: agent@ssa.com / password123
- **Admin**: admin@ssa.com / password123

## 🔧 Variables d'Environnement

### Backend (.env)
```bash
# Environnement
APP_ENV=dev
APP_SECRET=your_secret_key_here_change_in_production

# Base de données PostgreSQL
POSTGRES_USER=ssa
POSTGRES_PASSWORD=ssa
POSTGRES_DB=ssa
POSTGRES_VERSION=16
DATABASE_URL=postgresql://ssa:ssa@127.0.0.1:5432/ssa?serverVersion=16&charset=utf8

# CORS
CORS_ALLOW_ORIGIN=http://localhost:3000

# JWT Authentication
JWT_SECRET_KEY=your_jwt_secret_key_here_change_in_production
JWT_PUBLIC_KEY=your_jwt_public_key_here_change_in_production
JWT_PRIVATE_KEY=your_jwt_private_key_here_change_in_production
JWT_PASSPHRASE=your_jwt_passphrase_here_change_in_production

# Mailer (développement)
MAILER_DSN=null://null

# Messenger (développement)
MESSENGER_TRANSPORT_DSN=in-memory://

# Tests
PASSWORD_TEST=test_password_123
```

### Frontend (.env)
```bash
# API Backend
NUXT_PUBLIC_API_BASE_URL=http://localhost:8000

# Nom de l'application
NUXT_PUBLIC_APP_NAME=SSA - Secret Service Agency
```

## 🧪 Tests

### Lancer tous les tests
```bash
cd Backend
./vendor/bin/phpunit --testsuite="Application Test Suite"
```

### Tests unitaires uniquement
```bash
./vendor/bin/phpunit --testsuite=Unit
```

### Tests d'intégration uniquement
```bash
./vendor/bin/phpunit --testsuite=Integration
```

### Avec couverture de code
```bash
./vendor/bin/phpunit --testsuite="Application Test Suite" --coverage-text
```

## 📚 Documentation

- **Architecture CQS**: [Backend/ARCHITECTURE_CQS.md](Backend/ARCHITECTURE_CQS.md)
- **Tests**: [Backend/TESTS_README.md](Backend/TESTS_README.md)
- **Configuration**: [Backend/config/](Backend/config/)

## 🏛️ Structure du Projet

```
ssa/
├── Backend/                 # API Symfony + Architecture CQS
│   ├── src/
│   │   ├── Application/    # Commandes, Queries, Handlers
│   │   ├── Domain/         # Entités, Services, Logique métier
│   │   ├── Infrastructure/ # Persistence, Repositories
│   │   └── Presentation/   # Controllers, API
│   ├── tests/              # Tests PHPUnit
│   └── config/             # Configuration Symfony
├── Frontend/               # Interface Nuxt.js
│   ├── app/                # Composants Vue
│   ├── pages/              # Pages de l'application
│   └── services/           # Services API
├── install.sh              # Script d'installation automatique
└── README.md               # Ce fichier
```

## 🔧 Fonctionnalités

### Backend (API)
- ✅ Architecture CQS (Command Query Separation)
- ✅ Gestion des agents secrets
- ✅ Gestion des missions
- ✅ Système d'authentification JWT
- ✅ Validation des données
- ✅ Tests unitaires et d'intégration
- ✅ Base de données PostgreSQL
- ✅ Fixtures de données de test

### Frontend
- ✅ Interface moderne avec Nuxt.js 3
- ✅ Composants Vue 3 réutilisables
- ✅ Design responsive avec Tailwind CSS
- ✅ Gestion d'état avec composables
- ✅ Intégration API complète
- ✅ Formulaires validés

## 🚀 Développement

### Ajouter une nouvelle fonctionnalité
1. Créer la Command/Query dans `Backend/src/Application/`
2. Créer le Handler correspondant
3. Ajouter la logique métier dans `Backend/src/Domain/Service/`
4. Créer les tests unitaires
5. Ajouter l'interface utilisateur dans `Frontend/`

### Structure CQS
- **Commands**: Modifient l'état (CreateAgent, UpdateMission, etc.)
- **Queries**: Récupèrent des données (GetAgents, GetMissions, etc.)
- **Handlers**: Exécutent la logique métier
- **Services**: Logique métier complexe
