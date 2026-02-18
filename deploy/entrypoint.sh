#!/bin/bash
set -e

# =============================================================================
# Solar Directory Entrypoint
# =============================================================================
# Generates config.inc.php from environment variables and starts Apache + cron
# =============================================================================

CONFIG_FILE="/var/www/html/includes/config.inc.php"
DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-solar_user}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-solarlisting}"
DB_PREFIX="${DB_PREFIX:-fl_}"
SITE_URL="${SITE_URL:-https://findsolarinstallers.xyz}"
ADMIN_DIR="${ADMIN_DIR:-admin}"
CACHE_POSTFIX=$(head -c 4 /dev/urandom | xxd -p)

echo "[entrypoint] Generating config.inc.php ..."

cat > "$CONFIG_FILE" << PHPEOF
<?php
define('RL_DBHOST', '${DB_HOST}');
define('RL_DBPORT', ${DB_PORT});
define('RL_DBUSER', '${DB_USER}');
define('RL_DBPASS', '${DB_PASS}');
define('RL_DBNAME', '${DB_NAME}');
define('RL_DBPREFIX', '${DB_PREFIX}');

define('RL_DS', DIRECTORY_SEPARATOR);
define('RL_DIR', '/var/www/html/');
define('RL_ROOT', RL_DIR);
define('RL_INC', RL_ROOT . 'includes' . RL_DS);
define('RL_CLASSES', RL_INC . 'classes' . RL_DS);
define('RL_CONTROL', RL_INC . 'controllers' . RL_DS);
define('RL_LIBS', RL_ROOT . 'libs' . RL_DS);
define('RL_LIBS_URL', '${SITE_URL}/libs/');
define('RL_TMP', RL_ROOT . 'tmp' . RL_DS);
define('RL_CACHE', RL_TMP . 'cache_${CACHE_POSTFIX}' . RL_DS);
define('RL_UPLOAD', RL_TMP . 'upload' . RL_DS);
define('RL_FILES', RL_ROOT . 'files' . RL_DS);
define('RL_FILES_URL', '${SITE_URL}/files/');
define('RL_PLUGINS', RL_ROOT . 'plugins' . RL_DS);
define('RL_PLUGINS_URL', '${SITE_URL}/plugins/');

define('RL_URL_HOME', '${SITE_URL}/');
define('ADMIN', '${ADMIN_DIR}');

define('RL_LANG_CACHE', true);
define('RL_SETUP', 'JGxpY2Vuc2VfZG9tYWluID0gImZpbmRzb2xhcmluc3RhbGxlcnMueHl6IjskbGljZW5zZV9udW1iZXIgPSAiRkwxS1U2UUxJVUpBIjs=');

define('RL_DEBUG', false);
define('RL_DB_DEBUG', false);
define('RL_AJAX_DEBUG', false);
PHPEOF

echo "[entrypoint] Config written."

# Create cache directory with postfix
mkdir -p "/var/www/html/tmp/cache_${CACHE_POSTFIX}"
chown -R www-data:www-data "/var/www/html/tmp/cache_${CACHE_POSTFIX}"

# Write pipeline .env from Docker environment variables
ENV_FILE="/var/www/html/scripts/.env"
if [ ! -f "$ENV_FILE" ] || [ -z "$(grep OUTSCRAPER_API_KEY "$ENV_FILE" 2>/dev/null | grep -v 'your_key_here')" ]; then
    echo "[entrypoint] Writing scripts/.env from environment ..."
    cat > "$ENV_FILE" << ENVEOF
OUTSCRAPER_API_KEY=${OUTSCRAPER_API_KEY:-your_key_here}
MONTHLY_CREDIT_BUDGET=${MONTHLY_CREDIT_BUDGET:-10000}
WEEKLY_REGION_COUNT=${WEEKLY_REGION_COUNT:-5}
ADMIN_EMAIL=${ADMIN_EMAIL:-}
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_NAME=${DB_NAME}
DB_PREFIX=${DB_PREFIX}
ENVEOF
    chown www-data:www-data "$ENV_FILE"
fi

# Wait for MySQL to be ready
echo "[entrypoint] Waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."
for i in $(seq 1 30); do
    if php -r "new mysqli('${DB_HOST}', '${DB_USER}', '${DB_PASS}', '${DB_NAME}', ${DB_PORT});" 2>/dev/null; then
        echo "[entrypoint] MySQL is ready."
        break
    fi
    echo "[entrypoint]   Attempt $i/30 - waiting..."
    sleep 2
done

# Run solar_setup.sql if solar categories don't exist yet
SOLAR_CHECK=$(php -r "
\$m = new mysqli('${DB_HOST}', '${DB_USER}', '${DB_PASS}', '${DB_NAME}', ${DB_PORT});
\$r = \$m->query(\"SELECT COUNT(*) as c FROM \\\`${DB_PREFIX}categories\\\` WHERE ID = 2000\");
echo \$r ? \$r->fetch_assoc()['c'] : '0';
" 2>/dev/null || echo "0")

if [ "$SOLAR_CHECK" = "0" ]; then
    echo "[entrypoint] Running solar_setup.sql ..."
    if [ -f "/var/www/html/install/mysql/solar_setup.sql" ]; then
        sed "s/{db_prefix}/${DB_PREFIX}/g" /var/www/html/install/mysql/solar_setup.sql | \
            mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" 2>/dev/null || \
            echo "[entrypoint] WARNING: solar_setup.sql may have partially failed"
    fi
fi

# Run pipeline_tables.sql if pipeline tables don't exist yet
PIPELINE_CHECK=$(php -r "
\$m = new mysqli('${DB_HOST}', '${DB_USER}', '${DB_PASS}', '${DB_NAME}', ${DB_PORT});
\$r = \$m->query(\"SHOW TABLES LIKE '${DB_PREFIX}solar_pipeline_runs'\");
echo \$r ? \$r->num_rows : '0';
" 2>/dev/null || echo "0")

if [ "$PIPELINE_CHECK" = "0" ]; then
    echo "[entrypoint] Running pipeline_tables.sql ..."
    if [ -f "/var/www/html/install/mysql/pipeline_tables.sql" ]; then
        sed "s/{db_prefix}/${DB_PREFIX}/g" /var/www/html/install/mysql/pipeline_tables.sql | \
            mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" 2>/dev/null || \
            echo "[entrypoint] WARNING: pipeline_tables.sql may have partially failed"
    fi
fi

# Start cron daemon in background
echo "[entrypoint] Starting cron daemon ..."
cron

echo "[entrypoint] Starting Apache ..."
exec apache2-foreground
