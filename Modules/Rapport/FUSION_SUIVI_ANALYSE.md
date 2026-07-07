# Consolidation Rapport + SuiviAnalyse (§11 du plan)

## Statut : consolidation logique

Les modules `Rapport` et `SuiviAnalyse` cohabitent physiquement dans deux
dossiers séparés (`Modules/Rapport/` et `Modules/SuiviAnalyse/`), mais sont
présentés à l'utilisateur comme un seul module dans le sidebar.

## Découpage fonctionnel

| Module physique | Contenu | URL | Nature |
|---|---|---|---|
| `Modules/Rapport` | RapportController (dashboards) | `rapports.index` | Dashboards agrégés read-only avec Chart.js |
| `Modules/Rapport` | StatistiquesEcoleController | `statistiques-ecole.index` | Vue read-only par école |
| `Modules/Rapport` | StatistiquesClassesController | `statistiques-classes.index` | Vue read-only par classe |
| `Modules/SuiviAnalyse` | RapportController | `suivi-analyse.rapports.*` | CRUD rapports générés (fichiers PDF/Excel) |
| `Modules/SuiviAnalyse` | ModeleRapportController | `suivi-analyse.modeles-rapports.*` | CRUD modèles de rapports (templates) |

## Bugs résolus lors de la consolidation

- ✅ **§11.3 Collision route `rapports.index`** : `Modules/SuiviAnalyse` a été
  entièrement préfixé `suivi-analyse.*` pour ne plus entrer en conflit avec la
  route `rapports.index` du dashboard.
- ✅ **§10.9 8 fichiers Vue morts** : `StatistiquesClasses/{Create,Edit,Show,Form}.vue`
  et `StatistiquesEcole/{Create,Edit,Show,Form}.vue` supprimés (les controllers
  n'exposent que `.index`).
- ✅ **Doublons formulaires stubs Rapport/ModeleRapport** : refondus dans les
  entités actuelles (Phase 4.6e).

## Pourquoi ne pas fusionner physiquement ?

Le déplacement physique des entités/controllers/vues de `SuiviAnalyse` vers
`Rapport` demanderait :

1. Renommer 2 controllers (conflit `RapportController` entre les 2 modules)
2. Ajuster 2 namespaces d'entités
3. Déplacer 2 dossiers Vue avec toutes les références
4. Modifier composer autoload (classmap)
5. Fusionner routes/routes API
6. Modifier `modules_statuses.json` pour désactiver `SuiviAnalyse`
7. Mettre à jour les seeders RBAC (module_ids, feature.module_id)
8. Re-tester les permissions Spatie sur toutes les routes déplacées

**Estimation** : ~4 heures avec risque de régression sur les permissions et le
sidebar. Bénéfice fonctionnel : nul (les 2 modules fonctionnent déjà). Décision
prise en accord avec le principe YAGNI : la fusion physique est reportée à
une phase de refactor global.

## Comment fusionner physiquement plus tard

Si un futur besoin justifie le déplacement, voici la procédure :

```bash
# 1. Copie des entités avec ajustement namespace
cp Modules/SuiviAnalyse/Entities/Rapport.php \
   Modules/Rapport/Entities/RapportGenere.php
cp Modules/SuiviAnalyse/Entities/ModeleRapport.php \
   Modules/Rapport/Entities/ModeleRapport.php
# Remplacer `namespace Modules\SuiviAnalyse\Entities` par
# `namespace Modules\Rapport\Entities`, renommer class Rapport → RapportGenere.

# 2. Copie des controllers en renommant pour éviter la collision
cp Modules/SuiviAnalyse/Http/Controllers/RapportController.php \
   Modules/Rapport/Http/Controllers/RapportGenereController.php
cp Modules/SuiviAnalyse/Http/Controllers/ModeleRapportController.php \
   Modules/Rapport/Http/Controllers/ModeleRapportController.php

# 3. Déplacer les Vue folders
mv Modules/SuiviAnalyse/Resources/js/Pages/Rapports \
   Modules/Rapport/Resources/js/Pages/RapportsGeneres
mv Modules/SuiviAnalyse/Resources/js/Pages/ModelesRapports \
   Modules/Rapport/Resources/js/Pages/ModelesRapports

# 4. Fusionner les routes dans Modules/Rapport/Routes/web.php
#    en gardant les préfixes existants pour compat routes.

# 5. Désactiver SuiviAnalyse dans modules_statuses.json
sed -i 's/"SuiviAnalyse": true/"SuiviAnalyse": false/' modules_statuses.json

# 6. Composer autoload + tests
composer dump-autoload -o
php artisan test tests/Feature
```
