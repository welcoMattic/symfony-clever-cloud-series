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

> **Disclosure.** I am a Clever Cloud ambassador. This series is written independently, nobody there reviews it, and I allow myself the same criticism I would apply to any other platform.
