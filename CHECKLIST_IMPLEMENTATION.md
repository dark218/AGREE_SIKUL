# CHECKLIST D'IMPLEMENTATION - 27 CONTROLLERS PARAMETRAGE

## Phase 1: Préparation (Controllers)

### Controllers
- [x] RegionController.php
- [x] DepartementController.php
- [x] CommuneController.php
- [x] QuartierController.php
- [x] CycleEnseignementController.php
- [x] TypeEnseignementController.php
- [x] TypeEtablissementController.php
- [x] SectionController.php
- [x] NiveauEtudeController.php
- [x] TypeCoursController.php
- [x] NatureExamenController.php
- [x] TypeExamenController.php
- [x] UniteOrganisationnelleController.php
- [x] MatiereUniteController.php
- [x] GroupeMatiereController.php
- [x] TypeApprenantController.php
- [x] CategorieApprenantController.php
- [x] TitreCiviliteController.php
- [x] TypeEvenementAgendaController.php
- [x] PeriodesColairesController.php
- [x] AnneesScolairesController.php
- [x] TypeRessourceController.php
- [x] NatureContratController.php
- [x] CategorieEnseignantController.php
- [x] JourFerieController.php
- [x] ModesPaiementController.php
- [x] FonctionController.php

**STATUS: 27/27 COMPLETÉ** ✓

---

## Phase 2: Modeles Eloquent

### Création des Models

- [ ] Region.php
- [ ] Departement.php
- [ ] Commune.php
- [ ] Quartier.php
- [ ] CycleEnseignement.php
- [ ] TypeEnseignement.php
- [ ] TypeEtablissement.php
- [ ] Section.php
- [ ] NiveauEtude.php
- [ ] TypeCours.php
- [ ] NatureExamen.php
- [ ] TypeExamen.php
- [ ] UniteOrganisationnelle.php
- [ ] MatiereUnite.php
- [ ] GroupeMatiere.php
- [ ] TypeApprenant.php
- [ ] CategorieApprenant.php
- [ ] TitreCivilite.php
- [ ] TypeEvenementAgenda.php
- [ ] PeriodesColaires.php
- [ ] AnneesScolaires.php
- [ ] TypeRessource.php
- [ ] NatureContrat.php
- [ ] CategorieEnseignant.php
- [ ] JourFerie.php
- [ ] ModesPaiement.php
- [ ] Fonction.php

### Pour chaque Model
```php
- [ ] Ajouter use SoftDeletes
- [ ] Ajouter protected $fillable
- [ ] Ajouter protected $dates
- [ ] Créer les relations si nécessaire
- [ ] Tester le Model
```

**STATUS: __/27 À FAIRE**

---

## Phase 3: Migrations

### Création des Migrations

Pour chaque entity:
```bash
php artisan make:migration create_{entities}_table
```

- [ ] Migration Region
- [ ] Migration Departement
- [ ] Migration Commune
- [ ] Migration Quartier
- [ ] Migration CycleEnseignement
- [ ] Migration TypeEnseignement
- [ ] Migration TypeEtablissement
- [ ] Migration Section
- [ ] Migration NiveauEtude
- [ ] Migration TypeCours
- [ ] Migration NatureExamen
- [ ] Migration TypeExamen
- [ ] Migration UniteOrganisationnelle
- [ ] Migration MatiereUnite
- [ ] Migration GroupeMatiere
- [ ] Migration TypeApprenant
- [ ] Migration CategorieApprenant
- [ ] Migration TitreCivilite
- [ ] Migration TypeEvenementAgenda
- [ ] Migration PeriodesColaires
- [ ] Migration AnneesScolaires
- [ ] Migration TypeRessource
- [ ] Migration NatureContrat
- [ ] Migration CategorieEnseignant
- [ ] Migration JourFerie
- [ ] Migration ModesPaiement
- [ ] Migration Fonction

### Pour chaque Migration
```php
- [ ] Ajouter id() primary key
- [ ] Ajouter string('code', 100)->unique()
- [ ] Ajouter string('libelle', 255)
- [ ] Ajouter enum('etat', ['actif', 'inactif'])->default('actif')
- [ ] Ajouter unsignedBigInteger('created_by')
- [ ] Ajouter unsignedBigInteger('updated_by')
- [ ] Ajouter unsignedBigInteger('deleted_by')
- [ ] Ajouter timestamps()
- [ ] Ajouter softDeletes()
- [ ] Ajouter foreign keys vers users
- [ ] Ajouter indexes
```

### Exécuter les Migrations
```bash
- [ ] php artisan migrate
```

**STATUS: __/27 À FAIRE**

---

## Phase 4: Routes

### Routes Web (ou API)

Dans `routes/web.php` ou `Modules/Parametrage/Routes/web.php`:

```php
- [ ] Ajouter toutes les imports des 27 controllers
- [ ] Créer Route::group(['prefix' => 'parametrage'])
- [ ] Ajouter middleware 'auth' et 'verified'
```

Pour chaque entity:
```php
- [ ] Route::resource('{entity}', {Entity}Controller::class)
- [ ] Route::post('{entity}/{entity}/activate', [...])
```

### Routes Complètes à Ajouter

- [ ] Region Resource + Activate
- [ ] Departement Resource + Activate
- [ ] Commune Resource + Activate
- [ ] Quartier Resource + Activate
- [ ] CycleEnseignement Resource + Activate
- [ ] TypeEnseignement Resource + Activate
- [ ] TypeEtablissement Resource + Activate
- [ ] Section Resource + Activate
- [ ] NiveauEtude Resource + Activate
- [ ] TypeCours Resource + Activate
- [ ] NatureExamen Resource + Activate
- [ ] TypeExamen Resource + Activate
- [ ] UniteOrganisationnelle Resource + Activate
- [ ] MatiereUnite Resource + Activate
- [ ] GroupeMatiere Resource + Activate
- [ ] TypeApprenant Resource + Activate
- [ ] CategorieApprenant Resource + Activate
- [ ] TitreCivilite Resource + Activate
- [ ] TypeEvenementAgenda Resource + Activate
- [ ] PeriodesColaires Resource + Activate
- [ ] AnneesScolaires Resource + Activate
- [ ] TypeRessource Resource + Activate
- [ ] NatureContrat Resource + Activate
- [ ] CategorieEnseignant Resource + Activate
- [ ] JourFerie Resource + Activate
- [ ] ModesPaiement Resource + Activate
- [ ] Fonction Resource + Activate

**STATUS: __/27 À FAIRE**

---

## Phase 5: Permissions (Spatie Laravel Permission)

### Installation (si nécessaire)
```bash
- [ ] composer require spatie/laravel-permission
- [ ] php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
- [ ] php artisan migrate
```

### Création des Permissions (135 total)

Pour chaque entity, créer 5 permissions:

```bash
php artisan tinker
# Dans tinker:

- [ ] Permission::create(['name' => 'parametrage-region-list'])
- [ ] Permission::create(['name' => 'parametrage-region-create'])
- [ ] Permission::create(['name' => 'parametrage-region-edit'])
- [ ] Permission::create(['name' => 'parametrage-region-delete'])
- [ ] Permission::create(['name' => 'parametrage-region-activate'])
```

### Permissions à Créer (27 × 5 = 135)

- [ ] parametrage-region-list, create, edit, delete, activate
- [ ] parametrage-departement-list, create, edit, delete, activate
- [ ] parametrage-commune-list, create, edit, delete, activate
- [ ] parametrage-quartier-list, create, edit, delete, activate
- [ ] parametrage-cycleenseignement-list, create, edit, delete, activate
- [ ] parametrage-typeenseignement-list, create, edit, delete, activate
- [ ] parametrage-typeetablissement-list, create, edit, delete, activate
- [ ] parametrage-section-list, create, edit, delete, activate
- [ ] parametrage-niveauetude-list, create, edit, delete, activate
- [ ] parametrage-typecours-list, create, edit, delete, activate
- [ ] parametrage-natureexamen-list, create, edit, delete, activate
- [ ] parametrage-typeexamen-list, create, edit, delete, activate
- [ ] parametrage-uniteorganisationnelle-list, create, edit, delete, activate
- [ ] parametrage-matiereunite-list, create, edit, delete, activate
- [ ] parametrage-groupematiere-list, create, edit, delete, activate
- [ ] parametrage-typeapprenant-list, create, edit, delete, activate
- [ ] parametrage-categorieapprenant-list, create, edit, delete, activate
- [ ] parametrage-titrecivilite-list, create, edit, delete, activate
- [ ] parametrage-typeevenementagenda-list, create, edit, delete, activate
- [ ] parametrage-periodescolaires-list, create, edit, delete, activate
- [ ] parametrage-anneesscolaires-list, create, edit, delete, activate
- [ ] parametrage-typeressource-list, create, edit, delete, activate
- [ ] parametrage-naturecontrat-list, create, edit, delete, activate
- [ ] parametrage-categorieenseignant-list, create, edit, delete, activate
- [ ] parametrage-jourferie-list, create, edit, delete, activate
- [ ] parametrage-modespaiement-list, create, edit, delete, activate
- [ ] parametrage-fonction-list, create, edit, delete, activate

**STATUS: __/135 À FAIRE**

---

## Phase 6: Roles & Rôles-Permissions

### Créer les Rôles
```bash
- [ ] php artisan tinker
- [ ] Role::create(['name' => 'Parametrage Admin'])
- [ ] Role::create(['name' => 'Parametrage User'])
```

### Assigner les Permissions

#### Admin: Toutes les permissions
```bash
- [ ] $role = Role::findByName('Parametrage Admin')
- [ ] $role->givePermissionTo([...all 135 permissions])
```

#### User: Permissions de lecture
```bash
- [ ] $role = Role::findByName('Parametrage User')
- [ ] $role->givePermissionTo([...all 27 'list' permissions])
```

**STATUS: __/2 À FAIRE**

---

## Phase 7: Pages Vue/Inertia

### Créer dossiers
```
resources/views/Parametrage/
├── Region/
│   ├── Index.vue
│   ├── Create.vue
│   ├── Show.vue
│   └── Edit.vue
├── Departement/
│   ├── Index.vue
│   ├── Create.vue
│   ├── Show.vue
│   └── Edit.vue
... (répéter pour les 25 autres entities)
```

### Pour chaque Entity, créer 4 pages

#### Index.vue - Liste avec filtres
- [ ] Tableau avec colonnes (code, libelle, etat, actions)
- [ ] Filtres (search, etat)
- [ ] Pagination
- [ ] Boutons (Créer, Éditer, Supprimer, Activer/Désactiver)
- [ ] Messages flash
- [ ] Lien vers Create

#### Create.vue - Formulaire création
- [ ] Formulaire vide
- [ ] Inputs (code, libelle, etat)
- [ ] Validation côté client
- [ ] Boutons (Créer, Annuler)
- [ ] Affichage erreurs de validation

#### Show.vue - Détails
- [ ] Affichage lecture seule
- [ ] Informations complètes
- [ ] Audit (created_by, created_at, updated_by, updated_at, deleted_by)
- [ ] Boutons (Éditer, Retour)

#### Edit.vue - Formulaire édition
- [ ] Formulaire pré-rempli
- [ ] Inputs (code, libelle, etat)
- [ ] Validation côté client
- [ ] Boutons (Modifier, Annuler)
- [ ] Affichage erreurs de validation

### Pages à Créer

- [ ] Region (Index, Create, Show, Edit)
- [ ] Departement (Index, Create, Show, Edit)
- [ ] Commune (Index, Create, Show, Edit)
- [ ] Quartier (Index, Create, Show, Edit)
- [ ] CycleEnseignement (Index, Create, Show, Edit)
- [ ] TypeEnseignement (Index, Create, Show, Edit)
- [ ] TypeEtablissement (Index, Create, Show, Edit)
- [ ] Section (Index, Create, Show, Edit)
- [ ] NiveauEtude (Index, Create, Show, Edit)
- [ ] TypeCours (Index, Create, Show, Edit)
- [ ] NatureExamen (Index, Create, Show, Edit)
- [ ] TypeExamen (Index, Create, Show, Edit)
- [ ] UniteOrganisationnelle (Index, Create, Show, Edit)
- [ ] MatiereUnite (Index, Create, Show, Edit)
- [ ] GroupeMatiere (Index, Create, Show, Edit)
- [ ] TypeApprenant (Index, Create, Show, Edit)
- [ ] CategorieApprenant (Index, Create, Show, Edit)
- [ ] TitreCivilite (Index, Create, Show, Edit)
- [ ] TypeEvenementAgenda (Index, Create, Show, Edit)
- [ ] PeriodesColaires (Index, Create, Show, Edit)
- [ ] AnneesScolaires (Index, Create, Show, Edit)
- [ ] TypeRessource (Index, Create, Show, Edit)
- [ ] NatureContrat (Index, Create, Show, Edit)
- [ ] CategorieEnseignant (Index, Create, Show, Edit)
- [ ] JourFerie (Index, Create, Show, Edit)
- [ ] ModesPaiement (Index, Create, Show, Edit)
- [ ] Fonction (Index, Create, Show, Edit)

**STATUS: __/108 pages À FAIRE (27 × 4)**

---

## Phase 8: Tests

### Tester chaque CRUD (27 × 5 = 135 tests)

Pour chaque Entity:

#### Test Index
```php
- [ ] GET /parametrage/{entity}
- [ ] Vérifier pagination
- [ ] Tester filtres (search, etat)
- [ ] Vérifier permissions
```

#### Test Create
```php
- [ ] GET /parametrage/{entity}/create
- [ ] Affichage formulaire
- [ ] Vérifier permissions
```

#### Test Store
```php
- [ ] POST /parametrage/{entity}
- [ ] Créer enregistrement valide
- [ ] Tester validation (code unique, required, max)
- [ ] Vérifier created_by
- [ ] Vérifier timestamps
- [ ] Vérifier redirect + flash message
```

#### Test Edit/Show
```php
- [ ] GET /parametrage/{entity}/{id}/edit
- [ ] GET /parametrage/{entity}/{id}
- [ ] Affichage données pré-remplies
- [ ] Vérifier permissions
```

#### Test Update
```php
- [ ] PUT /parametrage/{entity}/{id}
- [ ] Modifier enregistrement
- [ ] Tester validation
- [ ] Vérifier updated_by
- [ ] Vérifier soft delete ne supprime pas
```

#### Test Destroy
```php
- [ ] DELETE /parametrage/{entity}/{id}
- [ ] Vérifier soft delete
- [ ] Vérifier deleted_by
- [ ] Vérifier deleted_at
- [ ] Vérifier données non récupérées après delete
```

#### Test Activate
```php
- [ ] POST /parametrage/{entity}/{id}/activate
- [ ] Tester désactivation (delete)
- [ ] Tester activation (restore)
- [ ] Vérifier toggle status
```

**STATUS: __/135 tests À FAIRE**

---

## Phase 9: Documentation

### Documentation à créer

- [x] CONTROLLERS_PARAMETRAGE_RESUME.md (FAIT)
- [x] EXEMPLE_UTILISATION_CONTROLLER.md (FAIT)
- [x] CHECKLIST_IMPLEMENTATION.md (CE FICHIER)
- [ ] API Documentation
- [ ] Vue Components Documentation
- [ ] Permissions Reference

**STATUS: 3/6 FAIT**

---

## Phase 10: Validation Finale

### Checks Finaux

- [ ] Tous les controllers testés
- [ ] Tous les routes testés
- [ ] Toutes les permissions assignées
- [ ] Audit trail vérifié
- [ ] Soft deletes fonctionnels
- [ ] Pagination fonctionnelle
- [ ] Filtres fonctionnels
- [ ] Messages flash apparaissent
- [ ] Erreurs loggées
- [ ] Validations côté client
- [ ] Validations côté serveur
- [ ] Model binding fonctionnel
- [ ] Inertia rendering OK
- [ ] Tests passent tous

**STATUS: __/14 À FAIRE**

---

## RESUME IMPLEMENTATION

| Phase | Element | Fait | Total | % |
|-------|---------|------|-------|---|
| 1 | Controllers | 27 | 27 | 100% ✓ |
| 2 | Models | 0 | 27 | 0% |
| 3 | Migrations | 0 | 27 | 0% |
| 4 | Routes | 0 | 27 | 0% |
| 5 | Permissions | 0 | 135 | 0% |
| 6 | Roles | 0 | 2 | 0% |
| 7 | Pages Vue | 0 | 108 | 0% |
| 8 | Tests | 0 | 135 | 0% |
| **TOTAL** | | **27** | **462** | **5.8%** |

---

## COMMANDES UTILES

### Lancer les migrations
```bash
php artisan migrate
```

### Créer les permissions
```bash
php artisan tinker

# Boucle pour créer toutes les 135 permissions
foreach (['region', 'departement', ..., 'fonction'] as $entity) {
  foreach (['list', 'create', 'edit', 'delete', 'activate'] as $action) {
    Permission::create(['name' => "parametrage-{$entity}-{$action}"]);
  }
}
```

### Tester les routes
```bash
php artisan route:list | grep parametrage
```

### Vérifier les permissions d'un utilisateur
```bash
php artisan tinker
$user = User::find(1);
$user->permissions;
$user->roles;
```

### Lancer les tests
```bash
php artisan test
```

---

## NOTES IMPORTANTES

1. Les controllers sont prêts (Phase 1 complète)
2. Les modèles doivent inclure `use SoftDeletes`
3. Les migrations doivent inclure `softDeletes()`
4. Les permissions doivent être en minuscules avec tirets
5. Les routes doivent être dans un groupe `prefix('parametrage')`
6. Les pages Vue doivent utiliser Inertia composants
7. Les tests doivent couvrir tous les cas (valide, invalide, erreur)
8. La documentation doit être à jour

---

## CONTACT & SUPPORT

Pour toute question sur l'implémentation:
- Consulter CONTROLLERS_PARAMETRAGE_RESUME.md
- Consulter EXEMPLE_UTILISATION_CONTROLLER.md
- Vérifier la structure d'un controller existant
- Tester avec `php artisan tinker`

