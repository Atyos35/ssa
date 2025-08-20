# 🕵️‍♂️ SSA - Secret Service Agency

Application complète de gestion d'agents secrets avec architecture CQS (Command Query Separation) et interface moderne.

## 🏗️ Architecture

- **Backend**: Symfony 7 + Architecture CQS + PostgreSQL
- **Frontend**: Nuxt.js 3 + Vue 3 + Quasar
- **Tests**: PHPUnit + Tests Unitaires et d'Intégration
- **Base de données**: PostgreSQL avec Docker
- **Email**: Mailhog pour l'inscription

## 🚀 Installation Rapide

Script Automatique

#### AVEC GIT BASH
```bash
chmod +x install.sh
./install.sh
```

## 🌐 Accès à l'Application

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000
- **Mailhog**: http://localhost:8025
- **Base de données**: postgresql://127.0.0.1:5432/ssa?serverVersion=16&charset=utf8 sur DBeaver par exemple

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

### Structure CQS
- **Commands**: Modifient l'état (CreateAgent, UpdateMission, etc.)
- **Queries**: Récupèrent des données (GetAgents, GetMissions, etc.)
- **Handlers**: Exécutent la logique métier
- **Services**: Logique métier complexe
