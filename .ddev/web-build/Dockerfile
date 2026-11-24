#ddev-silent-no-warn
ARG BASE_IMAGE
FROM $BASE_IMAGE

# Platzhalter-Startseiten, bis die Instanzen installiert sind
ARG uid
ARG gid
RUN mkdir -p /var/www/html/v13/public /var/www/html/v14/public \
 && echo '<h1>Zuerst <code>ddev install-v13</code> ausfuehren</h1>' > /var/www/html/v13/public/index.html \
 && echo '<h1>Zuerst <code>ddev install-v14</code> ausfuehren</h1>' > /var/www/html/v14/public/index.html \
 && echo '<h1>rsmbouncemailprocessor Dev</h1><ul><li><a href="https://v13.rsmbouncemailprocessor.ddev.site/typo3/">TYPO3 13.4</a></li><li><a href="https://v14.rsmbouncemailprocessor.ddev.site/typo3/">TYPO3 14.3</a></li><li><a href="https://rsmbouncemailprocessor.ddev.site:8026">Mailpit</a></li></ul>' > /var/www/html/index.html \
 && chown -R $uid:$gid /var/www/html
