#!/bin/bash

# Script d'installation et lancement de l'application SSA
# Backend CQS + Frontend Nuxt.js

set -e  # Arrêter en cas d'erreur

echo "🚀 Installation et lancement de l'application SSA"
echo "================================================"

# Vérifier les prérequis
echo "📋 Vérification des prérequis..."

# Vérifier Docker
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé. Veuillez installer Docker Desktop."
    exit 1
fi

# Vérifier Docker Compose
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose n'est pas installé. Veuillez installer Docker Compose."
    exit 1
fi

# Vérifier Node.js
if ! command -v node &> /dev/null; then
    echo "❌ Node.js n'est pas installé. Veuillez installer Node.js 18+."
    exit 1
fi

# Vérifier PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé. Veuillez installer PHP 8.2+."
    exit 1
fi

# Vérifier Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer n'est pas installé. Veuillez installer Composer."
    exit 1
fi

echo "✅ Tous les prérequis sont installés !"

# Créer les fichiers .env
echo "🔧 Configuration de l'environnement..."

# Backend .env
if [ ! -f "Backend/.env" ]; then
    echo "📝 Création du fichier .env pour le Backend..."
    cat > Backend/.env << EOF
APP_ENV=dev
APP_SECRET=your_secret_key_here_change_in_production
POSTGRES_USER=ssa
POSTGRES_PASSWORD=ssa
POSTGRES_DB=ssa
POSTGRES_VERSION=16
CORS_ALLOW_ORIGIN=http://localhost:3000
JWT_SECRET_KEY=your_jwt_secret_key_here_change_in_production
JWT_PUBLIC_KEY=your_jwt_public_key_here_change_in_production
JWT_PRIVATE_KEY=your_jwt_private_key_here_change_in_production
JWT_PASSPHRASE=your_jwt_passphrase_here_change_in_production
MAILER_DSN=null://null
MESSENGER_TRANSPORT_DSN=in-memory://
PASSWORD_TEST=test_password_123
DATABASE_URL=postgresql://ssa:ssa@127.0.0.1:5432/ssa?serverVersion=16&charset=utf8
EOF
    echo "✅ Fichier .env Backend créé"
else
    echo "✅ Fichier .env Backend existe déjà"
fi

# Frontend .env
if [ ! -f "Frontend/.env" ]; then
    echo "📝 Création du fichier .env pour le Frontend..."
    cat > Frontend/.env << 'EOF'
# Configuration de l'environnement Frontend SSA
# Généré automatiquement par le script d'installation

# API Backend
NUXT_PUBLIC_API_BASE_URL=http://localhost:8000

# Nom de l'application
NUXT_PUBLIC_APP_NAME=SSA - Secret Service Agency
EOF
    echo "✅ Fichier .env Frontend créé"
else
    echo "✅ Fichier .env Frontend existe déjà"
fi

# Lancer les services Docker
echo "🐳 Lancement des services Docker (PostgreSQL + Mailhog)..."
cd Backend
docker-compose up -d
cd ..

# Attendre que PostgreSQL soit prêt
echo "⏳ Attente que PostgreSQL soit prêt..."
# Attendre que le conteneur soit démarré et prêt
until docker-compose -f Backend/compose.yaml exec -T database pg_isready -U ssa; do
    echo "⏳ PostgreSQL n'est pas encore prêt..."
    sleep 2
done
echo "✅ PostgreSQL est prêt !"

# Installer les dépendances Backend
echo "📦 Installation des dépendances Backend..."
cd Backend
composer install --no-interaction --prefer-dist --optimize-autoloader

# Créer la base de données et le schéma
echo "🗄️ Vérification et création de la base de données..."
# Vérifier si la base de données existe déjà
if php bin/console doctrine:query:sql "SELECT 1" --no-interaction 2>/dev/null; then
    echo "✅ Base de données 'ssa' existe déjà"
else
    echo "📝 Création de la base de données 'ssa'..."
    php bin/console doctrine:database:create --no-interaction
fi

# Vérifier si le schéma existe déjà
if php bin/console doctrine:query:sql "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'" --no-interaction 2>/dev/null | grep -q "0"; then
    echo "📝 Création du schéma de base de données..."
    php bin/console doctrine:schema:create --no-interaction
else
    echo "✅ Schéma de base de données existe déjà"
fi

# Lancer le serveur Backend
echo "🚀 Lancement du serveur Backend..."
cd public
php -S localhost:8000 &
BACKEND_PID=$!
cd ..

# Lancer le consommateur de messages asynchrones
echo "🔄 Lancement du consommateur de messages asynchrones..."
php bin/console messenger:consume async &
MESSENGER_PID=$!

# Installer les dépendances Frontend
echo "📦 Installation des dépendances Frontend..."
cd Frontend
npm install

# Lancer le serveur Frontend
echo "🚀 Lancement du serveur Frontend..."
npm run dev &
FRONTEND_PID=$!
cd ..

# Attendre un peu pour que les serveurs démarrent
echo "⏳ Démarrage des serveurs..."
sleep 5

# Afficher les informations
echo ""
echo "🎉 Application SSA installée et lancée avec succès !"
echo "=================================================="
echo ""
echo "🌐 Frontend: http://localhost:3000/registration"
echo "🔧 Backend API: http://localhost:8000"
echo "📧 Mailhog: http://localhost:8025"
echo "🗄️ Base de données: localhost:5432"
echo ""
echo "🛑 Pour arrêter l'application:"
echo "   - Ctrl+C pour arrêter ce script"
echo "   - docker-compose down dans le dossier Backend"
echo ""

# Fonction de nettoyage
cleanup() {
    echo ""
    echo "🛑 Arrêt de l'application..."
    kill $BACKEND_PID 2>/dev/null || true
    kill $MESSENGER_PID 2>/dev/null || true
    kill $FRONTEND_PID 2>/dev/null || true
    cd Backend
    docker-compose down
    cd ..
    echo "✅ Application arrêtée"
    exit 0
}

# Capturer Ctrl+C
trap cleanup SIGINT

# Garder le script en vie
echo "🔄 Application en cours d'exécution... Appuyez sur Ctrl+C pour arrêter"
wait
