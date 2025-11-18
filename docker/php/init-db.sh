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
MAX_RETRIES=60
RETRY=0
MYSQL_READY=0

while [ $RETRY -lt $MAX_RETRIES ]; do
    if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" >/dev/null 2>&1; then
        MYSQL_READY=1
        break
    fi
    RETRY=$((RETRY + 1))
    if [ $((RETRY % 5)) -eq 0 ]; then
        echo "MySQL n'est pas encore prêt, attente... ($RETRY/$MAX_RETRIES)"
    fi
    sleep 2
done

if [ $MYSQL_READY -eq 1 ]; then
    echo "✓ MySQL est prêt"
else
    echo "⚠️  MySQL n'est pas disponible après $MAX_RETRIES tentatives"
    echo "⚠️  L'application démarrera mais la base de données peut ne pas être initialisée"
fi
echo ""

# Vérifier si la table 'user' existe (seulement si MySQL est prêt)
if [ $MYSQL_READY -eq 1 ]; then
    echo "Vérification de l'existence de la table 'user'..."
    TABLE_COUNT=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='user';" 2>/dev/null | tail -n 1 | tr -d '[:space:]' || echo "0")
    
    if [ "$TABLE_COUNT" -eq "0" ] || [ -z "$TABLE_COUNT" ]; then
        echo "✗ La table 'user' n'existe pas"
        echo "Import du fichier SQL..."
        
        if [ -f "$SQL_FILE" ]; then
            echo "Fichier SQL trouvé: $SQL_FILE"
            echo "Import en cours..."
            mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SQL_FILE" 2>&1
            
            # Vérifier que l'import a réussi
            TABLE_COUNT_AFTER=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='user';" 2>/dev/null | tail -n 1 | tr -d '[:space:]' || echo "0")
            if [ "$TABLE_COUNT_AFTER" -eq "1" ]; then
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
else
    echo "⚠️  Impossible de vérifier/initialiser la base de données (MySQL non disponible)"
fi

echo ""
echo "=========================================="

