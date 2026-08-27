#!/bin/bash -l
set -euo pipefail

echo "==> Compilation des assets"
php bin/console asset-map:compile --env=prod --no-debug

echo "==> Préchauffage du cache"
php bin/console cache:warmup --env=prod --no-debug

echo "==> Migrations Doctrine"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod
