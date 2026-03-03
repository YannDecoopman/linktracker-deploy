# STORY-062 : Migration + Modèle SourceDomain

**Epic :** EPIC-014 — Catalogue de Domaines Sources
**Points :** 3
**Status :** Draft
**Sprint :** 6
**Priorité :** Must Have

---

## User Story

En tant que développeur,
Je veux une table `source_domains` et un modèle Eloquent `SourceDomain` avec ses relations,
Afin de disposer de la fondation de données nécessaire au catalogue de domaines sources.

---

## Critères d'Acceptation

- [ ] **AC-1** : Migration `create_source_domains_table` créée et exécutable — colonnes : `id`, `domain` (string unique), `first_seen_at` (timestamp), `last_synced_at` (timestamp nullable), `notes` (text nullable), `created_at`, `updated_at`
- [ ] **AC-2** : Index unique sur `domain`
- [ ] **AC-3** : Modèle `App\Models\SourceDomain` avec `$fillable`, `$casts`, relation `domainMetric()` (hasOne via `domain`)
- [ ] **AC-4** : Méthode statique `SourceDomain::fromUrl(string $url): self` qui extrait le domaine depuis une URL (strip `www.`, lowercase) et fait un `firstOrCreate` avec `first_seen_at = now()`
- [ ] **AC-5** : `php artisan migrate` s'exécute sans erreur sur la base de développement (SQLite)

---

## Dev Notes

### Contexte et dépendances

- **EPIC-014** planifié (2026-03-03) — STORY-062 est la première story de cet epic
- **DomainMetric** (existant) : table `domain_metrics`, colonne `domain` (string, unique). La méthode `DomainMetric::extractDomain($url)` normalise déjà un domaine depuis une URL (strip `www.`, lowercase) — **réutiliser cette logique**
- **BacklinkObserver** (existant, `app/Observers/BacklinkObserver.php`) : actuellement invalide le cache dashboard sur created/updated/deleted. Sera étendu dans STORY-063 pour alimenter `source_domains` — ne pas modifier dans cette story
- Pas de colonne métriques SEO dans `source_domains` — les métriques restent dans `domain_metrics` (relation via `domain` string)

### Structure de la table `source_domains`

```
source_domains
├── id                  (bigIncrements)
├── domain              (string, unique) — ex: "exemple.com" (sans www., lowercase)
├── first_seen_at       (timestamp)      — date de première apparition
├── last_synced_at      (timestamp, nullable) — date dernière sync depuis backlinks
├── notes               (text, nullable)
├── created_at          (timestamp)
└── updated_at          (timestamp)
```

### Modèle SourceDomain

**Fichier à créer :** `app/Models/SourceDomain.php`

```php
namespace App\Models;

class SourceDomain extends Model
{
    protected $fillable = [
        'domain',
        'first_seen_at',
        'last_synced_at',
        'notes',
    ];

    protected $casts = [
        'first_seen_at'  => 'datetime',
        'last_synced_at' => 'datetime',
    ];
}
```

**Relation `domainMetric()`** — hasOne via clé étrangère personnalisée (les deux tables utilisent `domain` comme clé de jointure, pas `id`) :

```php
public function domainMetric(): \Illuminate\Database\Eloquent\Relations\HasOne
{
    return $this->hasOne(DomainMetric::class, 'domain', 'domain');
}
```

**Méthode `fromUrl()`** — extraction et upsert :

```php
public static function fromUrl(string $url): self
{
    $domain = DomainMetric::extractDomain($url); // réutilise la normalisation existante

    return static::firstOrCreate(
        ['domain' => $domain],
        ['first_seen_at' => now()]
    );
}
```

### Normalisation du domaine

Règle : utiliser **exactement** `DomainMetric::extractDomain($url)` pour garantir la cohérence avec la table `domain_metrics` :
```php
// Dans DomainMetric::extractDomain() (existant) :
$host = parse_url($url, PHP_URL_HOST) ?? $url;
return preg_replace('/^www\./', '', strtolower($host));
// Exemple : "https://www.EXEMPLE.com/page" → "exemple.com"
```

### Conventions migrations du projet

- Fichier de migration existant le plus récent : `2026_03_02_155609_fix_user_settings_defaults.php`
- La nouvelle migration sera donc nommée : `2026_03_XX_XXXXXX_create_source_domains_table.php`
- Commande : `php artisan make:migration create_source_domains_table`
- Pattern existant (voir autres migrations) : `Schema::create('source_domains', function (Blueprint $table) { ... })`

### Fichiers à créer

```
app-laravel/
├── app/Models/SourceDomain.php
└── database/migrations/YYYY_MM_DD_HHMMSS_create_source_domains_table.php
```

### Testing Requirements

Pas de tests Feature dans cette story (couverture dans STORY-069). Seul test minimal : `php artisan migrate` passe (AC-5).

---

## Tasks / Subtasks

### Task 1 — Créer la migration (AC-1, AC-2, AC-5)

- [ ] 1.1 Exécuter `php artisan make:migration create_source_domains_table`
- [ ] 1.2 Remplir le `up()` : `$table->id()`, `$table->string('domain')->unique()`, `$table->timestamp('first_seen_at')`, `$table->timestamp('last_synced_at')->nullable()`, `$table->text('notes')->nullable()`, `$table->timestamps()`
- [ ] 1.3 Remplir le `down()` : `Schema::dropIfExists('source_domains')`
- [ ] 1.4 Exécuter `php artisan migrate` et vérifier que ça passe sans erreur

### Task 2 — Créer le modèle SourceDomain (AC-3, AC-4)

- [ ] 2.1 Créer `app/Models/SourceDomain.php` avec namespace, imports, `$fillable`, `$casts`
- [ ] 2.2 Ajouter la relation `domainMetric()` : `hasOne(DomainMetric::class, 'domain', 'domain')`
- [ ] 2.3 Ajouter la méthode statique `fromUrl(string $url): self` avec `DomainMetric::extractDomain()` + `firstOrCreate`

### Task 3 — Vérification finale (AC-5)

- [ ] 3.1 Exécuter `php artisan migrate:fresh` pour vérifier que la migration est idempotente
- [ ] 3.2 Vérifier que `php artisan tinker` peut instancier `SourceDomain::fromUrl('https://www.example.com/page')` et retourne un objet avec `domain = 'example.com'`

---

## Notes de Structure Projet

- **Modèle :** `app-laravel/app/Models/SourceDomain.php`
- **Migration :** `app-laravel/database/migrations/YYYY_MM_DD_XXXXXX_create_source_domains_table.php`
- **Relation DomainMetric :** clé étrangère non-standard (`domain` → `domain`), pas `source_domain_id`
- **Pas de factory** dans cette story (ajoutée dans STORY-069 si nécessaire pour les tests)

---

## Validation Checklist (story-draft-checklist)

| Catégorie | Statut | Notes |
|-----------|--------|-------|
| 1. Goal & Context Clarity | PASS | Story clairement liée à EPIC-014, première brick fondationnelle |
| 2. Technical Implementation Guidance | PASS | Structure table, code modèle et méthode fromUrl() documentés |
| 3. Reference Effectiveness | PASS | Réutilisation DomainMetric::extractDomain() explicite |
| 4. Self-Containment Assessment | PASS | Aucune dépendance sur d'autres stories non terminées |
| 5. Testing Guidance | PASS | AC-5 vérifiable avec migrate + tinker |

**Résultat final : READY**

---

## Définition de Terminé

- [ ] Migration `create_source_domains_table` créée et exécutée sans erreur
- [ ] Modèle `SourceDomain` créé avec `$fillable`, `$casts`, `domainMetric()`, `fromUrl()`
- [ ] `php artisan migrate` passe sur SQLite (dev)
- [ ] Story status mis à jour en `completed`
