# STORY-060 : Tests de régression post-Sprint 5 + couverture bulk actions

**Epic :** EPIC-012 — Testing et Qualité
**Points :** 3
**Status :** Draft
**Sprint :** 5
**Priorité :** Must Have

---

## User Story

En tant que développeur,
Je veux une suite de tests complète pour les fonctionnalités du Sprint 5,
Afin de garantir la non-régression sur les 360+ tests existants.

---

## Critères d'Acceptation

- [ ] **AC-1** : Tests Feature pour `bulkDelete` — succès (suppression effective), tableau vide, trop d'IDs (>500), IDs invalides/inexistants
- [ ] **AC-2** : Tests Feature pour `bulkEdit` — chaque champ modifiable : `published_at`, `status`, `is_indexed`, `is_dofollow`
- [ ] **AC-3** : Tests Feature pour `bulkEdit` — validation : champ invalide refusé, valeur invalide pour le champ refusée
- [ ] **AC-4** : Tests pour `DashboardController::chartData()` — présence des clés `perfect`, `not_indexed`, `nofollow` dans la réponse JSON (si non déjà couverts)
- [ ] **AC-5** : Suite complète ≥ 360 tests passants sans aucune régression

---

## Dev Notes

### Contexte et dépendances

- **STORY-050** ✅ (2026-02-18) : `BacklinkController@bulkDelete()` et `@bulkEdit()` ajoutés, routes `POST /backlinks/bulk-delete` et `POST /backlinks/bulk-edit` en place, AlpineJS `bulkActions()` dans l'UI.
- **STORY-051** ✅ (2026-02-18) : `DashboardController::chartData()` enrichi avec clés `perfect`, `not_indexed`, `nofollow`, `gained`, `lost`, `delta`.
- **STORY-054** ✅ (2026-02-18) : Champ `is_indexed` boolean nullable sur table `backlinks`, champ `published_at` date nullable.

### Implémentation à tester

#### BacklinkController — bulkDelete (ligne 590-600)

```php
// POST /backlinks/bulk-delete
// Route name: backlinks.bulk-delete
public function bulkDelete(Request $request)
{
    $request->validate([
        'ids'   => 'required|array|min:1|max:500',
        'ids.*' => 'integer|exists:backlinks,id',
    ]);

    $deleted = Backlink::whereIn('id', $request->ids)->delete();

    return redirect()->back()->with('success', "{$deleted} backlink(s) supprimé(s).");
}
```

**Comportements attendus :**
- Succès → redirect avec flash `success` contenant le nombre supprimé
- `ids` vide → validation échoue (`ids` required)
- `ids` avec plus de 500 éléments → validation échoue (`max:500`)
- `ids` avec IDs inexistants → validation échoue (`exists:backlinks,id`)

#### BacklinkController — bulkEdit (ligne 626-654)

```php
// POST /backlinks/bulk-edit
// Route name: backlinks.bulk-edit
public function bulkEdit(Request $request)
{
    $request->validate([
        'ids'   => 'required|array|min:1|max:500',
        'ids.*' => 'integer|exists:backlinks,id',
        'field' => 'required|in:published_at,status,is_indexed,is_dofollow',
        'value' => 'nullable|string|max:20',
    ]);

    $update = match ($field) {
        'published_at' => ['published_at' => $value ?: null],
        'status'       => in_array($value, ['active', 'lost', 'changed']) ? ['status' => $value] : null,
        'is_indexed'   => ['is_indexed' => $value === '1' ? true : ($value === '0' ? false : null)],
        'is_dofollow'  => ['is_dofollow' => $value === '1'],
        default        => null,
    };

    if ($update === null) {
        return redirect()->back()->withErrors(['field' => 'Valeur invalide.']);
    }

    $updated = Backlink::whereIn('id', $request->ids)->update($update);

    return redirect()->back()->with('success', "{$updated} backlink(s) mis à jour.");
}
```

**Comportements attendus :**
- `field=published_at`, `value='2024-01-15'` → `published_at` mis à jour en DB
- `field=published_at`, `value=null` → `published_at` mis à `null` en DB
- `field=status`, `value='lost'` → `status` mis à `'lost'`
- `field=status`, `value='invalide'` → redirect back avec erreur `field`
- `field=is_indexed`, `value='1'` → `is_indexed = true`
- `field=is_indexed`, `value='0'` → `is_indexed = false`
- `field=is_indexed`, `value=null` → `is_indexed = null`
- `field=is_dofollow`, `value='1'` → `is_dofollow = true`
- `field=is_dofollow`, `value='0'` → `is_dofollow = false`
- `field=invalide` → validation Laravel échoue (not in enum)

#### DashboardController::chartData — nouvelles clés Sprint 5

`DashboardChartsTest` existant (12 tests) couvre déjà les clés `labels`, `active`, `perfect`, `not_indexed`, `nofollow`, `gained`, `lost`, `delta`. Vérifier que ces tests passent toujours et éventuellement ajouter des tests de valeurs pour `perfect`, `not_indexed`, `nofollow` (backlinks actifs+indexés+dofollow pour `perfect`, etc.).

**Clé `perfect` :** backlink `status=active` + `is_indexed=true` + `is_dofollow=true`
**Clé `not_indexed` :** backlink `status=active` + `is_indexed=false`
**Clé `nofollow` :** backlink `status=active` + `is_dofollow=false`

### Fichiers de test existants à connaître

| Fichier | Tests existants | Pertinence |
|---------|----------------|------------|
| `tests/Feature/BacklinkControllerTest.php` | 13 tests (CRUD, validations) | Pattern à suivre pour les nouveaux tests |
| `tests/Feature/DashboardChartsTest.php` | 12 tests (chart endpoint) | À compléter pour clés Sprint 5 |
| `tests/Feature/BacklinkCsvImportTest.php` | 20 tests | Pattern d'import de référence |
| `tests/Feature/RateLimitingTest.php` | 8 tests | Pattern throttle |

### Fichier à créer

```
tests/Feature/BacklinkBulkActionsTest.php
```

### Conventions du projet (coding-standards.md style)

- Namespace : `Tests\Feature`
- Extend : `Tests\TestCase`
- Trait : `Illuminate\Foundation\Testing\RefreshDatabase`
- Auth : utiliser `$this->actingAs($user)` ou test sans auth (les routes bulk n'ont pas de middleware auth explicite basé sur le code existant — vérifier)
- Assertions : `assertRedirect`, `assertSessionHas`, `assertSessionHasErrors`, `assertDatabaseHas`, `assertDatabaseMissing`, `assertDatabaseCount`
- Factories : `Backlink::factory()->create(...)`, `Project::factory()->create()`, `User::factory()->create()`

### Vérifier la protection auth des routes bulk

À vérifier dans `routes/web.php` : les routes `backlinks.bulk-delete` et `backlinks.bulk-edit` sont-elles dans le groupe `auth` middleware ? D'après le pattern des autres routes backlinks, probablement oui. Si c'est le cas, les tests doivent utiliser `$this->actingAs($user)`.

### Nombre de tests total attendu

Sprint 5 démarré avec ~347 tests (d'après sprint-status.yaml sprint 4). Après STORY-050 à 054 déjà implémentées, le total actuel est à vérifier. L'objectif est ≥ 360 tests.

---

## Tasks / Subtasks

### Task 1 — Vérifier l'état actuel des tests (AC-5)
- [ ] 1.1 Exécuter `php artisan test` et noter le nombre total de tests passants
- [ ] 1.2 Identifier les éventuelles régressions existantes à corriger avant d'ajouter de nouveaux tests

### Task 2 — Créer `tests/Feature/BacklinkBulkActionsTest.php` (AC-1, AC-2, AC-3)

- [ ] 2.1 Créer la classe `BacklinkBulkActionsTest` avec `RefreshDatabase`
- [ ] 2.2 Écrire `setUp()` : créer un user, appeler `$this->actingAs($user)`

**Tests bulkDelete (AC-1) :**
- [ ] 2.3 `test_bulk_delete_succeeds_with_valid_ids` — créer 3 backlinks, POST avec leurs IDs, assertRedirect + assertDatabaseMissing + flash success
- [ ] 2.4 `test_bulk_delete_fails_with_empty_ids` — POST `ids=[]`, assertSessionHasErrors('ids')
- [ ] 2.5 `test_bulk_delete_fails_with_too_many_ids` — POST avec 501 IDs fictifs, assertSessionHasErrors('ids')
- [ ] 2.6 `test_bulk_delete_fails_with_invalid_ids` — POST avec IDs inexistants (ex: 99999), assertSessionHasErrors
- [ ] 2.7 `test_bulk_delete_returns_count_in_flash` — vérifier que le message flash contient le bon nombre

**Tests bulkEdit — published_at (AC-2) :**
- [ ] 2.8 `test_bulk_edit_published_at_with_valid_date` — assertDatabaseHas `published_at = '2024-01-15 00:00:00'`
- [ ] 2.9 `test_bulk_edit_published_at_with_null_clears_value` — assertDatabaseHas `published_at = null`

**Tests bulkEdit — status (AC-2) :**
- [ ] 2.10 `test_bulk_edit_status_to_lost` — assertDatabaseHas `status = 'lost'`
- [ ] 2.11 `test_bulk_edit_status_to_active` — assertDatabaseHas `status = 'active'`
- [ ] 2.12 `test_bulk_edit_status_invalid_value_returns_error` — POST `field=status, value='invalide'`, assertRedirect + assertSessionHasErrors('field')

**Tests bulkEdit — is_indexed (AC-2) :**
- [ ] 2.13 `test_bulk_edit_is_indexed_to_true` — value='1', assertDatabaseHas `is_indexed = 1`
- [ ] 2.14 `test_bulk_edit_is_indexed_to_false` — value='0', assertDatabaseHas `is_indexed = 0`
- [ ] 2.15 `test_bulk_edit_is_indexed_to_null` — value=null, assertDatabaseHas `is_indexed = null`

**Tests bulkEdit — is_dofollow (AC-2) :**
- [ ] 2.16 `test_bulk_edit_is_dofollow_to_true` — value='1', assertDatabaseHas `is_dofollow = 1`
- [ ] 2.17 `test_bulk_edit_is_dofollow_to_false` — value='0', assertDatabaseHas `is_dofollow = 0`

**Tests bulkEdit — validations générales (AC-3) :**
- [ ] 2.18 `test_bulk_edit_fails_with_invalid_field` — POST `field='couleur'`, assertSessionHasErrors('field')
- [ ] 2.19 `test_bulk_edit_fails_with_empty_ids` — assertSessionHasErrors('ids')

### Task 3 — Compléter DashboardChartsTest pour les clés Sprint 5 (AC-4)

- [ ] 3.1 Vérifier que `test_chart_data_endpoint_returns_json` inclut `perfect`, `not_indexed`, `nofollow` dans `assertJsonStructure` — c'est déjà le cas d'après le test existant (ligne 37)
- [ ] 3.2 Ajouter `test_chart_data_counts_perfect_backlinks` — créer backlinks `active+is_indexed=true+is_dofollow=true`, vérifier que `max($data['perfect']) >= 1`
- [ ] 3.3 Ajouter `test_chart_data_counts_not_indexed_backlinks` — créer backlinks `active+is_indexed=false`, vérifier `max($data['not_indexed']) >= 1`
- [ ] 3.4 Ajouter `test_chart_data_counts_nofollow_backlinks` — créer backlinks `active+is_dofollow=false`, vérifier `max($data['nofollow']) >= 1`

### Task 4 — Valider la non-régression complète (AC-5)

- [ ] 4.1 Exécuter `php artisan test` et vérifier ≥ 360 tests passants
- [ ] 4.2 S'assurer que 0 test est en échec (aucune régression)
- [ ] 4.3 Reporter le total final dans les notes de cette story

---

## Notes de Structure Projet

- **Fichier à créer :** `app-laravel/tests/Feature/BacklinkBulkActionsTest.php`
- **Fichier à compléter :** `app-laravel/tests/Feature/DashboardChartsTest.php`
- **Controller de référence :** `app-laravel/app/Http/Controllers/BacklinkController.php` (méthodes `bulkDelete` ligne 590, `bulkEdit` ligne 626)
- **Routes :** `app-laravel/routes/web.php` (lignes 55-56 pour les routes bulk)

---

## Contexte Story Précédente (STORY-059 non démarrée, STORY-054 dernière complétée)

STORY-054 a ajouté les champs `is_indexed` (boolean nullable) et `published_at` (date nullable) sur la table `backlinks`. Ces champs sont utilisés dans les tests `bulkEdit` de cette story. Le modèle `Backlink` a `is_indexed` et `published_at` dans `$fillable` et `$casts`.

---

## Validation Checklist (story-draft-checklist)

| Catégorie | Statut | Notes |
|-----------|--------|-------|
| 1. Goal & Context Clarity | PASS | Story clairement liée à EPIC-012, dépendances STORY-050/051/054 explicites |
| 2. Technical Implementation Guidance | PASS | Signatures de méthodes, chemins de fichiers, comportements attendus documentés |
| 3. Reference Effectiveness | PASS | Références au code source avec numéros de ligne |
| 4. Self-Containment Assessment | PASS | Code à tester reproduit inline, cas limites explicites |
| 5. Testing Guidance | PASS | Chaque test listé avec nom, données, assertions attendues |

**Résultat final : READY** — La story fournit suffisamment de contexte pour qu'un agent développeur puisse implémenter tous les tests sans recherche supplémentaire.

---

## Définition de Terminé

- [ ] `BacklinkBulkActionsTest.php` créé avec ≥ 17 tests
- [ ] `DashboardChartsTest.php` complété avec 3 tests supplémentaires pour clés Sprint 5
- [ ] `php artisan test` passe sans régression
- [ ] Total ≥ 360 tests passants
- [ ] Story status mis à jour en `completed` dans `sprint-status.yaml`
