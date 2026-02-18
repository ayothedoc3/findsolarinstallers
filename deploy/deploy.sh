#!/usr/bin/env bash
###############################################################################
#  Solar Directory - Automated VPS Deployment Script
#  -------------------------------------------------
#  Takes a fresh Ubuntu 22.04 / 24.04 server and produces a running
#  Solar Directory (Flynax-based) website.
#
#  Usage:  sudo bash deploy.sh --domain example.com \
#              --db-pass 'S3cretP@ss' --admin-email you@example.com
#
#  The Flynax codebase must already be present in the parent directory
#  of this script (uploaded manually or via SCP).
###############################################################################

set -euo pipefail

# ─────────────────────────────────────────────────────────────────────────────
# Colors
# ─────────────────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

info()    { echo -e "${CYAN}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
err()     { echo -e "${RED}[ERROR]${NC} $*" >&2; }

# ─────────────────────────────────────────────────────────────────────────────
# Usage / Help
# ─────────────────────────────────────────────────────────────────────────────
usage() {
    cat <<EOF
${BOLD}Solar Directory - Deployment Script${NC}

${BOLD}USAGE${NC}
    sudo bash deploy.sh [OPTIONS]

${BOLD}REQUIRED OPTIONS${NC}
    --domain        Target domain name           (e.g. solardirectory.com)
    --db-pass       MySQL password for the app   (e.g. 'MyS3cure!Pass')
    --admin-email   E-mail for SSL cert & admin  (e.g. admin@solardirectory.com)

${BOLD}OPTIONAL${NC}
    --db-name       Database name                (default: solarlisting)
    --db-user       Database user                (default: solar_user)
    --db-prefix     Flynax table prefix          (default: fl_)
    --skip-ssl      Skip Let's Encrypt SSL setup (useful for local/staging)

${BOLD}EXAMPLE${NC}
    sudo bash deploy.sh \\
        --domain solardirectory.com \\
        --db-pass 'P@ssw0rd!' \\
        --admin-email admin@solardirectory.com

EOF
    exit 1
}

# ─────────────────────────────────────────────────────────────────────────────
# Argument Parsing
# ─────────────────────────────────────────────────────────────────────────────
DOMAIN=""
DB_PASS=""
ADMIN_EMAIL=""
DB_NAME="solarlisting"
DB_USER="solar_user"
DB_PREFIX="fl_"
SKIP_SSL=false

while [[ $# -gt 0 ]]; do
    case "$1" in
        --domain)      DOMAIN="$2";      shift 2 ;;
        --db-pass)     DB_PASS="$2";     shift 2 ;;
        --admin-email) ADMIN_EMAIL="$2"; shift 2 ;;
        --db-name)     DB_NAME="$2";     shift 2 ;;
        --db-user)     DB_USER="$2";     shift 2 ;;
        --db-prefix)   DB_PREFIX="$2";   shift 2 ;;
        --skip-ssl)    SKIP_SSL=true;    shift   ;;
        -h|--help)     usage ;;
        *)
            err "Unknown option: $1"
            usage
            ;;
    esac
done

# Validate required arguments
if [[ -z "$DOMAIN" ]]; then
    err "--domain is required"
    usage
fi
if [[ -z "$DB_PASS" ]]; then
    err "--db-pass is required"
    usage
fi
if [[ -z "$ADMIN_EMAIL" ]]; then
    err "--admin-email is required"
    usage
fi

# Must be root
if [[ $EUID -ne 0 ]]; then
    err "This script must be run as root (use sudo)"
    exit 1
fi

# ─────────────────────────────────────────────────────────────────────────────
# Derived Variables
# ─────────────────────────────────────────────────────────────────────────────
DEPLOY_DIR="$(cd "$(dirname "$0")/.." && pwd)"
WEB_ROOT="/var/www/${DOMAIN}"

if [[ "$SKIP_SSL" == true ]]; then
    SITE_URL="http://${DOMAIN}/"
else
    SITE_URL="https://${DOMAIN}/"
fi

CACHE_POSTFIX="$(head -c 100 /dev/urandom | tr -dc 'a-zA-Z0-9' | head -c 8)"

echo ""
echo -e "${BOLD}============================================================${NC}"
echo -e "${BOLD}  Solar Directory Deployment${NC}"
echo -e "${BOLD}============================================================${NC}"
echo -e "  Domain:      ${CYAN}${DOMAIN}${NC}"
echo -e "  DB Name:     ${DB_NAME}"
echo -e "  DB User:     ${DB_USER}"
echo -e "  DB Prefix:   ${DB_PREFIX}"
echo -e "  Web Root:    ${WEB_ROOT}"
echo -e "  Source:      ${DEPLOY_DIR}"
echo -e "  SSL:         $(if $SKIP_SSL; then echo 'SKIP'; else echo 'Yes (Let'\''s Encrypt)'; fi)"
echo -e "${BOLD}============================================================${NC}"
echo ""

###############################################################################
# PHASE 1 - System Packages
###############################################################################
echo -e "\n${BOLD}--- PHASE 1: System Packages ---${NC}\n"

info "Setting timezone to UTC..."
timedatectl set-timezone UTC
success "Timezone set to UTC"

info "Updating apt package lists..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
success "Package lists updated"

info "Installing required packages..."
apt-get install -y -qq \
    apache2 \
    mysql-server \
    php8.1-fpm \
    php8.1-mysqli \
    php8.1-gd \
    php8.1-curl \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-zip \
    php8.1-intl \
    certbot \
    python3-certbot-apache \
    python3-pip \
    python3-venv \
    unzip
success "All packages installed"

info "Enabling Apache modules..."
a2enmod rewrite proxy_fcgi setenvif ssl headers deflate expires > /dev/null 2>&1
# Enable PHP-FPM config for Apache
a2enconf php8.1-fpm > /dev/null 2>&1 || true
success "Apache modules enabled: rewrite, proxy_fcgi, setenvif, ssl, headers, deflate, expires"

# Ensure services are running
systemctl enable --now apache2  > /dev/null 2>&1
systemctl enable --now php8.1-fpm > /dev/null 2>&1
success "Apache and PHP-FPM are running"

###############################################################################
# PHASE 2 - MySQL Setup
###############################################################################
echo -e "\n${BOLD}--- PHASE 2: MySQL Setup ---${NC}\n"

info "Starting and enabling MySQL..."
systemctl enable --now mysql > /dev/null 2>&1
success "MySQL is running"

info "Creating database '${DB_NAME}'..."
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
success "Database '${DB_NAME}' ready"

info "Creating MySQL user '${DB_USER}'..."
# Drop user first (idempotent) then recreate
mysql -e "DROP USER IF EXISTS '${DB_USER}'@'localhost';"
mysql -e "CREATE USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
success "MySQL user '${DB_USER}' created with full privileges on '${DB_NAME}'"

# Import core dump.sql (with prefix replacement)
info "Importing dump.sql (core schema)..."
sed "s/{db_prefix}/${DB_PREFIX}/g" "${DEPLOY_DIR}/install/mysql/dump.sql" \
    | mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}"
success "dump.sql imported"

# Import solar_setup.sql
info "Importing solar_setup.sql..."
sed "s/{db_prefix}/${DB_PREFIX}/g" "${DEPLOY_DIR}/install/mysql/solar_setup.sql" \
    | mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}"
success "solar_setup.sql imported"

# Import pipeline_tables.sql (if it exists)
if [[ -f "${DEPLOY_DIR}/install/mysql/pipeline_tables.sql" ]]; then
    info "Importing pipeline_tables.sql..."
    sed "s/{db_prefix}/${DB_PREFIX}/g" "${DEPLOY_DIR}/install/mysql/pipeline_tables.sql" \
        | mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}"
    success "pipeline_tables.sql imported"
else
    warn "pipeline_tables.sql not found -- skipping"
fi

# Import remaining fl_*.sql files (additional Flynax data: locations, plugins, etc.)
info "Importing additional fl_*.sql data files..."
EXTRA_COUNT=0
for sql_file in "${DEPLOY_DIR}"/install/mysql/fl_*.sql; do
    [[ ! -f "$sql_file" ]] && continue
    basename_file="$(basename "$sql_file")"
    # Skip the files we already imported explicitly
    case "$basename_file" in
        dump.sql|solar_setup.sql|pipeline_tables.sql) continue ;;
    esac
    info "  -> ${basename_file}"
    sed "s/{db_prefix}/${DB_PREFIX}/g" "$sql_file" \
        | mysql -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}"
    EXTRA_COUNT=$((EXTRA_COUNT + 1))
done
success "Imported ${EXTRA_COUNT} additional SQL file(s)"

###############################################################################
# PHASE 3 - Deploy Files
###############################################################################
echo -e "\n${BOLD}--- PHASE 3: Deploy Files ---${NC}\n"

info "Copying codebase to ${WEB_ROOT}..."
mkdir -p "${WEB_ROOT}"
rsync -a --delete \
    --exclude deploy/ \
    --exclude .git/ \
    --exclude data/ \
    "${DEPLOY_DIR}/" "${WEB_ROOT}/"
success "Codebase deployed to ${WEB_ROOT}"

info "Setting file ownership to www-data..."
chown -R www-data:www-data "${WEB_ROOT}"
success "Ownership set"

info "Setting base permissions (755 dirs, 644 files)..."
find "${WEB_ROOT}" -type d -exec chmod 755 {} \;
find "${WEB_ROOT}" -type f -exec chmod 644 {} \;
success "Base permissions set"

info "Setting writable directories (tmp, files, plugins)..."
mkdir -p "${WEB_ROOT}/tmp" "${WEB_ROOT}/files" "${WEB_ROOT}/plugins"
chmod -R 777 "${WEB_ROOT}/tmp" "${WEB_ROOT}/files" "${WEB_ROOT}/plugins"
success "Writable directories configured"

info "Creating data/ and scripts/logs/ directories..."
mkdir -p "${WEB_ROOT}/../data"
chown www-data:www-data "${WEB_ROOT}/../data"
mkdir -p "${WEB_ROOT}/scripts/logs"
chown -R www-data:www-data "${WEB_ROOT}/scripts/logs"
success "Auxiliary directories created"

###############################################################################
# PHASE 4 - Write config.inc.php
###############################################################################
echo -e "\n${BOLD}--- PHASE 4: Write config.inc.php ---${NC}\n"

CONFIG_TEMPLATE="${DEPLOY_DIR}/install/config.inc.php.tmp"
CONFIG_TARGET="${WEB_ROOT}/includes/config.inc.php"

if [[ ! -f "$CONFIG_TEMPLATE" ]]; then
    err "Config template not found at ${CONFIG_TEMPLATE}"
    exit 1
fi

info "Generating config.inc.php from template..."
mkdir -p "$(dirname "${CONFIG_TARGET}")"

sed \
    -e "s|{db_host}|localhost|g" \
    -e "s|{db_user}|${DB_USER}|g" \
    -e "s|{db_pass}|${DB_PASS}|g" \
    -e "s|{db_name}|${DB_NAME}|g" \
    -e "s|{db_prefix}|${DB_PREFIX}|g" \
    -e "s|{db_port}|3306|g" \
    -e "s|{rl_root}|${WEB_ROOT}/|g" \
    -e "s|{rl_url}|${SITE_URL}|g" \
    -e "s|{rl_admin}|admin|g" \
    -e "s|{rl_cache_postfix}|${CACHE_POSTFIX}|g" \
    -e "s|{rl_dir}|''|g" \
    -e "s|{file}|config.inc.php|g" \
    "${CONFIG_TEMPLATE}" > "${CONFIG_TARGET}"

chmod 644 "${CONFIG_TARGET}"
chown www-data:www-data "${CONFIG_TARGET}"
success "config.inc.php written to ${CONFIG_TARGET}"

###############################################################################
# PHASE 5 - Apache VirtualHost
###############################################################################
echo -e "\n${BOLD}--- PHASE 5: Apache VirtualHost ---${NC}\n"

VHOST_FILE="/etc/apache2/sites-available/${DOMAIN}.conf"

info "Writing Apache VirtualHost to ${VHOST_FILE}..."
cat > "${VHOST_FILE}" <<VHOST
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias www.${DOMAIN}
    DocumentRoot ${WEB_ROOT}

    <Directory ${WEB_ROOT}>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Route PHP requests through PHP-FPM
    <FilesMatch \.php\$>
        SetHandler "proxy:unix:/run/php/php8.1-fpm.sock|fcgi://localhost"
    </FilesMatch>

    ErrorLog  \${APACHE_LOG_DIR}/${DOMAIN}-error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN}-access.log combined
</VirtualHost>
VHOST

success "VirtualHost config written"

info "Enabling site and disabling default..."
a2ensite "${DOMAIN}.conf" > /dev/null 2>&1
a2dissite 000-default.conf > /dev/null 2>&1 || true
success "Site enabled"

info "Restarting Apache..."
systemctl restart apache2
success "Apache restarted"

###############################################################################
# PHASE 6 - SSL (Let's Encrypt)
###############################################################################
echo -e "\n${BOLD}--- PHASE 6: SSL Certificate ---${NC}\n"

if [[ "$SKIP_SSL" == true ]]; then
    warn "Skipping SSL (--skip-ssl flag set)"
    warn "Site will be served over HTTP only"
else
    info "Requesting Let's Encrypt certificate for ${DOMAIN}..."
    certbot --apache \
        -d "${DOMAIN}" \
        -d "www.${DOMAIN}" \
        --non-interactive \
        --agree-tos \
        -m "${ADMIN_EMAIL}"
    success "SSL certificate installed for ${DOMAIN}"
fi

###############################################################################
# PHASE 7 - Python Virtual Environment
###############################################################################
echo -e "\n${BOLD}--- PHASE 7: Python Environment ---${NC}\n"

VENV_DIR="${WEB_ROOT}/scripts/venv"

info "Creating Python virtual environment at ${VENV_DIR}..."
python3 -m venv "${VENV_DIR}"
success "Virtual environment created"

REQUIREMENTS="${WEB_ROOT}/scripts/requirements.txt"
if [[ -f "$REQUIREMENTS" ]]; then
    info "Installing Python dependencies from requirements.txt..."
    "${VENV_DIR}/bin/pip" install --upgrade pip > /dev/null 2>&1
    "${VENV_DIR}/bin/pip" install -r "${REQUIREMENTS}"
    success "Python dependencies installed"
else
    warn "No requirements.txt found at ${REQUIREMENTS} -- skipping pip install"
fi

chown -R www-data:www-data "${VENV_DIR}"
success "Virtual environment ownership set to www-data"

###############################################################################
# PHASE 8 - Cron Setup
###############################################################################
echo -e "\n${BOLD}--- PHASE 8: Cron Jobs ---${NC}\n"

info "Installing crontab entries for www-data..."

# Build the cron block with a marker so re-runs are idempotent
CRON_MARKER="# -- Solar Directory cron (${DOMAIN}) --"
CRON_BLOCK="${CRON_MARKER}
*/30 * * * * php ${WEB_ROOT}/cron/index.php > /dev/null 2>&1
0 2 * * 0 ${VENV_DIR}/bin/python ${WEB_ROOT}/scripts/pipeline_orchestrator.py --mode weekly >> ${WEB_ROOT}/scripts/logs/pipeline.log 2>&1
0 3 1 * * ${VENV_DIR}/bin/python ${WEB_ROOT}/scripts/pipeline_orchestrator.py --mode monthly >> ${WEB_ROOT}/scripts/logs/pipeline.log 2>&1"

# Remove old Solar Directory cron block if present, then append new one
EXISTING_CRON=$(crontab -u www-data -l 2>/dev/null || true)
# Strip any previous Solar Directory block (between markers)
CLEANED_CRON=$(echo "$EXISTING_CRON" | sed "/${CRON_MARKER//\//\\/}/,/^$/d" | sed '/^$/N;/^\n$/d')

echo "${CLEANED_CRON}
${CRON_BLOCK}
" | crontab -u www-data -

success "Cron jobs installed for www-data"
info "  -> Flynax cron:       every 30 minutes"
info "  -> Weekly pipeline:   Sundays at 02:00 UTC"
info "  -> Monthly pipeline:  1st of month at 03:00 UTC"

###############################################################################
# PHASE 9 - Post-Install Cleanup
###############################################################################
echo -e "\n${BOLD}--- PHASE 9: Post-Install Cleanup ---${NC}\n"

info "Removing install/ directory from web root..."
rm -rf "${WEB_ROOT}/install/"
success "install/ directory removed"

# Create .env from .env.example if scripts/.env.example exists
ENV_FILE="${WEB_ROOT}/scripts/.env"
ENV_EXAMPLE="${WEB_ROOT}/scripts/.env.example"

if [[ ! -f "$ENV_FILE" ]]; then
    if [[ -f "$ENV_EXAMPLE" ]]; then
        info "Creating scripts/.env from .env.example..."
        cp "$ENV_EXAMPLE" "$ENV_FILE"
        chown www-data:www-data "$ENV_FILE"
        chmod 640 "$ENV_FILE"
        success ".env created from template"
    else
        info "Creating empty scripts/.env placeholder..."
        cat > "$ENV_FILE" <<'ENVFILE'
# Solar Directory - Script Configuration
# Fill in the API keys required by your pipeline scripts.
#
# OUTSCRAPER_API_KEY=
# OPENAI_API_KEY=
# CRAWL4AI_API_KEY=
ENVFILE
        chown www-data:www-data "$ENV_FILE"
        chmod 640 "$ENV_FILE"
        success "Placeholder .env created"
    fi
else
    warn ".env already exists at ${ENV_FILE} -- not overwriting"
fi

warn "REMINDER: Edit ${ENV_FILE} with your API keys before running pipeline scripts"

###############################################################################
# Done!
###############################################################################
echo ""
echo -e "${BOLD}${GREEN}============================================================${NC}"
echo -e "${BOLD}${GREEN}  Deployment Complete!${NC}"
echo -e "${BOLD}${GREEN}============================================================${NC}"
echo ""
echo -e "  ${BOLD}Site URL:${NC}       ${SITE_URL}"
echo -e "  ${BOLD}Admin Panel:${NC}    ${SITE_URL}admin/"
echo -e "  ${BOLD}Web Root:${NC}       ${WEB_ROOT}"
echo -e "  ${BOLD}Config:${NC}         ${WEB_ROOT}/includes/config.inc.php"
echo -e "  ${BOLD}Python venv:${NC}    ${VENV_DIR}"
echo -e "  ${BOLD}Cron status:${NC}    $(crontab -u www-data -l 2>/dev/null | grep -c "${DOMAIN}") entries for ${DOMAIN}"
echo ""
echo -e "  ${YELLOW}Next steps:${NC}"
echo -e "    1. Edit ${ENV_FILE} with your API keys"
echo -e "    2. Visit ${SITE_URL}admin/ to configure the site"
echo -e "    3. Verify cron jobs: ${CYAN}crontab -u www-data -l${NC}"
echo ""
echo -e "${GREEN}Deployment finished at $(date '+%Y-%m-%d %H:%M:%S %Z')${NC}"
echo ""
