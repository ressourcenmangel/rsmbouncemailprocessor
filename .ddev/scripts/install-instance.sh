#!/bin/bash
# Installiert eine TYPO3-Instanz (v13 oder v14) im Web-Container.
# Aufruf: install-instance.sh <13|14>
# Idempotent: eine vorhandene Instanz samt Datenbank wird komplett neu aufgebaut.
set -euo pipefail

MAJOR="${1:?Aufruf: install-instance.sh <13|14>}"
VERSION="v${MAJOR}"
TARGET="/var/www/html/${VERSION}"
EXT_PATH="/var/www/${EXTENSION_KEY}"
HOST="${VERSION}.${DDEV_SITENAME}.ddev.site"

# Instanz leeren (Volume bleibt bestehen, Inhalt wird neu aufgebaut)
mkdir -p "${TARGET}"
find "${TARGET}" -mindepth 1 -delete
mysql -h db -u root -p"${TYPO3_DB_PASSWORD}" -e "DROP DATABASE IF EXISTS \`${VERSION}\`; CREATE DATABASE \`${VERSION}\`;"

cd "${TARGET}"
echo '{}' >composer.json
composer config extra.typo3/cms.web-dir public
composer config minimum-stability dev
composer config prefer-stable true
# Unsere Extension per Path-Repository: Symlink auf das Repo, Aenderungen wirken sofort
composer config repositories.ext "{\"type\":\"path\",\"url\":\"${EXT_PATH}\",\"options\":{\"symlink\":true}}" --json
composer config audit.block-insecure false
composer config --no-plugins allow-plugins.typo3/cms-composer-installers true
composer config --no-plugins allow-plugins.typo3/class-alias-loader true

composer require --no-progress -n \
  "typo3/minimal:^${MAJOR}" \
  "typo3/cms-install:^${MAJOR}" \
  "typo3/cms-scheduler:^${MAJOR}" \
  "typo3/cms-fluid-styled-content:^${MAJOR}" \
  "typo3/cms-lowlevel:^${MAJOR}" \
  "undkonsorten/typo3-cute-mailing:^4.2" \
  "undkonsorten/typo3-cute-mailing-ttaddress:^2.4" \
  "${PACKAGE_NAME}:*@dev"

vendor/bin/typo3 setup -n \
  --driver=mysqli --host=db --port=3306 \
  --dbname="${VERSION}" --username=root --password="${TYPO3_DB_PASSWORD}" \
  --admin-username=admin --admin-user-password="${TYPO3_SETUP_ADMIN_PASSWORD}" \
  --admin-email="${TYPO3_SETUP_ADMIN_EMAIL}" \
  --project-name="${EXTENSION_KEY} ${VERSION}" \
  --server-type=other \
  --create-site="https://${HOST}/"

# Rewrite-Regeln von TYPO3: ab v14 gibt es kein public/typo3 mehr, /typo3/ laeuft nur ueber index.php
cp vendor/typo3/cms-install/Resources/Private/FolderStructureTemplateFiles/root-htaccess public/.htaccess

# Entwicklungs-Einstellungen (config/system/additional.php wird von TYPO3 nach settings.php geladen)
# Ausgehende Mails: Mailpit lauscht im DDEV-Web-Container auf localhost:1025
# (TYPO3-Typ "php" bekommt von DDEV keine Mail-Konfiguration, daher hier explizit)
cat >config/system/additional.php <<PHP
<?php
// Lokale Test-Konfiguration, erzeugt von .ddev/scripts/install-instance.sh
\$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = true;
\$GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = true;
\$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;
\$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
\$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';
\$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'smtp';
\$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = 'localhost:1025';
\$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = '${TYPO3_SETUP_ADMIN_EMAIL}';
\$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'RSM Bounce Test';
PHP

vendor/bin/typo3 extension:setup
# Scheduler-Tasks (AnalyzeBounceMail gegen Dovecot, ProcessBounceMail) und Seed-Daten
php /mnt/ddev_config/scripts/create-tasks.php
/mnt/ddev_config/scripts/seed-instance.sh "${MAJOR}"
vendor/bin/typo3 cache:flush

echo "Instanz ${VERSION} fertig: https://${HOST}/typo3/ (admin / siehe README)"
