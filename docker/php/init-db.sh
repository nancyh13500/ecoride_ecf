#!/bin/bash
# Script d'initialisation de la base de données
# Vérifie si les tables existent et importe le SQL si nécessaire

# Ne pas utiliser set -e pour ne pas bloquer le démarrage d'Apache en cas d'erreur

DB_HOST="${DB_HOST:-db}"
DB_NAME="${DB_NAME:-ecoride}"
DB_USER="${DB_USER:-ecoride_user}"
DB_PASS="${DB_PASS:-ecoride_pass}"
# Le fichier SQL est copié dans l'image avec le code
SQL_FILE="${SQL_FILE:-/var/www/html/ecoride.sql}"

echo "=========================================="
echo "Initialisation de la base de données"
echo "=========================================="

# Attendre que MySQL soit prêt
echo "Attente de MySQL..."
MAX_RETRIES=30
RETRY=0
until mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" >/dev/null 2>&1; do
    RETRY=$((RETRY + 1))
    if [ $RETRY -ge $MAX_RETRIES ]; then
        echo "⚠️  MySQL n'est pas disponible après $MAX_RETRIES tentatives"
        echo "⚠️  L'application démarrera mais la base de données peut ne pas être initialisée"
        break
    fi
    echo "MySQL n'est pas encore prêt, attente... ($RETRY/$MAX_RETRIES)"
    sleep 2
done

echo "✓ MySQL est prêt"
echo ""

# Vérifier si la table 'user' existe
echo "Vérification de l'existence de la table 'user'..."
TABLE_EXISTS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES LIKE 'user';" 2>/dev/null | grep -c "user" || echo "0")

if [ "$TABLE_EXISTS" -eq "0" ]; then
    echo "✗ La table 'user' n'existe pas"
    echo "Import du fichier SQL..."
    
    if [ -f "$SQL_FILE" ]; then
        echo "Fichier SQL trouvé: $SQL_FILE"
        echo "Import en cours..."
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SQL_FILE"
        
        # Vérifier que l'import a réussi
        TABLE_EXISTS_AFTER=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES LIKE 'user';" 2>/dev/null | grep -c "user" || echo "0")
        if [ "$TABLE_EXISTS_AFTER" -eq "1" ]; then
            echo "✓ Import réussi - La table 'user' existe maintenant"
        else
            echo "✗ Erreur : L'import semble avoir échoué"
        fi
    else
        echo "⚠️  Fichier SQL non trouvé: $SQL_FILE"
        echo "Recherche du fichier SQL dans /var/www/html..."
        find /var/www/html -name "*.sql" -type f | head -5
        echo ""
        echo "⚠️  L'application peut ne pas fonctionner correctement sans la base de données initialisée"
    fi
else
    echo "✓ La table 'user' existe déjà, pas besoin d'importer"
fi

echo ""
echo "=== Tables présentes dans la base de données ==="
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES;" 2>/dev/null || echo "Erreur lors de la vérification"
echo ""
echo "=========================================="

