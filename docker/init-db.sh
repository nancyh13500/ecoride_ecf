#!/bin/bash
# Script d'initialisation de la base de données
# Vérifie si les tables existent et les crée si nécessaire

set -e

echo "🔄 Vérification de la base de données..."

# Attendre que MySQL soit prêt
until mysqladmin ping -h db -u root -proot --silent; do
    echo "⏳ En attente de MySQL..."
    sleep 2
done

echo "✅ MySQL est prêt"

# Vérifier si la table user existe
TABLE_EXISTS=$(mysql -h db -u ecoride_user -pecoride_pass ecoride -e "SHOW TABLES LIKE 'user';" 2>/dev/null | grep -c "user" || echo "0")

if [ "$TABLE_EXISTS" -eq "0" ]; then
    echo "📦 Initialisation de la base de données..."
    
    # Vérifier si le fichier SQL existe
    if [ -f /var/www/html/ecoride.sql ]; then
        echo "📄 Import du fichier SQL..."
        mysql -h db -u root -proot ecoride < /var/www/html/ecoride.sql
        echo "✅ Base de données initialisée avec succès!"
    else
        echo "⚠️ Fichier ecoride.sql non trouvé dans /var/www/html/"
        exit 1
    fi
else
    echo "✅ La base de données est déjà initialisée"
fi

