# EPIC-014 : Catalogue de Domaines Sources

**Status:** Planifié
**Priority:** High
**Epic Type:** Feature / Business Intelligence
**Estimated Points:** 34-44 points
**Estimated Stories:** 8-11 stories
**Dependencies:** EPIC-005 (DomainMetric existant), EPIC-006 (Orders existant), EPIC-013 (UI SaaS en place)

---

## Vue d'Ensemble

Créer un module de gestion des **domaines sources** de backlinks : une liste centralisée de tous les sites qui pointent vers les projets de l'utilisateur, enrichie de métriques SEO via DataforSEO, avec une page détail par domaine et un tableau de couverture croisée projets × domaines.

**Problème :**
Aujourd'hui, l'utilisateur achète des liens depuis de nombreux domaines externes, mais n'a aucune vue consolidée de ces domaines : impossible de savoir rapidement quels domaines ont déjà été exploités pour quel projet, à quel prix, ni quelle est leur qualité SEO. Il doit croiser manuellement les données backlinks / projets.

**Solution :**
Un onglet "Domaines" dédié dans la sidebar, alimenté automatiquement depuis les `source_url` des backlinks, avec enrichissement DataforSEO (DA, DR, spam score, referring domains, organic keywords), une page détail par domaine, et une matrice de couverture projets pour guider les futures décisions de netlinking.

---

## Objectifs Business

1. **Centraliser la connaissance des domaines partenaires**
   - Vision unifiée de tous les sites depuis lesquels des backlinks ont été achetés
   - Détection immédiate des domaines déjà utilisés vs nouveaux

2. **Évaluer la qualité des domaines**
   - Intégration DataforSEO : DA, DR, spam score, referring domains, organic keywords
   - Indicateur de tendance prix (dernier achat vs avant-dernier)

3. **Optimiser la stratégie de netlinking**
   - Matrice couverture : quel domaine → quels projets déjà liés / pas encore liés
   - Aide à la décision pour les futures commandes de liens

4. **Accélérer les futures workflows de commandes en masse**
   - Base de données domaines qualifiés prête à être exploitée dans un futur module "commandes en masse"

---

## Périmètre Fonctionnel

### IN SCOPE
- Modèle `SourceDomain` avec extraction automatique depuis `backlinks.source_url`
- Synchronisation automatique : chaque nouveau/modifié backlink met à jour la liste des domaines
- Ajout manuel d'un domaine (nom de domaine uniquement → fetch métriques auto)
- Intégration DataforSEO (nouveau provider) via le pattern Strategy `SeoMetricService` existant
- Suppression de Moz comme provider actif (remplacé par DataforSEO)
- Page liste `/domains` avec filtres et métriques SEO
- Page détail `/domains/{domain}` avec :
  - Métriques SEO du domaine (DA, DR, spam, referring domains, organic keywords)
  - Matrice couverture : tableau Projets × Domaine (lié / non lié)
  - Liste des backlinks achetés sur ce domaine (tableaux avec prix, date, statut)
  - Indicateur prix dernier achat vs avant-dernier (↑↓ ou "premier achat")
- Onglet "Domaines" dans la sidebar principale (navigation au même niveau que Backlinks)
- Commande Artisan `app:sync-source-domains` pour synchronisation batch initiale
- Job `FetchDomainMetricsJob` via queue pour enrichissement asynchrone

### OUT OF SCOPE
- Module "commandes en masse" depuis les domaines (feature séparée future)
- Gestion de contrats/tarifs négociés au niveau domaine
- Import CSV de domaines
- Alertes automatiques sur dégradation métriques domaine

---

## Architecture Technique

### Nouveau modèle : `SourceDomain`

```
source_domains
├── id
├── domain              (string, unique) — ex: "exemple.com"
├── first_seen_at       (timestamp)
├── last_synced_at      (timestamp, nullable)
├── notes               (text, nullable)
├── created_at / updated_at
```

Pas de colonne métriques SEO dans cette table — les métriques restent dans `domain_metrics` existant (relation via `domain` string), pattern `DomainMetric::forDomain($url)` déjà en place.

### Relations
- `SourceDomain` hasMany `Backlink` via `domain` extrait de `source_url`
- `SourceDomain` hasOne `DomainMetric` via `domain` string
- `Backlink` → extraction du domaine via `parse_url($source_url, PHP_URL_HOST)`

### DataforSEO Provider

Nouveau `DataForSeoProvider` implémentant `SeoMetricProviderInterface` existant :
- Endpoint : `DataForSEO Labs → Domain Authority` + `Backlinks Summary`
- Métriques récupérées : `domain_authority`, `domain_rating`, `spam_score`, `referring_domains_count`, `organic_keywords_count`
- La colonne `domain_authority` existante dans `domain_metrics` est réutilisée
- Ajout migration pour les nouvelles colonnes : `domain_rating`, `referring_domains_count`, `organic_keywords_count`
- Configuration via Settings (clé `dataforseo_login` + `dataforseo_password`, chiffrés via `Crypt`)

### Persistance des métriques (pas de fetch répété)

Les métriques DataforSEO sont **stockées en base** dans `domain_metrics` (modèle existant) et **ne sont pas refetchées à chaque requête** :
- `last_fetched_at` : date du dernier appel API (déjà dans `domain_metrics`)
- Règle de fraîcheur : ne pas refetcher si `last_fetched_at > now() - 7 jours` (configurable via `SeoMetricService`)
- Refresh manuel : bouton "Actualiser les métriques" sur la page détail domaine (dispatch `FetchDomainMetricsJob`, throttle 1/heure par domaine)
- Refresh automatique : scheduler hebdomadaire existant (`app:refresh-seo-metrics`) — sera étendu aux `SourceDomain`
- Indicateur visuel `last_fetched_at` affiché sur la page détail domaine

### Synchronisation automatique

`BacklinkObserver` (déjà en place) étendu :
- Sur `created` / `updated` : extraire domaine de `source_url` → upsert dans `source_domains`
- Si nouveau domaine : dispatcher `FetchDomainMetricsJob`

### Indicateur prix

Calculé à la volée depuis les backlinks du domaine triés par `published_at` :
- Prendre les 2 derniers backlinks avec un `price` non null sur ce domaine
- Comparer : si dernier > avant-dernier → `↑ plus cher`, si dernier < avant-dernier → `↓ moins cher`, si égal → `= stable`, si un seul → `Premier achat`

---

## Stories

### STORY-062 — Migration + Modèle SourceDomain (3 pts)
Créer la table `source_domains`, le modèle Eloquent avec ses relations, et la méthode `SourceDomain::fromUrl($url)` pour extraction et upsert depuis une source_url.

### STORY-063 — Synchronisation automatique depuis les backlinks (3 pts)
Étendre `BacklinkObserver` pour alimenter `source_domains` à chaque création/modification de backlink. Commande Artisan `app:sync-source-domains` pour la migration initiale des données existantes.

### STORY-064 — DataForSEO Provider (5 pts)
Implémenter `DataForSeoProvider` via `SeoMetricProviderInterface`. Ajouter migration pour `domain_rating`, `referring_domains_count`, `organic_keywords_count` dans `domain_metrics`. Configurer via Settings (login/password chiffrés). Désactiver Moz comme provider par défaut.

### STORY-065 — Page liste /domains (5 pts)
Controller `SourceDomainController@index`, vue `pages/domains/index.blade.php`. Colonnes : domaine, DA, DR, spam score, referring domains, nb backlinks, nb projets liés, indicateur prix. Filtres : search, DA min/max, spam score max, tri colonnes. Lien sidebar "Domaines".

### STORY-066 — Page détail /domains/{domain} (8 pts)
Vue `pages/domains/show.blade.php` structurée en 3 sections :
1. Hero strip métriques SEO (DA, DR, spam, referring domains, organic keywords, bouton refresh)
2. Tableau couverture projets (liste de tous les projets : lié ✓ / non lié ✗, nb backlinks, dernier lien, indicateur prix)
3. Tableau backlinks achetés sur ce domaine (même composant `<x-backlink-row>` avec `showProject=true`)

### STORY-067 — Ajout manuel de domaine (2 pts)
Formulaire `POST /domains` (domaine uniquement), validation format domaine, upsert `SourceDomain`, dispatch `FetchDomainMetricsJob`. Bouton "Ajouter un domaine" sur la page liste.

### STORY-068 — Onglet DataforSEO dans Settings (3 pts)
Nouvel onglet dans `/settings` pour configurer login/password DataforSEO (chiffrés via Crypt), bouton "Tester la connexion", indicateur provider actif.

### STORY-069 — Tests Feature + intégration (5 pts)
Tests Feature complets : `SourceDomainControllerTest`, `DataForSeoProviderTest` (fake HTTP), `BacklinkObserverSyncTest`, `SyncSourceDomainsCommandTest`. Vérification non-régression des tests existants.

---

## Dépendances Techniques

| Dépendance | Statut | Notes |
|-----------|--------|-------|
| `DomainMetric` model + table | ✅ Existant | Pattern `forDomain()` réutilisé |
| `SeoMetricService` + Interface | ✅ Existant | Ajouter DataForSeoProvider |
| `BacklinkObserver` | ✅ Existant | Étendre avec sync domaine |
| `FetchSeoMetricsJob` | ✅ Existant | Adapter ou créer `FetchDomainMetricsJob` |
| Settings (Crypt) | ✅ Existant | Ajouter clés DataforSEO |
| `<x-backlink-row>` composant | ✅ Existant | Réutilisé dans page détail |
| `<x-backlink-filters>` composant | ✅ Existant | Réutilisé si besoin |
| Sidebar navigation | ✅ Existant | Ajouter lien "Domaines" |
| DataforSEO API | ❌ Nouveau | Credentials à configurer |

---

## Critères de Succès

- [ ] Tous les domaines des backlinks existants sont synchronisés dans `source_domains`
- [ ] Chaque nouveau backlink crée automatiquement son domaine si absent
- [ ] Les métriques DataforSEO s'affichent sur la page détail domaine
- [ ] La matrice couverture projets est correcte et à jour en temps réel
- [ ] L'indicateur prix ↑↓ est affiché quand 2 achats ou plus sont disponibles
- [ ] Le bouton "Tester la connexion" DataforSEO fonctionne dans les Settings
- [ ] Aucune régression sur les tests existants (340 tests passants)

---

## Risques et Mitigation

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|------------|
| Coûts API DataforSEO élevés | Moyen | Haut | Caching agressif (30j min), fetch uniquement à la demande ou 1x/semaine |
| Volume de domaines très élevé | Faible | Moyen | Pagination, index sur `domain`, queue pour les fetches |
| Format domaine inconsistant (www vs non-www) | Moyen | Moyen | Normalisation systématique : strip `www.`, lowercase |
| Régression BacklinkObserver | Faible | Haut | Tests dédiés `BacklinkObserverSyncTest` avant merge |

---

## Plan de Rollback

- La table `source_domains` est additive — aucune table existante modifiée
- Le `BacklinkObserver` étendu peut être désactivé via config sans impact sur les backlinks
- Les nouvelles colonnes `domain_metrics` sont nullable — aucune régression
- Le provider DataforSEO est swappable via Settings — retour à Custom en 1 clic

---

## Definition of Done

- [ ] Stories STORY-062 à 069 complétées avec ACs validés
- [ ] Tests Feature ≥ 340 passants (aucune régression)
- [ ] Onglet "Domaines" visible dans la sidebar
- [ ] DataforSEO configuré et testé dans les Settings
- [ ] Synchronisation initiale des domaines existants exécutée
- [ ] Documentation CLAUDE.md mise à jour (nouveau modèle, routes, services)
- [ ] Pas de N+1 queries sur les pages liste et détail

---

*Créé le 2026-03-03 — John (PM Agent) — LinkTracker Sprint 5/6*
