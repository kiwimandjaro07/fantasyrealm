# FantasyRealm Online - système de gestion de personnages

Projet de l'ECF **Développeur Web et Web Mobile** (Studi) pour PixelVerse Studios (entreprise fictive) :
création, personnalisation (avatar SVG avec aperçu en direct), équipement, partage et modération de personnages.

Réalisé avec l'assistant IA Claude (Anthropic), sous ma direction : voir `docs/gestion-de-projet.pdf`.

## Technologies

PHP 8.3 (MVC sans framework, PDO), MySQL 8, MongoDB 7, HTML/CSS/JavaScript natifs, PHPMailer, PHPUnit, Docker.

## Installation en local

Prérequis : Docker Desktop et Git.

```bash
git clone <url-du-depot> fantasyrealm
cd fantasyrealm
cp .env.example .env              # puis changer les mots de passe
docker compose up -d --build      # premier lancement : quelques minutes
docker compose exec app composer install
```

Au premier démarrage, MySQL exécute `database/01_schema.sql` (création) puis `database/02_donnees.sql` (données de démonstration).
Pour repartir de zéro : `docker compose down -v` puis `docker compose up -d`.

| Service | Adresse |
|---|---|
| Application | http://localhost:8090 |
| E-mails (Mailpit) | http://localhost:8026 |
| phpMyAdmin | http://localhost:8082 |

## Comptes de démonstration

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur | admin@pixelverse-studios.local | Admin#Fantasy2026 |
| Employé | jose@pixelverse-studios.local | Employe#Fantasy2026 |
| Joueurs | lyra@exemple.fr, thorin@exemple.fr, kael@exemple.fr | Joueur#Fantasy2026 |

## Tests

```bash
docker compose exec app vendor/bin/phpunit
```

## Documentation (`docs/`)

- `manuel-utilisation.pdf` : présentation, identifiants, parcours
- `charte-graphique.pdf` : palette, polices, wireframes et maquettes (3 bureau, 3 mobile)
- `gestion-de-projet.pdf` : méthode Kanban, branches Git, planning
- `documentation-technique.pdf` : choix techniques, environnement, MCD, diagrammes, sécurité, déploiement
- `outils/` : scripts Node qui produisent les captures et les PDF (`node docs/outils/captures.mjs`, `node docs/outils/pdf.mjs`)

## Branches Git

`main` (version livrée) ← `develop` (intégration) ← `feature/…` (une branche par fonctionnalité).
