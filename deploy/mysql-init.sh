#!/bin/bash
# =============================================================================
# MySQL initialization script for Docker
# =============================================================================
# Runs inside the MySQL container on first boot.
# Replaces {db_prefix} placeholders and imports SQL dumps in correct order.
# =============================================================================

set -e

DB_PREFIX="${DB_PREFIX:-fl_}"
SQL_DIR="/docker-entrypoint-initdb.d/sql"

echo "[mysql-init] Starting Flynax database initialization ..."
echo "[mysql-init] Using table prefix: ${DB_PREFIX}"

# Import main dump first
if [ -f "${SQL_DIR}/dump.sql" ]; then
    echo "[mysql-init] Importing dump.sql ..."
    sed "s/{db_prefix}/${DB_PREFIX}/g" "${SQL_DIR}/dump.sql" | mysql -u root -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}"
fi

# Import additional fl_*.sql files (locations, formats, etc.)
for sqlfile in ${SQL_DIR}/fl_*.sql; do
    if [ -f "$sqlfile" ]; then
        fname=$(basename "$sqlfile")
        echo "[mysql-init] Importing ${fname} ..."
        sed "s/{db_prefix}/${DB_PREFIX}/g" "$sqlfile" | mysql -u root -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" || \
            echo "[mysql-init] WARNING: ${fname} had errors (may be expected for optional data)"
    fi
done

# Import post_package.sql (hooks, plugin registrations)
if [ -f "${SQL_DIR}/post_package.sql" ]; then
    echo "[mysql-init] Importing post_package.sql ..."
    sed "s/{db_prefix}/${DB_PREFIX}/g" "${SQL_DIR}/post_package.sql" | mysql -u root -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}"
fi

# Import solar setup
if [ -f "${SQL_DIR}/solar_setup.sql" ]; then
    echo "[mysql-init] Importing solar_setup.sql ..."
    sed "s/{db_prefix}/${DB_PREFIX}/g" "${SQL_DIR}/solar_setup.sql" | mysql -u root -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}"
fi

# Import pipeline tables
if [ -f "${SQL_DIR}/pipeline_tables.sql" ]; then
    echo "[mysql-init] Importing pipeline_tables.sql ..."
    sed "s/{db_prefix}/${DB_PREFIX}/g" "${SQL_DIR}/pipeline_tables.sql" | mysql -u root -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}"
fi

echo "[mysql-init] Database initialization complete."
