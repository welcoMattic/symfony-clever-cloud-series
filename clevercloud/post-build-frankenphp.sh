#!/bin/bash -l
set -euo pipefail

# Le runtime frankenphp n'exécute pas les auto-scripts de Flex : la commande que
# le composer.json déclarait doit être rejouée ici, avant la compilation des
# assets qui en dépend.
echo "==> Installation des paquets de l'importmap"
php bin/console importmap:install

echo "==> Compilation des assets"
php bin/console asset-map:compile --env=prod --no-debug

echo "==> Préchauffage du cache"
php bin/console cache:warmup --env=prod --no-debug

echo "==> Migrations Doctrine"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod
