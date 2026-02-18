#!/bin/bash

echo "[entrypoint] =========================================="
echo "[entrypoint] Solar Directory Entrypoint starting ..."
echo "[entrypoint] =========================================="

# Generates config.inc.php, initializes the database, and starts Apache + cron.
# All DB initialization is handled here (not in MySQL init scripts) because
# Coolify cannot bind-mount files into the MySQL container.

CONFIG_FILE="/var/www/html/includes/config.inc.php"
SQL_DIR="/var/www/html/install/mysql"
DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-solar_user}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-solarlisting}"
DB_PREFIX="${DB_PREFIX:-fl_}"
MYSQL_OPTS="--skip-ssl"
SITE_URL="${SITE_URL:-https://findsolarinstallers.xyz}"
ADMIN_DIR="${ADMIN_DIR:-admin}"
CACHE_POSTFIX=$(date +%s%N | md5sum | head -c 8)

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

# ---------------------------------------------------------------------------
# Wait for MySQL to be ready
# ---------------------------------------------------------------------------
echo "[entrypoint] Waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."
MYSQL_READY=0
for i in $(seq 1 60); do
    if mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASS}" ${MYSQL_OPTS} --silent 2>/dev/null; then
        echo "[entrypoint] MySQL is ready."
        MYSQL_READY=1
        break
    fi
    echo "[entrypoint]   Attempt $i/60 - waiting..."
    sleep 3
done

if [ "$MYSQL_READY" = "0" ]; then
    echo "[entrypoint] ERROR: MySQL did not become ready in time. Starting Apache anyway."
fi

# Helper function to run SQL files
run_sql_file() {
    local file="$1"
    local desc="$2"
    if [ -f "$file" ]; then
        echo "[entrypoint] Importing ${desc} ..."
        { echo "SET sql_mode='NO_ENGINE_SUBSTITUTION';"; sed "s/{db_prefix}/${DB_PREFIX}/g" "$file"; } | \
            mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASS}" ${MYSQL_OPTS} "${DB_NAME}" 2>&1 || \
            echo "[entrypoint] WARNING: ${desc} had errors (may be expected for optional data)"
    else
        echo "[entrypoint] SKIP: ${desc} not found at ${file}"
    fi
}

# ---------------------------------------------------------------------------
# Database initialization — runs only if main tables don't exist yet
# ---------------------------------------------------------------------------
if [ "$MYSQL_READY" = "1" ]; then
    # Check if the main Flynax tables exist (fl_config is always present after dump.sql)
    TABLE_CHECK=$(mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASS}" ${MYSQL_OPTS} "${DB_NAME}" \
        -N -e "SHOW TABLES LIKE '${DB_PREFIX}config'" 2>/dev/null | wc -l || echo "0")

    if [ "$TABLE_CHECK" = "0" ]; then
        echo "[entrypoint] ============================================="
        echo "[entrypoint] First run — importing database schema ..."
        echo "[entrypoint] ============================================="

        # 1. Import main dump (core Flynax tables)
        run_sql_file "${SQL_DIR}/dump.sql" "dump.sql (core tables)"

        # 2. Import additional fl_*.sql files (locations, formats, etc.)
        for sqlfile in ${SQL_DIR}/fl_*.sql; do
            if [ -f "$sqlfile" ]; then
                fname=$(basename "$sqlfile")
                run_sql_file "$sqlfile" "$fname"
            fi
        done

        # 3. Import post_package.sql (hooks, plugin registrations)
        run_sql_file "${SQL_DIR}/post_package.sql" "post_package.sql (hooks/plugins)"

        # 4. Import solar setup (categories, fields, plans)
        run_sql_file "${SQL_DIR}/solar_setup.sql" "solar_setup.sql (solar directory setup)"

        # 5. Import pipeline tables
        run_sql_file "${SQL_DIR}/pipeline_tables.sql" "pipeline_tables.sql (automation tables)"

        echo "[entrypoint] Database initialization complete."
    else
        echo "[entrypoint] Database tables already exist — skipping import."

        # Still check for solar setup and pipeline tables on subsequent boots
        SOLAR_CHECK=$(mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASS}" ${MYSQL_OPTS} "${DB_NAME}" \
            -N -e "SELECT COUNT(*) FROM \`${DB_PREFIX}categories\` WHERE ID = 2000" 2>/dev/null || echo "0")

        if [ "$SOLAR_CHECK" = "0" ]; then
            run_sql_file "${SQL_DIR}/solar_setup.sql" "solar_setup.sql (solar directory setup)"
        fi

        PIPELINE_CHECK=$(mysql -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASS}" ${MYSQL_OPTS} "${DB_NAME}" \
            -N -e "SHOW TABLES LIKE '${DB_PREFIX}solar_pipeline_runs'" 2>/dev/null | wc -l || echo "0")

        if [ "$PIPELINE_CHECK" = "0" ]; then
            run_sql_file "${SQL_DIR}/pipeline_tables.sql" "pipeline_tables.sql (automation tables)"
        fi
    fi
fi

# Start cron daemon in background
echo "[entrypoint] Starting cron daemon ..."
cron

echo "[entrypoint] Starting Apache ..."
exec apache2-foreground
