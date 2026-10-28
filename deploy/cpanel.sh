#!/bin/bash
#
# After a pull on a cPanel server (.cpanel.yml: "Deploy HEAD Commit"), from
# the `deploy` branch, which already carries public/build and vendor/
# (.github/workflows/deploy-branch.yml): the database brought up to date and
# Laravel's caches made again. The site is down for the few seconds it takes,
# and up again whatever happens.
#
# The .env stays on the server, out of git; this refuses to run without it.

set -euo pipefail

cd "$(dirname "$0")/.."

# The PHP the site runs on: EasyApache's, as MultiPHP Manager gives it to
# the domain (CloudLinux's PHP Selector cannot set one per domain there),
# else CloudLinux's.
PHP=""
for candidate in /opt/cpanel/ea-php84/root/usr/bin/php /opt/alt/php84/usr/bin/php; do
    if [ -x "$candidate" ]; then
        PHP="$candidate"
        break
    fi
done
PHP="${PHP:-$(command -v php)}"

if [ ! -f .env ]; then
    echo "No .env in $(pwd): create it first (deploy/README.md)." >&2
    exit 1
fi

echo "Deploying $(git log -1 --format='%h %s') with $("$PHP" -r 'echo PHP_VERSION;')"

# cPanel clones into a folder only its owner may enter (700): the web
# server has to pass through it to the document root, public/.
chmod 755 .

"$PHP" artisan down --retry=15 || true
trap '"$PHP" artisan up' EXIT

"$PHP" artisan migrate --force

if [ ! -e public/storage ]; then
    "$PHP" artisan storage:link
fi

"$PHP" artisan optimize:clear
"$PHP" artisan optimize
"$PHP" artisan queue:restart
