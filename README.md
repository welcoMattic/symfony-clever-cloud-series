# Symfony sur Clever Cloud

Dépôt d'accompagnement de la série d'articles [Symfony sur Clever Cloud](https://blog.welcomattic.com/tags/serie-symfony-clever/), publiée sur [blog.welcomattic.com](https://blog.welcomattic.com).

*This README is also available in [English](#symfony-on-clever-cloud) below.*

## Comment ce dépôt est organisé

Il n'y a pas de branche principale. **Chaque article de la série a sa branche**, nommée `NN-sujet` où `NN` est le numéro de l'article. Toutes les branches partent du même commit racine et n'ajoutent que ce que leur article décrit, commit par commit, dans l'ordre de lecture. Chaque article indique la branche qui lui correspond.

```bash
git clone https://github.com/welcoMattic/symfony-clever-cloud-series.git
cd symfony-clever-cloud-series
git branch -r
git switch 01-fresh-symfony-app
```

Comparer deux branches montre exactement ce qu'un article ajoute :

```bash
git diff 01-fresh-symfony-app 02-first-deployment
```

## Le point de départ

Le commit racine, commun à toutes les branches, est une application Symfony 8.1 créée avec :

```bash
symfony new --version=8.1 --webapp --docker
```

Sans une ligne ajoutée à la main. Tout ce qui vient ensuite est ajouté, expliqué et justifié par un article.

## Faire tourner l'application en local

Prérequis : PHP 8.4 ou plus récent, Composer, Docker avec Compose, et la [CLI Symfony](https://symfony.com/download).

```bash
composer install
docker compose up -d
symfony console doctrine:migrations:migrate --no-interaction
symfony serve
```

La CLI Symfony découvre le conteneur PostgreSQL et injecte `DATABASE_URL` d'elle-même : il n'y a rien à configurer à la main. Une CI de fumée (`.github/workflows/ci.yaml`) rejoue ces étapes à chaque push, et vérifie que la base répond et que l'application se sert sans erreur 5xx.

## Les workers Messenger (branche `05-workers`)

Pour voir le worker travailler en local, mettez un message dans la file puis consommez-le :

```bash
symfony console app:welcome alice@example.com
symfony console messenger:consume async --limit=1 -vv
```

Relancez les deux commandes avec la même adresse : le message repart bien dans la file et le handler le reconnaît comme un rejeu, sans envoyer un second mail. Le mail se lit dans Mailpit, sur le port que donne `docker compose port mailer 8025`.

En production, ce `messenger:consume` n'est pas lancé à la main : il vit dans une variable d'environnement, et la plateforme le surveille comme un service systemd.

| Variable | Valeur posée ici | À quoi ça sert |
| --- | --- | --- |
| `CC_WORKER_COMMAND` | `php bin/console messenger:consume async --time-limit=3600 --memory-limit=64M` | La commande que la plateforme lance en arrière-plan. Les index `_0`, `_1` permettent d'en déclarer plusieurs. |
| `CC_WORKER_RESTART` | `always` | **Le point qui compte.** Le défaut est `on-failure`, or `--time-limit` fait sortir le worker en code 0 : une sortie propre n'est pas un échec, et le worker ne serait jamais relancé. |
| `CC_WORKER_RESTART_DELAY` | `30` | Le délai avant relance, en secondes, pour tous les workers. Le défaut d'une seconde brûle la limite de redémarrage de systemd quand le worker plante en boucle. |

```bash
clever env set -a symfony-clever-demo CC_WORKER_COMMAND \
  "php bin/console messenger:consume async --time-limit=3600 --memory-limit=64M"
clever env set -a symfony-clever-demo CC_WORKER_RESTART always
clever env set -a symfony-clever-demo CC_WORKER_RESTART_DELAY 30
clever restart -a symfony-clever-demo
```

Gardez `--memory-limit` sous le `memory_limit` de PHP que la plateforme calcule pour la taille du scaler, sinon PHP meurt en erreur fatale avant que Messenger n'ait pu sortir proprement. D'où `64M` et non le `128M` de la documentation de Symfony : la grille commence à 91 Mio sur un Nano. Le worker de cette application démarre à 12 Mio en `prod`, la marge reste large.

## Les tâches récurrentes (branche `06-recurring`)

Le registre d'idempotence posé avec les workers grandit à chaque message traité. La commande `app:handled-messages:purge` en retire les clés de plus de 30 jours, et l'attribut `#[AsCronTask]` la confie au Scheduler de Symfony, chaque nuit à 3 h, heure de Paris :

```bash
symfony console app:handled-messages:purge
symfony console debug:scheduler
symfony console messenger:consume scheduler_default -vv
```

`src/Schedule.php` branche un verrou et un état partagés par toutes les instances. En production, les deux vivent dans l'add-on Redis déjà utilisé pour les sessions (`REDIS_URL`), en local le verrou `flock` et le pool `cache.app` suffisent.

En production, le Scheduler est un worker de plus. `CC_WORKER_COMMAND`, posé pour les workers Messenger, ne bouge pas :

| Variable | Valeur posée ici | À quoi ça sert |
| --- | --- | --- |
| `CC_WORKER_COMMAND_1` | `php bin/console messenger:consume scheduler_default --time-limit=3600 --memory-limit=64M` | Le worker du Scheduler, à côté de celui de la file `async`. Le contrat de redémarrage (`CC_WORKER_RESTART=always`, délai de 30 s) s'applique aux deux. |

```bash
clever env set -a symfony-clever-demo CC_WORKER_COMMAND_1 \
  "php bin/console messenger:consume scheduler_default --time-limit=3600 --memory-limit=64M"
clever restart -a symfony-clever-demo
```

### La même purge, à la demande (Clever Task)

Une Clever Task est une application qui exécute sa commande puis s'arrête. Elle a son propre environnement, et partage la base de l'application web :

```bash
clever create --type php symfony-clever-task \
  --task "php bin/console app:handled-messages:purge" -a symfony-clever-task
clever service -a symfony-clever-task link-addon symfony-clever-demo-db
clever env set -a symfony-clever-task CC_PHP_VERSION 8.4
clever env set -a symfony-clever-task APP_ENV prod
clever env set -a symfony-clever-task APP_SECRET "$(openssl rand -hex 32)"
clever config set cancel-on-push false -a symfony-clever-task
```

Le dernier réglage compte : avec la valeur par défaut d'une application neuve, un second déclenchement pendant qu'une exécution tourne annule cette exécution au lieu d'attendre qu'elle se termine.

Le workflow `.github/workflows/task.yml` la lance depuis l'onglet Actions (`workflow_dispatch`). Il réutilise `CLEVER_TOKEN` et `CLEVER_SECRET`, et attend un secret de plus, `CLEVER_TASK_APP_ID`.

> **Transparence.** Je suis ambassadeur Clever Cloud. J'écris cette série en toute indépendance, personne chez eux ne la relit, et je m'y autorise les mêmes critiques que sur n'importe quelle autre plateforme.

---

# Symfony on Clever Cloud

Companion repository for the [Symfony on Clever Cloud](https://blog.welcomattic.com/tags/serie-symfony-clever/) article series, published on [blog.welcomattic.com](https://blog.welcomattic.com).

*Ce README est aussi disponible en [français](#symfony-sur-clever-cloud) ci-dessus.*

## How this repository is organised

There is no main branch. **Every article in the series has its own branch**, named `NN-topic` where `NN` is the article number. All branches start from the same root commit and only add what their article describes, commit by commit, in reading order. Each article points to its branch.

```bash
git clone https://github.com/welcoMattic/symfony-clever-cloud-series.git
cd symfony-clever-cloud-series
git branch -r
git switch 01-fresh-symfony-app
```

Diffing two branches shows exactly what an article adds:

```bash
git diff 01-fresh-symfony-app 02-first-deployment
```

## The starting point

The root commit, shared by every branch, is a Symfony 8.1 application created with:

```bash
symfony new --version=8.1 --webapp --docker
```

Not a single line added by hand. Everything on top of it is added, explained and justified by an article.

## Running the application locally

Requirements: PHP 8.4 or newer, Composer, Docker with Compose, and the [Symfony CLI](https://symfony.com/download).

```bash
composer install
docker compose up -d
symfony console doctrine:migrations:migrate --no-interaction
symfony serve
```

The Symfony CLI discovers the PostgreSQL container and injects `DATABASE_URL` on its own: there is nothing to wire by hand. A smoke CI (`.github/workflows/ci.yaml`) replays these steps on every push, and checks that the database answers and that the application is served without a 5xx error.

## The Messenger workers (`05-workers` branch)

To watch the worker do its job locally, queue a message and consume it:

```bash
symfony console app:welcome alice@example.com
symfony console messenger:consume async --limit=1 -vv
```

Run both commands again with the same address: the message does go back into the queue, and the handler recognises it as a redelivery instead of sending a second email. The email shows up in Mailpit, on the port that `docker compose port mailer 8025` prints.

In production that `messenger:consume` is not run by hand: it lives in an environment variable, and the platform watches it as a systemd service.

| Variable | Value set here | What it does |
| --- | --- | --- |
| `CC_WORKER_COMMAND` | `php bin/console messenger:consume async --time-limit=3600 --memory-limit=64M` | The command the platform runs in the background. The `_0`, `_1` indexes let you declare several. |
| `CC_WORKER_RESTART` | `always` | **The one that matters.** The default is `on-failure`, yet `--time-limit` makes the worker exit with code 0: a clean exit is not a failure, so the worker would never be restarted. |
| `CC_WORKER_RESTART_DELAY` | `30` | The delay before a restart, in seconds, for every worker. The one-second default burns systemd's restart limit when the worker crashes in a loop. |

```bash
clever env set -a symfony-clever-demo CC_WORKER_COMMAND \
  "php bin/console messenger:consume async --time-limit=3600 --memory-limit=64M"
clever env set -a symfony-clever-demo CC_WORKER_RESTART always
clever env set -a symfony-clever-demo CC_WORKER_RESTART_DELAY 30
clever restart -a symfony-clever-demo
```

Keep `--memory-limit` below the PHP `memory_limit` the platform computes from the scaler size, otherwise PHP dies on a fatal error before Messenger gets a chance to exit cleanly. Hence `64M` rather than the `128M` of the Symfony documentation: the grid starts at 91 MiB on a Nano. This application's worker starts at 12 MiB in `prod`, so there is plenty of room.

## Recurring tasks (`06-recurring` branch)

The idempotency registry added with the workers grows with every handled message. The `app:handled-messages:purge` command removes the keys older than 30 days, and the `#[AsCronTask]` attribute hands it over to the Symfony Scheduler, every night at 3 am, Paris time:

```bash
symfony console app:handled-messages:purge
symfony console debug:scheduler
symfony console messenger:consume scheduler_default -vv
```

`src/Schedule.php` plugs in a lock and a state shared by every instance. In production, both live in the Redis add-on already used for sessions (`REDIS_URL`); locally, the `flock` lock and the `cache.app` pool are enough.

In production, the Scheduler is one more worker. `CC_WORKER_COMMAND`, set for the Messenger workers, stays as it is:

| Variable | Value set here | What it does |
| --- | --- | --- |
| `CC_WORKER_COMMAND_1` | `php bin/console messenger:consume scheduler_default --time-limit=3600 --memory-limit=64M` | The Scheduler worker, next to the `async` one. The restart contract (`CC_WORKER_RESTART=always`, 30 s delay) applies to both. |

```bash
clever env set -a symfony-clever-demo CC_WORKER_COMMAND_1 \
  "php bin/console messenger:consume scheduler_default --time-limit=3600 --memory-limit=64M"
clever restart -a symfony-clever-demo
```

### The same purge, on demand (Clever Task)

A Clever Task is an application that runs its command and then stops. It has its own environment, and shares the web application's database:

```bash
clever create --type php symfony-clever-task \
  --task "php bin/console app:handled-messages:purge" -a symfony-clever-task
clever service -a symfony-clever-task link-addon symfony-clever-demo-db
clever env set -a symfony-clever-task CC_PHP_VERSION 8.4
clever env set -a symfony-clever-task APP_ENV prod
clever env set -a symfony-clever-task APP_SECRET "$(openssl rand -hex 32)"
clever config set cancel-on-push false -a symfony-clever-task
```

The last setting matters: with a new application's default value, a second trigger while a run is in progress cancels that run instead of waiting for it to finish.

The `.github/workflows/task.yml` workflow starts it from the Actions tab (`workflow_dispatch`). It reuses `CLEVER_TOKEN` and `CLEVER_SECRET`, and expects one more secret, `CLEVER_TASK_APP_ID`.

> **Disclosure.** I am a Clever Cloud ambassador. This series is written independently, nobody there reviews it, and I allow myself the same criticism I would apply to any other platform.
