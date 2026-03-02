# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**LinkTracker** - Application Laravel de suivi et monitoring de backlinks SEO.

Le projet permet de :
- Surveiller des backlinks (liens entrants) vers vos sites web
- Vérifier automatiquement la présence et les attributs des liens
- Détecter les changements (ancre modifiée, passage en nofollow, perte du lien)
- Recevoir des alertes en cas de problème
- Suivre l'historique des vérifications avec taux de disponibilité

## Architecture

### Stack technique
- **Backend** : Laravel 10.x (PHP 8.1+)
- **Database** : SQLite (dev) / PostgreSQL (production)
- **Frontend** : Blade templates + AlpineJS + TailwindCSS
- **Queue** : Database driver (avec support Redis)
- **Scheduler** : Laravel Task Scheduling (cron)

### Structure du projet

```
app-laravel/
├── app/
│   ├── Console/Commands/      # Commandes Artisan
│   ├── Http/Controllers/      # Controllers MVC
│   ├── Jobs/                  # Jobs de queue (CheckBacklinkJob)
│   ├── Models/                # Modèles Eloquent
│   └── Services/              # Services métier (BacklinkCheckerService, AlertService)
├── database/migrations/       # Migrations de base de données
├── resources/views/           # Templates Blade
├── routes/web.php            # Routes de l'application
└── docs/                     # Documentation technique
```

## Modèles principaux

### 1. Project
Projet de suivi (site web à monitorer)
- Nom, URL, description
- Has many Backlinks

### 2. Backlink
Lien entrant à surveiller
- `source_url` : URL de la page contenant le lien
- `target_url` : URL cible du lien (votre site)
- `anchor_text` : Texte d'ancre du lien
- `status` : active, lost, changed
- `tier_level` : tier1 (lien direct) ou tier2 (lien vers tier1)
- `spot_type` : external (site tiers) ou internal (PBN)
- Relations : project, platform, checks, alerts

### 3. BacklinkCheck
Historique des vérifications d'un backlink
- `checked_at`, `is_present`, `http_status`, `error_message`
- Permet de calculer le taux de disponibilité

### 4. Alert
Alertes générées automatiquement
- Types : `backlink_lost`, `backlink_changed`, `backlink_recovered`
- Sévérité : critical, high, medium, low
- `is_read`, `read_at`

### 5. Platform
Plateformes d'achat de backlinks (ex: SEMrush, Ahrefs Marketplace)

### 6. Order
Commande de backlink auprès d'une plateforme
- `project_id`, `platform_id`, `source_url`, `target_url`
- `status` : pending, in_progress, published, cancelled, rejected
- `ordered_at`, `expected_at`, `published_at`, `price`
- Workflow : statut `published` → création automatique d'un Backlink (STORY-036)
- Has many OrderStatusLogs

### 7. OrderStatusLog
Historique des changements de statut d'une commande (STORY-037)
- `order_id`, `old_status`, `new_status`, `notes`, `changed_at`
- Créé automatiquement à chaque `PATCH /orders/{id}/status`

### 8. DomainMetric
Métriques SEO par domaine (STORY-025)
- `domain`, `domain_authority`, `spam_score`, `last_fetched_at`
- `DomainMetric::forDomain($url)` pour upsert par domaine extrait

## Services principaux

### BacklinkCheckerService
Service de vérification des backlinks :
- Requête HTTP vers `source_url`
- Parse HTML avec DOMDocument/DOMXPath
- Trouve le lien vers `target_url`
- Extrait ancre, rel attributes, détecte dofollow/nofollow
- Protection SSRF via UrlValidator

### AlertService
Service de gestion des alertes :
- `createBacklinkLostAlert()` - Lien perdu
- `createBacklinkChangedAlert()` - Attributs modifiés
- `createBacklinkRecoveredAlert()` - Lien récupéré
- Logique intelligente de sévérité (tier, prix, type de changement)
- Notifications email pour alertes critical/high

### SeoMetricService
Service métriques SEO (Domain Authority, Spam Score) :
- Pattern Strategy avec providers : CustomSeoProvider, MozSeoProvider
- Upsert dans `domain_metrics` par domaine
- Configuration via Settings (clé `seo_provider`)

### BacklinkCsvImportService
Import de backlinks depuis CSV :
- Colonnes : source_url, target_url, anchor_text, project_id, tier_level, spot_type
- Gestion doublons et erreurs (ImportResult)

### UrlValidator (Sécurité SSRF)
Validation des URLs pour prévenir SSRF :
- Bloque IPs privées RFC 1918, loopback, link-local
- Résolution DNS pour domaines
- Lève `SsrfException` si URL dangereuse
- Voir `docs/SERVICES.md` pour documentation complète

## Jobs et automatisation

### CheckBacklinkJob
Job de queue pour vérifier un backlink :
- Appelle BacklinkCheckerService
- Crée un BacklinkCheck
- Met à jour le statut du backlink
- Crée des alertes via AlertService
- Retry : 3 tentatives, timeout 120s

### Scheduler (Cron)
Planification automatique :
- **Quotidien (2h)** : backlinks non vérifiés depuis 24h
- **Hebdomadaire (dimanche 3h)** : tous backlinks non vérifiés depuis 7j

Commande : `php artisan app:check-backlinks --frequency=daily`

## Commandes Artisan

```bash
# Vérifier tous les backlinks (batch)
php artisan app:check-backlinks --frequency=daily
php artisan app:check-backlinks --project=1 --limit=50

# Vérifier un backlink spécifique
php artisan app:check-backlink 42 --verbose

# Statut de la queue (STORY-042)
php artisan app:queue-status
php artisan app:queue-status --failed
php artisan app:queue-status --reset-failed

# Rafraîchir métriques SEO
php artisan app:refresh-seo-metrics --force

# Lancer le worker de queue
php artisan queue:work --verbose

# Simuler le cron (dev)
php artisan schedule:work
```

## Routes principales

```
GET  /dashboard                → DashboardController@index
GET  /api/dashboard/chart      → DashboardController@chartData (JSON)
GET  /projects                 → ProjectController (resource)
GET  /projects/{id}/report     → ProjectController@report (rapport HTML imprimable)
GET  /backlinks                → BacklinkController (resource)
POST /backlinks/{id}/check     → BacklinkController@check (vérif. manuelle, throttle 10/min)
POST /backlinks/{id}/seo-metrics → BacklinkController@refreshSeoMetrics (throttle 3/min)
GET  /backlinks/import         → BacklinkController@importForm
POST /backlinks/import         → BacklinkController@importCsv (throttle 5/min)
GET  /backlinks/export         → BacklinkController@exportCsv
GET  /alerts                   → AlertController@index
GET  /platforms                → PlatformController (resource)
GET  /orders                   → OrderController (resource)
PATCH /orders/{id}/status      → OrderController@updateStatus
GET  /settings                 → SettingsController@index
GET  /settings/webhook         → WebhookSettingsController@show
GET  /profile                  → ProfileController@show
PATCH /profile/password        → ProfileController@updatePassword
```

Documentation complète : voir `docs/API.md`

## Conventions de code

### Nommage
- **Models** : Singulier PascalCase (Backlink, BacklinkCheck)
- **Controllers** : Resource controllers (BacklinkController)
- **Services** : Suffixe "Service" (BacklinkCheckerService)
- **Jobs** : Suffixe "Job" (CheckBacklinkJob)
- **Migrations** : snake_case avec timestamp

### Base de données
- Tables : pluriel snake_case (backlinks, backlink_checks)
- Colonnes : snake_case
- Foreign keys : `{model}_id` (project_id, backlink_id)
- Timestamps : `created_at`, `updated_at`

### Blade
- Composants : kebab-case (`<x-page-header>`)
- Layouts : resources/views/layouts/app.blade.php
- Pages : resources/views/pages/{resource}/{action}.blade.php

## Méthodologie de développement

### BMAD (Benchmark, Model, Analyze, Deliver)
Toujours suivre cette méthodologie pour les nouvelles fonctionnalités :

1. **Benchmark** : Analyser l'existant (grep, read files, comprendre le code)
2. **Model** : Concevoir la solution (structure, classes, relations)
3. **Analyze** : Identifier les dépendances et impacts
4. **Deliver** : Implémenter avec tests et documentation

### Git
- Commits détaillés avec type (feat, fix, refactor, docs)
- Co-Author : Claude Sonnet 4.5
- Branches : master (stable), feature/* (développement)

## Documentation

### Fichiers de documentation
- `docs/API.md` : Documentation complète des routes et endpoints (STORY-049)
- `docs/SERVICES.md` : Documentation des services métier (STORY-049)
- `docs/QUEUES.md` : Guide complet du système de queues
- `docs/EPIC-JOBS-VERIFICATION.md` : Documentation EPIC vérification backlinks
- `docs/sprint-status.yaml` : Suivi de progression des sprints BMAD
- `CLAUDE.md` : Ce fichier (guidance pour Claude Code)

### EPICs complétés (Sprints 1-4)
- ✅ EPIC-001 : Setup infrastructure (Herd, SQLite, migrations)
- ✅ EPIC-002 : Authentification Sanctum (login, logout, profil)
- ✅ EPIC-003 : Gestion projets et backlinks (CRUD complet)
- ✅ EPIC-004 : Système d'alertes (lost, changed, recovered)
- ✅ EPIC-005 : Métriques SEO (Domain Authority, Moz provider)
- ✅ EPIC-006 : Marketplace et commandes (Orders CRUD, Order → Backlink auto, timeline statut)
- ✅ EPIC-007 : Reporting (rapport HTML imprimable par projet)
- ✅ EPIC-008 : Configuration et settings (monitoring, SEO, webhook, profil)
- ✅ EPIC-009 : Performance (indexes DB, caching dashboard, queue monitoring)
- ✅ EPIC-010 : Sécurité (audit SSRF UrlValidator, rate limiting avancé)
- ✅ EPIC-011 : UI/UX (navigation Orders + Import, flash messages auto-dismiss, pagination tri)
- ✅ EPIC-012 : Qualité (tests 339/339, documentation API/Services)
- ✅ EPIC-013 : SaaS UI Redesign (Blade + TailwindCSS v4)

### EPICs optionnelles — NE PAS DÉVELOPPER sans demande explicite
> Ces idées sont notées pour référence future uniquement. **Ne jamais les implémenter proactivement.**
- 💡 Import/export avancé (XLSX, filtres plus riches)
- 💡 Multi-utilisateurs et organisations
- 💡 Webhooks sortants (Slack, Discord)

## Configuration environnement

### Développement
```env
APP_ENV=local
DB_CONNECTION=sqlite
QUEUE_CONNECTION=sync  # Synchrone pour dev
```

### Production
```env
APP_ENV=production
DB_CONNECTION=pgsql
QUEUE_CONNECTION=database  # Asynchrone avec worker
```

### Activation scheduler (production)
```bash
# Crontab
* * * * * cd /path/to/app-laravel && php artisan schedule:run
```

### Worker queues (production)
```bash
# Avec Supervisor (voir docs/QUEUES.md)
php artisan queue:work database --sleep=3 --tries=3
```

## Tests et qualité

### Sécurité
- Protection SSRF dans BacklinkCheckerService
- Validation stricte des URLs (UrlValidator)
- Rate limiting sur routes sensibles (5/min pour vérifications manuelles)
- Échappement SQL dans filtres (wildcards)

### Performance
- Indexes sur colonnes filtrées (status, project_id, tier_level)
- Eager loading (with) pour éviter N+1
- Pagination (20 items/page)
- Queue asynchrone pour vérifications

## Notes importantes

- **Ne jamais** créer de commits sans instructions explicites
- **Toujours** utiliser la méthodologie BMAD
- **Toujours** documenter les EPICs dans docs/
- **Rate limiting** : respecter les limites sur vérifications externes
- **Logs** : utiliser Log::info/warning/error pour traçabilité
