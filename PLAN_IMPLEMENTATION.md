# Plan d'implémentation AGREE SIKUL — Audit et refonte

Rédigé par Claude Opus 4.7 après audit complet du dépôt.
Date : 2026-07-07.

---

## 0 · Constat général

Le projet a été forké depuis le POS d'assurance **SmilPay**. Il en garde une dette technique lourde :

- **10 modules Laravel** (Academique, Parametrage, Personnel, Finances, RessourcesLogistique, Services, Communication, Administration, Rapport, SuiviAnalyse), **148 controllers**, **~34 000 lignes** rien que dans les controllers.
- La table `feature` (RBAC dynamique) contient **encore ~180 features SmilPay** (Réseau de soins, Actes médicaux, Confection cartes, Barèmes commerciaux, Nomenclature polices, Recouvrement assurance…). La sidebar Agree Sikul actuelle est en fait **statique** dans [TheSidebar.vue](resources/js/Components/Layout/TheSidebar.vue), pilotée par une constante `DEFAULT_MENU_CONFIG` — la table `feature` n'est utilisée que comme fallback / vérification de permissions.
- **6 doublons fonctionnels majeurs** identifiés (Matière/MatièreUnité, Niveau/NiveauÉtude, Civilités/Titres Civilité, Type Contrat/Nature Contrat, Type Établissement en 2 dossiers Vue).
- **Formulaires monstrueux** : ParentForm 78 champs, Apprenant 47, Enseignant 30 (avec 24 slots numérotés matière/cycle/niveau/classe).
- **Colonnes NOT NULL héritées** de SmilPay qui font échouer les INSERT (uuid, qr_data, num_enseignant, pays_id sur tous les référentiels — déjà corrigé par batches successifs mais d'autres surviennent).
- **256 migrations pending** au moment du dernier audit, dont beaucoup non-idempotentes → problèmes récurrents sur les déploiements.

**Diagnostic** : ce n'est pas un problème de développeur, c'est un projet **construit sur un socle inadapté** qu'il faut nettoyer méthodiquement. Le plan ci-dessous prévoit une remise à niveau par phases, chaque phase testable indépendamment.

---

## 1 · Suppressions immédiates (dette bloquante)

### 1.1 Fonctionnalité "Matière" (Académique) → migrer vers "Matière Unité" (Paramétrage)

**Ampleur** : 17 controllers, 13 entités PHP, 8 tables avec FK, ~55 fichiers Vue impactés indirectement.

**Impact tables (foreign keys `matiere_id`)** :
- `cours`, `moyennes_matieres`, `enseignant_matieres`, `devoirs`, `absences_apprenants`, `absences_enseignants`, `evaluations`
- `affectations_enseignants` avec **21 colonnes** `matiere_1_id` … `matiere_21_id` (à revoir en table pivot)

**Migration en 3 étapes** :

1. **Enrichir MatiereUnite** : ajouter les colonnes présentes dans `matieres` mais pas dans `matieres_unites` — `annee_scolaire_id`, `description`, `couleur`, `categorie`. Sinon on perd des données.
2. **Copier les données** : `INSERT INTO matieres_unites (...) SELECT ... FROM matieres WHERE deleted_at IS NULL`. **Attention** : `code` est UNIQUE dans les 2 tables — dédoublonnage nécessaire (`ON DUPLICATE KEY UPDATE`).
3. **Repointer les FK** : `dropForeign(['matiere_id'])` puis `foreign('matiere_id')->references('id')->on('matieres_unites')` sur les 8 tables. Pour `affectations_enseignants` : `SET FOREIGN_KEY_CHECKS=0` puis boucle sur les 21 colonnes.

**Code à supprimer** :
- `Modules/Academique/Http/Controllers/MatiereController.php` (entier)
- `Modules/Academique/Entities/Matiere.php`
- `Modules/Academique/Resources/js/Pages/Matieres/` (5 fichiers Vue)
- Routes `Route::prefix('matieres')` de `Modules/Academique/Routes/web.php:5,85-96`
- Entrée `matieres` du sidebar [TheSidebar.vue:92](resources/js/Components/Layout/TheSidebar.vue#L92)
- Permissions `academique-matiere-*` dans les seeders RBAC
- Ligne 92 sidebar `matieres`

**Code à réécrire (17 controllers)** — remplacer `Matiere::class` par `MatiereUnite::class` + `where('statut', ...)` en `where('etat', ...)` :
- `AbsenceApprenantController`, `AbsenceEnseignantController`, `AffectationEnseignantController`, `BulletinController`, `CoursController`, `DevoirController`, `DocumentScolaireController`, `EmploiTempsController`, `EvaluationController`, `ExamenEnLigneController`, `MoyenneMatiereController`, `NoteController`, `PdfExportController`, `PlanificationExamenController`, `ResultatExamenController`, `AcademicCalculationService`, `HomeController` (jointures SQL brutes)

**Entités qui déclarent `belongsTo(Matiere::class)`** (12 fichiers) — à basculer vers `MatiereUnite::class` :
`AbsenceApprenant`, `AbsenceEnseignant`, `Cours`, `Devoir`, `EmploiTemps`, `Evaluation`, `ExamenEnLigne`, `MoyenneMatiere`, `Moyenne`, `Note`, `PlanificationExamen`, `Seance`.

**Bugs latents révélés par l'audit** :
- `MatiereClasseTestSeeder.php:33` référence `Modules\Parametrage\Entities\Matiere` qui n'existe pas — bug préexistant
- `EmploiTempsTestSeeder.php:23` idem

**Tests unitaires à écrire** :
- `MatiereMigrationTest` : vérifie qu'après migration, les cours/évaluations/notes existants pointent tous vers une MatiereUnite valide (0 orphelin)
- `MatiereRoutesRemovedTest` : les routes `academique.matieres.*` retournent 404
- `EnseignantMatiereRelationTest` : la relation `enseignant->matieres` fonctionne toujours et pointe bien sur `matieres_unites`

---

### 1.2 Fonctionnalité "Civilités" → supprimer (doublon de "Titres Civilité")

- Ligne 231 sidebar : `civilites` (icône `fas fa-user`) — **DOUBLON**
- Ligne 230 sidebar : `titres_civilites` — **À GARDER**

**Actions** :
- Supprimer route `civilites` + controller + entity + dossier Vue `Modules/Parametrage/Resources/js/Pages/Civilites/`
- Supprimer entrée sidebar ligne 231
- Supprimer permissions `civilites-*` + feature `civilites` de la table `feature`
- Migration data : si la table `civilites` a des enregistrements, les copier dans `titres_civilites` (si structure similaire) puis dropper. Sinon dropper directement.

---

### 1.3 Fonctionnalité "Type de contrat" → supprimer (doublon de "Nature de contrat")

- Ligne 237 sidebar : `types_contrats` — **DOUBLON**
- Ligne 213 sidebar : `natures_contrat` — **À GARDER**

**Actions** identiques à Civilités.

**Attention** : `enseignants.type_contrat` est un champ VARCHAR (pas une FK vers `types_contrats`) — ce n'est pas la FK qui est en cause, c'est bien la fonctionnalité "référentiel Type de contrat" qui doublonne avec Nature de contrat. Le champ `type_contrat` d'Enseignant devrait être remplacé par `nature_contrat_id` (FK vers `natures_contrats`, déjà présente — cohabitation actuelle).

---

### 1.4 Doublons à investiguer (à valider avec toi avant action)

L'audit RBAC a révélé **12 doublons** au total (au-delà des 3 signalés) :

| # | Doublon 1 | Doublon 2 | Décision recommandée |
|---|---|---|---|
| a | `matieres` (id 54 Académique) | `matieres-unites` (id 24 Paramétrage) | **Garder MatiereUnite** — voir §1.1 |
| b | `civilites` (fallback JS) | `titres-civilites` (id 10) | **Garder Titre Civilité** — voir §1.2 |
| c | `types_contrats` (fallback JS) | `natures-contrats` (id 34) | **Garder Nature Contrat** — voir §1.3 |
| d | `niveaux` (fallback) + `niveaux_etude` (fallback) + `niveaux-etudes` (id 8) | 3 entrées pour 1 concept ! | **Garder NiveauEtude uniquement**, purger les 2 autres |
| e | `types-etablissement` (id 13) | `types_etablissement_spe` (id 168) masqué | Supprimer le "Spé" — masqué ET dupliqué |
| f | `presences` (id 62) | `absences` (id 63) masqué + `presences-seances` | Fusionner Présences/Absences en 1 module unique |
| g | `absence-apprenants` (id 42) + `absence-enseignants` (id 43) + `absences` générique (id 63) | 3 entrées | Garder les 2 spécialisées, dropper la générique |
| h | `categories-fournitures` (id 90/91 module 15) | `categories-fournitures` (id 140/142 module 26) | **BUG : doublons de menu_url → conflits de route** |
| i | Bibliothèque : `bibliotheques` (id 136 Académique) | `ouvrages`+`exemplaires` (id 148, 149 RessourcesLogistique) | Consolider sous RessourcesLogistique |
| j | `tuteurs` (id 61 Personnel) | `tuteurs` (fallback JS Académique) | Doublon affichage — garder Personnel |
| k | `types_etablissements` (Vue folder) | `TypesÉtablissement` (autre Vue folder) | Deux dossiers Vue → unifier |
| l | `rapports` (id 162) | `statistiques-ecole`+`statistiques-classes` (id 152, 153) | Chevauchement → fusion en 1 module Rapports |

---

## 2 · Formulaires — passage en steppers + réduction drastique

**Composant à créer une seule fois** : `resources/js/Components/Common/FormStepper.vue`
- Props : `steps: [{key, label, icon, requiredFields: [], validate?: fn}]`
- Slot par step (nommé par `key`)
- Barre de progression + navigation Prev/Next + validation soft avant next
- Persistance locale (localStorage) pour ne pas perdre la saisie sur refresh
- Skip fluide vers un step déjà validé

### 2.1 Formulaire Parent — le pire cas (78 → ~20 champs)

Actuellement 4 blocs quasi-identiques (Père/Mère/Tuteur1/Tuteur2) avec 16 champs chacun = 64 + 14 autres.

**Steps proposés (4)** :

1. **Enfants rattachés** — apprenant_ids[], lien_parente[] (via ApprenantsPicker), auto-fill école commune
2. **Père** — 5 champs seulement : Nom(s) et prénom(s), Profession, Téléphone principal, Email principal, Adresse résidence
3. **Mère** — mêmes 5 champs + case à cocher "Même adresse que le père" (copie)
4. **Tuteur(s)** — Tuteur 1 OPTIONNEL (case à cocher "Ajouter un tuteur"), Tuteur 2 masqué par défaut

**Champs à supprimer massivement** :
- Pour chaque personne : `quartier`, `commune`, `arrondissement`, `ville`, `departement`, `region`, `pays`, `code_postal`, `boite_postal` (textes libres redondants avec `adresse_residence`)
- `whatsapp_1/2`, `telephone_2`, `email_2` : garder 1 tél + 1 email, cacher le reste sous "Options avancées"
- `organisation_travail` + `ville_travail` + `pays_travail` : fusionner en 1 champ **"Employeur"**

### 2.2 Formulaire Apprenant (47 → ~28 champs, 5 steps)

Selon les blocs du **docx client** + nos recommandations expert :

1. **Identité** — nom, prenoms, date_naissance, lieu_naissance, genre_id, nationalite, photo
2. **Sanitaire** — groupe_sanguin_id, allergies textuelles, aliments_interdits, hôpital1/2, médecin téléphone1/2, cases drépano/asthme/diabète/épilepsie
3. **Scolarité** — classe_id (auto section/cycle/école/campus/année), type_apprenant_id, categorie_apprenant_id, école_precedente, classe_precedente, matricule, numero_inscription, apte_sport
4. **Famille & Contact** — nom_complet_pere, nom_complet_mere, nom_complet_tuteur, adresse, quartier_id, telephone1, whatsapp1, email1
5. **Hébergement (conditionnel) & Suivi** — est_interne → si oui : batiment/étage/chambre/lit ; date_entree_ecole, date_depart, motif_depart, statut

**Champs à supprimer** :
- `age` → toujours computed depuis `date_naissance`
- Bloc naissance (commune/dept/region/pays_naissance_id) : garder seulement `commune_naissance_id`, le reste cascade auto
- Bloc résidence : `arrondissement`, `ville`, `departement_residence_id`, `region_residence_id`, `pays_residence_id` : dérivables de `quartier_id`
- `telephone2`, `whatsapp2` : rares, cachés sous "Avancé"

**Auto-fill critique** :
- `section_id`, `cycle_id`, `ecole_id`, `campus_id`, `annee_scolaire_id` ← `classe_id`
- `nationalite` ← `pays_naissance_id.nationalite`

### 2.3 Formulaire Enseignant (30 champs + 24 slots → ~22 champs, 5 steps)

Selon docx client + expertise :

1. **Identité** — titre_civilite, nom, prenoms, nom_restituer, genre_id, marital_status, nom_jeune_fille (conditionnel), date_of_birth, place_of_birth, nationalite
2. **Formation** — highest_diploma, speciality, year_obtained, languages[], teaching_speciality
3. **Emploi** — matricule, nature_contrat_id, date_embauche, categorie_enseignant_id, fonction_id, statut
4. **Enseignement** — **multi-selects n-n** au lieu des 24 slots numérotés :
   - `matieres[]` (multi) → table pivot `enseignant_matieres`
   - `cycles[]` (multi) → table pivot `enseignant_cycles_enseignement` (déjà existante)
   - `niveaux[]` (multi) → pivot `enseignant_niveaux`
   - `classes[]` (multi) → pivot `enseignant_classes`
5. **Contact & Photo** — email, telephone, photo

**Champs à supprimer** :
- `nom_restituer` → computed
- `age` → computed
- `place_of_birth` texte : doublon avec `commune_id` — garder un des deux
- Slots `matiere_1..7`, `cycle_1..2`, `niveau_1..4`, `classe_1..5` : passer en multi-select (24 → 4)

### 2.4 Formulaire Inscription (16 champs → 4 steps)

1. **Apprenant & année** — apprenant_id, annee_scolaire_id, classe_id, type_inscription, premiere_inscription
2. **Frais** — 3 blocs base + payé (dossier/inscription/scolarité), pré-remplis depuis `classe.frais_*` ; restant calculé
3. **Pièces jointes** — 8 fichiers regroupés visuellement (dropzone unique + labels)
4. **Validation** — statut, dossier_complet, date_inscription

**Champs à retirer du form soumis** (déjà auto-fill) : `ecole_id`, `campus_id`, `institution_id`.
**Champ auto-serveur** : `numero_inscription` (format `INS-YYYY-NNNNN`).

### 2.5 Formulaires à laisser mono-page (déjà minimaux)

- Tuteur (9 champs)
- PersonnelAdministratif (7 champs)
- Tous les référentiels Paramétrage simples (Code + Libellé + État)

---

## 3 · Nettoyage RBAC / sidebar

### 3.1 Élimination des résidus SmilPay dans `feature`

Purger la table `feature` de tout ce qui n'a rien à voir avec l'école :

- Modules à **DROPPER** de la table `module` : `Reseau Commercial`, `Reseau de Soins`, `Referentiel Medical`, `Produits & Tarification`, `Prestations`, `Dispensation`, `Confection Cartes`, `Recouvrement`, `Force Update`, `Vitrine`, `Support` (3 doublons !), `Paiement` (dupliqué), `Finance` (5 doublons SmilPay!), `Principal` (SmilPay), `Carte`, `Gestion` (SmilPay)

- **~180 features SmilPay** à supprimer (Actes médicaux, Barèmes, Chefs agence, Commerciaux, Signature & Tampon, Types réseau, Formules produit, Nomenclature polices, etc.)

- Modules à **GARDER + RENOMMER** proprement pour Agree Sikul :
  - Module `Académique` → à créer proprement + seeder complet des ~20 features
  - Module `Personnel école` → Parents, Tuteurs, Accompagnateurs, PersonnelAdmin
  - Module `Paramétrage école` → tous les référentiels
  - Module `Finances école` → Ecolage, Salaires, Facturation, Rapports
  - Module `Ressources & Services` → Bibliothèque, Cantine, Transport, Infirmerie
  - Module `Communication` → Messages, Annonces
  - Module `Rapports` → statistiques, exports

### 3.2 Migration du sidebar de statique → dynamique via `feature`

Actuellement `TheSidebar.vue` a **121 entrées en dur** dans `DEFAULT_MENU_CONFIG`. C'est ce qui fait qu'on a des icônes cohérentes côté UI, mais la vraie source RBAC (table `feature`) est incomplète/en dérive.

**Plan** :
1. Migration `seed_agree_sikul_features` : insérer/mettre à jour les 121 features d'Agree Sikul depuis le contenu de `DEFAULT_MENU_CONFIG` (icône, libelle, menu_url, module_id, ordre)
2. Refactor `TheSidebar.vue` : lire uniquement depuis la prop `navbars` fournie par le backend, garder `DEFAULT_MENU_CONFIG` uniquement comme fallback (aucun autre usage)
3. Test : le sidebar reste iso après migration → puis on peut sereinement supprimer les résidus SmilPay sans affecter Agree Sikul

### 3.3 Icônes manquantes et incohérences

**Bonne nouvelle** : aucune feature en base n'a d'icône NULL. Toutes ont un `fas fa-...`.

**Mauvaises nouvelles** révélées par l'audit :

1. **`NouveauxReferentielsRbacSeeder` jamais exécuté** — les 10 features des référentiels récemment ajoutés (`civilites`, `genres`, `liens_parente`, `situations_matrimoniales`, `groupes_sanguins`, `langues`, `types_contrats`, `statuts_employes`, `statuts_apprenants`, `types_inscriptions`) **n'existent pas en DB**. Elles apparaissent uniquement dans le fallback JS de `TheSidebar.vue`, donc visibles seulement pour super_admin (pas de permission créée) → **`php artisan db:seed --class=NouveauxReferentielsRbacSeeder` à exécuter d'urgence**.

2. **Modules orphelins** (`feature.module_id` sans ligne dans `module`) — le sidebar ne peut pas afficher le module parent :
   - Module 15 (Inventaire) : Catégories Équipement, Équipements, Maintenances, Catégories Fournitures, Fournitures
   - Modules 19–21 : Clients, Moyens Paiement, Ventes POS, QR Codes, Articles (résidus commerciaux SmilPay)
   - Module 27 : Paramétrage Généraux
   - **Décision** : purger toutes ces features orphelines (module 15, 19–21, 27) — pas de sens Agree Sikul.

3. **Bug menu_url dash vs underscore** — critique :
   - DB utilise `matieres-unites` (dash)
   - Fallback JS utilise `matiere_unites` (underscore)
   - `usePermissions()` compare `${menu_url}-list` littéralement → **permissions silencieusement ratées**
   - **Action** : uniformiser TOUT en dash (convention DB) + adapter fallback JS + relancer seeder

4. **Doublons d'icônes** qui rendent la sidebar illisible :
   - `fa-graduation-cap` × 4 (Écolage, module Académique, Cours, Type Enseignement…)
   - `fa-tag` × 4 (Catégories équipement/fournitures, motifs demande…)
   - `fa-book` × 3 (Bibliothèques, Liste manuels…)
   - `fa-list` × 3 (Menus cantine, Postes recettes/dépenses)
   - `fa-users` × 3, `fa-coins` × 3, `fa-school` × 3, `fa-chart-line` × 3

   **Actions ciblées** :
   - `menus` (cantine) : `fa-utensils`
   - `bibliotheques` : `fa-book-open-reader`
   - `postes-recettes` : `fa-arrow-trend-up`
   - `postes-depenses` : `fa-arrow-trend-down`
   - Retirer `ecolage` (doublon Académique) + attribuer une icône dédiée

5. **`HIDDEN_FEATURES` list dans TheSidebar.vue:412** cache des features (types_etablissement_spe, absences génériques, civilites) au lieu de les supprimer proprement de la DB. À nettoyer : soit dropper la feature, soit la démasquer.

---

## 4 · Nettoyage migrations

**Problème** : la stack de migrations est cassée par des non-idempotences répétées (voir l'épisode 256 pending migrations avec erreurs `already exists`).

**Actions** :
1. Créer un dossier `database/migrations_archive/` et y déplacer toutes les migrations antérieures à `2026_02_09_*` (résidus SmilPay non-idempotents)
2. Créer une migration `_squash` unique qui contient l'état actuel de la base : `CREATE TABLE IF NOT EXISTS` pour toutes les tables Agree Sikul, sans FK sur des tables droppées SmilPay
3. Ajouter un contrôle CI (script pre-push) : pour chaque nouvelle migration, vérifier qu'elle a bien un `Schema::hasTable / hasColumn` guard sur les `Schema::create` et les `add column`

**Alternative pragmatique** (moins risquée) : ne toucher aux migrations SmilPay que quand elles cassent — on continue d'ajouter des migrations idempotentes.

---

## 5 · Tests unitaires — plan minimal

Chaque phase produit un test dédié. Structure proposée :

```
tests/
├── Feature/
│   ├── Migration/
│   │   ├── MatiereToMatiereUniteMigrationTest.php
│   │   ├── CivilitesRemovalTest.php
│   │   └── TypeContratRemovalTest.php
│   ├── Forms/
│   │   ├── ApprenantStepperTest.php
│   │   ├── EnseignantStepperTest.php
│   │   ├── ParentStepperTest.php
│   │   └── InscriptionStepperTest.php
│   ├── AutoFill/
│   │   ├── ClasseAutoFillTest.php   (classe → section, cycle, école, campus)
│   │   ├── NiveauAutoFillTest.php   (niveau → section, cycle)
│   │   └── GeoAutoFillTest.php      (quartier → commune → dept → région → pays)
│   ├── Rbac/
│   │   ├── SidebarFeaturesSeededTest.php
│   │   └── SmilPayFeaturesRemovedTest.php
│   └── CrudReferentiels/
│       ├── (12 referentiels simples)
│       └── ...
└── Unit/
    ├── AutoUserCreatorTest.php
    └── ...
```

Objectif : **couverture 60%** sur les modules Academique / Parametrage / Personnel, **80%** sur les migrations, **100%** sur AutoUserCreator et les services de calcul (moyennes, notes).

---

## 6 · Ordre d'exécution recommandé

### Phase 0 — URGENCE (0.5 jour) : dette révélée par l'audit
1. **`php artisan db:seed --class=NouveauxReferentielsRbacSeeder`** — sinon les 10 référentiels récents ne sont visibles que par super_admin
2. **Fix bug `menu_url` dash/underscore** — les permissions sont silencieusement KO sur toutes ces features → uniformiser en dash + relancer seeders
3. **Purger les modules orphelins 15, 19–21, 27** (résidus SmilPay avec features fantômes)

### Phase 1 — Nettoyage bloquant (semaine 1)
1. ✅ Suppression fonctionnalité Matière → MatiereUnite (impact fort, mais bien cartographié)
2. ✅ Suppression Civilités (petit impact)
3. ✅ Suppression Type de contrat (petit impact)
4. ✅ Résolution doublons Niveau vs NiveauEtude (3 entrées !), TypeEtablissement (2 folders Vue)
5. ✅ Résolution doublons Bibliothèque (2 modules), Rapport vs Statistiques
6. ✅ Fix conflits routes `categories-fournitures` (menu_url dupliqué en DB)

### Phase 2 — Steppers (semaine 2-3)
1. Création composant `FormStepper.vue`
2. Refonte ParentForm (78 → 20)
3. Refonte ApprenantForm (47 → 28)
4. Refonte EnseignantForm (30+24 slots → 22 + n-n)
5. Refonte InscriptionForm (16 → 4 steps)

### Phase 3 — RBAC dynamique (semaine 4)
1. Seed complet des features Agree Sikul depuis `DEFAULT_MENU_CONFIG`
2. Refactor `TheSidebar.vue` vers 100% dynamique
3. Purge résidus SmilPay dans `feature` + `module`

### Phase 4 — Tests + CI (semaine 5)
1. Écriture des tests unitaires listés §5
2. Ajout CI hook pré-push (idempotence migrations)
3. Validation manuelle en golden path + edge cases

### Phase 5 — Nettoyage migrations (optionnel, semaine 6)
1. Archive migrations SmilPay
2. Migration `_squash` de l'état Agree Sikul

---

## 7 · Métriques de succès

| Métrique | Avant | Cible |
|---|---|---|
| Champs formulaire Parent | 78 | 20 |
| Champs formulaire Apprenant | 47 | 28 |
| Champs formulaire Enseignant (avec slots) | 54 | 22 |
| Doublons fonctionnels visibles dans sidebar | 6+ | 0 |
| Features SmilPay résiduelles dans `feature` | ~180 | 0 |
| Modules SmilPay résiduels dans `module` | ~35 | 0 |
| Couverture tests unitaires Academique/Parametrage/Personnel | inconnu | ≥60% |
| Migrations non-idempotentes | ~200 | 0 |
| Erreurs "Field X doesn't have a default value" en test | fréquent | 0 |

---

## 8 · Risques et mitigations

| Risque | Mitigation |
|---|---|
| Perte de données lors migration Matière → MatiereUnite (collision `code` UNIQUE) | Migration en 3 étapes, dédoublonnage préalable + backup DB |
| Casse de fonctionnalités Académique existantes (17 controllers touchés) | Tests d'intégration avant/après + rollback simple (les vraies FK ne sont dropped qu'en toute fin) |
| Fenêtre de "sidebar cassé" pendant migration statique→dynamique | Feature flag / branch preview + rollout en 1 fois seulement quand tests passent |
| Formulaires steppers cassent l'UX habituelle | Prototype 1 formulaire (Parent), validation utilisateur, puis déploiement des 3 autres |
| Régression permissions RBAC pendant purge SmilPay | Backup table `feature` + `permissions` avant purge, test sur chaque rôle après |

---

## 9 · Décisions à valider

**Blocages** — je ne fais rien tant que tu n'as pas tranché :

1. **Doublon `niveaux` vs `niveaux_etude` vs `niveaux-etudes`** : recommandation → garder uniquement **NiveauEtude** (`niveaux-etudes`, id 8) et purger les 2 autres. OK ?
2. **`enseignants.type_contrat` (VARCHAR)** : on le remplace complètement par `nature_contrat_id` (FK), ou on garde la coexistence ?
3. **Composant FormStepper** : librairie externe (`@vueform/multistep`, `formkit`) ou custom léger ? Recommandation : custom léger (~150 lignes, moins de deps)
4. **Feature flag stepper** : bascule progressive utilisateur par utilisateur, ou switch global ? Recommandation : global — 5 formulaires = pas assez pour justifier un flag
5. **Migrations archive** : Phase 1 ou Phase 5 ? Recommandation : Phase 5 (on ne touche pas ce qui marche encore)
6. **Bibliothèque** : garder module Académique (rapide accès pour prof/élève) ou RessourcesLogistique (plus cohérent gestion) ?
7. **Modules Rapport + SuiviAnalyse** : fusionner en un seul module "Rapports" ?
8. **`HIDDEN_FEATURES` dans TheSidebar.vue** : on garde le masquage à la volée ou on drop les features de la DB ?

---

## 10 · Audit EXHAUSTIF par module

Cette section détaille chaque module (10 au total, 148 controllers). Pour chaque module : formulaires (avec nb champs), redondances internes, auto-fill à faire, bugs latents avec fichier:ligne.

### 10.1 · Module `Academique` (hors Apprenant/Enseignant/Inscription/Matière — déjà §1 et §2)

**33 controllers restants**, groupés en 6 sous-domaines :

**Formulaires prioritaires à refondre** :

| Formulaire | Fichier | Nb champs | Priorité | Action |
|---|---|---|---|---|
| Note | [NoteController.php:165](Modules/Academique/Http/Controllers/NoteController.php#L165) | **20** | **P0** | Stepper 3 steps + auto-fill élève→classe/école/campus/année, évaluation→matière/coef/note_sur → réduire à **3-4 saisis** |
| AffectationEnseignant | [AffectationEnseignantController.php:108](Modules/Academique/Http/Controllers/AffectationEnseignantController.php#L108) | **26** dont 21 matières | **P0** | Refactor `matiere_1_id`..`matiere_21_id` en table pivot `affectation_matieres`. Anti-pattern majeur, empêche l'ajout d'une 22e matière sans migration. |
| ExamenEnLigne | [ExamenEnLigneController.php:90](Modules/Academique/Http/Controllers/ExamenEnLigneController.php#L90) | **21** | P1 | Stepper 4 steps (Infos / Planning / Comportement / Sécurité). **Bug** : `mot_de_passe` stocké en clair L.111 |
| EmploiTemps | [EmploiTempsController.php:154](Modules/Academique/Http/Controllers/EmploiTempsController.php#L154) | **19** | P1 | Auto-fill classe_id → section/cycle/école/campus/année + dérivation `duree` depuis `date_debut`+`date_fin` |
| Bibliotheque | [BibliothequeController.php:102](Modules/Academique/Http/Controllers/BibliothequeController.php#L102) | 12 | **P0** | **COLLISION TABLE** avec `RessourcesLogistique\Bibliotheque` — voir §11 |

**Autres formulaires** (11 CRUD) déjà OK ou avec petites simplifications : Bulletin (6), Cours (9), Devoir (9), Evaluation (9), Seance (9), AbsenceApprenant (11), AbsenceEnseignant (10), ExamFinance (9), MoyenneMatiere (7), PlanificationExamen (11), Presence (5), Passage (5), PersonnelAdministratif (7).

**Redondances internes détectées** :
- **Presence vs AbsenceApprenant** : deux sources de vérité pour l'absentéisme des élèves. La Présence gère `absent/malade/permis` par séance, l'Absence gère les mêmes états mais sur plage libre. **À unifier** : garder Presence comme source unique, AbsenceApprenant devient une vue agrégée.
- **JustificatifAbsence redondant** : `AbsenceApprenant.justificatif_path` et `AbsenceEnseignant.justificatif_path` existent déjà. Le CRUD JustificatifAbsence polymorphique fait doublon.
- **Manuel vs ListeManuels** : `ManuelController::store` et `ListeManuelsController::store` utilisent tous deux `ListeManuels::create()`. Un controller de trop.
- **Evaluation vs Devoir vs PlanificationExamen vs ExamenEnLigne** : 4 entités "épreuve" avec les mêmes champs (titre, matière, classe, date, coefficient). Consolidation possible : entité `Evaluation` unique avec `type` enum (devoir/examen/interro/en_ligne). Gain énorme sur les calculs de moyenne.
- **MoyenneMatiere vs Bulletin.moyenne_generale vs Note** : moyennes recalculées à 3 endroits (`BulletinService`, `AcademicCalculationService`, calcul inline dans `DocumentScolaireController`). Source de bugs de divergence.

**Bugs latents identifiés** :

| Bug | Fichier | Impact |
|---|---|---|
| Typo table `nature_examens` vs `natures_examens` | [NoteController.php:175](Modules/Academique/Http/Controllers/NoteController.php#L175) vs [PlanificationExamenController.php:118](Modules/Academique/Http/Controllers/PlanificationExamenController.php#L118) | **Crash runtime** sur l'un des deux |
| Race condition unicité bulletin (apprenant+classe+période+année vérifié en PHP sans lock) | [BulletinController.php:155-165](Modules/Academique/Http/Controllers/BulletinController.php#L155-L165) | Doublons en saisie concurrente |
| `mot_de_passe` examen en ligne stocké en CLAIR | [ExamenEnLigneController.php:111](Modules/Academique/Http/Controllers/ExamenEnLigneController.php#L111) | **Faille sécurité** |
| `Cours.code` UNIQUE global (pas per école/année) | [CoursController.php:126](Modules/Academique/Http/Controllers/CoursController.php#L126) | Collision multi-tenant |
| `moyenne = 20 × coefficient` accepté | [MoyenneMatiereController.php:162](Modules/Academique/Http/Controllers/MoyenneMatiereController.php#L162) | Moyenne à 100 possible si coef=5 |
| Aucune unicité (apprenant_id, seance_id) sur `presences` | [PresencesController.php:87](Modules/Academique/Http/Controllers/PresencesController.php#L87) | 5 présences pour la même séance possibles |
| Note normalisée mais originale figée : si `evaluation.sur` change, incohérence | [NoteController.php:192](Modules/Academique/Http/Controllers/NoteController.php#L192) | Corruption silencieuse |
| Changement classe = simple `update(classe_id)`, pas d'historique | [ClassesApprenantsController.php:129-135](Modules/Academique/Http/Controllers/ClassesApprenantsController.php#L129) | Perte de traçabilité en cours d'année |

---

### 10.2 · Module `Parametrage` (48 CRUD → objectif 34)

**Recommandation générale** : après nettoyage, on passe de 48 à **~34 CRUD (-30%)**. Détails :

**À DROPPER (8 CRUD)** :
1. `Niveau` (table `niveaux`) — doublon confirmé de `NiveauEtude`. `Classe.niveau()` pointe déjà explicitement vers `niveaux_etudes` ([Classe.php:54](Modules/Parametrage/Entities/Classe.php#L54))
2. `Civilite` (0 FK entrante, doublon de `TitreCivilite`)
3. `Zone` (0 FK entrante)
4. `Langue` (0 FK, aucune utilisation)
5. `TypeEtablissementSpe` (0 FK, doublon confirmé)
6. `CategorieApprenant` (0 FK, TypeApprenant + StatutApprenant suffisent)
7. `TypeContrat` (le user a tranché — garder NatureContrat comme dans §1.3)
8. `GroupeMatiere` (10 slots `matiere_1_id`..`matiere_10_id` hardcodés — même anti-pattern qu'AffectationEnseignant). **À refactorer en pivot** `groupe_matiere_items`, pas dropper.

**Note divergence** : l'agent d'audit a recommandé de dropper `MatiereUnite` (Paramétrage) au profit de `Matiere` (Académique) — **INVERSE** de ta décision. Tu as tranché §1.1 : c'est bien `Matiere` qui saute. Ordre respecté dans le plan.

**Doublons annexes trouvés** :
- `NatureContrat.duree_mois` et `regime_travail` (colonnes métier qui n'existent pas sur `TypeContrat`) — à **porter** dans NatureContrat lors du drop de TypeContrat.
- `GroupeSanguin`, `LienParente`, `SituationMatrimoniale`, `TypeInscription`, `TypeRessource`, `TypeEvenementAgenda` : référentiels dormants (0 FK). Soit brancher côté Apprenant/Enseignant, soit archiver.

**Dénormalisation** : `MatiereUnite`, `NatureExamen`, `TypeExamen`, `TypeApprenant`, `GroupeMatiere` portent tous les 3 FK `cycle_id + section_id + niveau_id` en même temps. Puisque `niveau → section → cycle`, ne garder que `niveau_id` et déduire le reste par relation.

**Institution** : entité racine à **60+ colonnes** (KYC, comptabilité, signataire, adresse siège, RIB, cachet, logo, TVA, etc.) → à refondre en stepper 5 étapes.

**Écoles** : 45+ colonnes (idem, gigantesque) → stepper 5 étapes.

---

### 10.3 · Module `Personnel`

**3 controllers** (Parent 531 lignes, Accompagnateur 442, Tuteur 309) + `PersonnelAdministratifController` **mal placé dans `Modules/Academique/`** (à déplacer).

**Formulaires** :

| Formulaire | Fichier | Nb champs | Priorité | Action |
|---|---|---|---|---|
| Parent | [ParentForm.vue](Modules/Personnel/Resources/js/Pages/Parents/ParentForm.vue) — **761 lignes** | 79 (déjà simplifié à 63 après nos travaux) | **P0** | Stepper 4 steps (voir §2.1). Extraire composant partagé `<PersonneAvecApprenants>` |
| Accompagnateur | [AccompagnateurForm.vue](Modules/Personnel/Resources/js/Pages/Accompagnateurs/AccompagnateurForm.vue) — **610 lignes** | 18 | P1 | Blocs Accompagnant 1/2/3 identiques triplés en copier-coller → même composant `<PersonneAvecApprenants>` que Parent |
| Tuteur | [TuteurForm.vue](Modules/Personnel/Resources/js/Pages/Tuteurs/TuteurForm.vue) — 167 lignes | 9 | OK | Modèle propre à suivre |

**Redondances structurelles** : Parent + Accompagnateur = **~800 lignes copiées-collées** qui pourraient être un composant unique. Rôles métier différents (parent légal vs personne autorisée à récupérer l'enfant) mais formulaire structurellement identique.

**Bugs** :
- `console.log('ParentForm component loading...')` laissé en prod ([ParentForm.vue:9,43](Modules/Personnel/Resources/js/Pages/Parents/ParentForm.vue#L9))
- 77 des 79 champs Parent n'affichent AUCUN message d'erreur de validation côté frontend (seuls `apprenant_ids` et `etat` remontent les erreurs)

---

### 10.4 · Module `Finances` — **le plus dégradé**

**20 controllers, 17 formulaires, 5 sous-domaines** : Ecolage, Dépenses, Recettes, Paie, Comptabilité.

**Formulaires stubs cassés (5)** — chacun a un form 3-champs (nom/code/statut) alors que l'entité PHP en attend 6-10. Résultat : création UI **impossible** ou **silencieusement invalide** :

| Form | Entité attend | Bug |
|---|---|---|
| PaiementForm.vue (3 champs) | Aucune entité `Paiement` existe | **Route cassée** `finances.paiement.index` (réelle: `paiements.index`) |
| DepenseForm.vue (3 champs) | `libelle, categorie, montant_cents, date_depense, facture_id, ecole_id, auteur_id` tous required | Création UI **impossible** |
| EcheancierForm.vue (3 champs) | Entité stub aussi | Route `finances.echeancier.index` cassée |
| FraisForm.vue (3 champs) | Doublonne TypeFrais | À supprimer |
| TypeFraisForm.vue (3 champs) | Doublonne Frais + PosteRecette | À supprimer |

**Formulaires massifs à passer en stepper** :

| Formulaire | Fichier | Nb champs | Action |
|---|---|---|---|
| FacturationApprenant | `Modules/Finances/Resources/js/Pages/FacturationApprenant/FacturationApprenantForm.vue` | **~47** dont 24 versements 1..12 | **P0 CATASTROPHE** : voir bug bloquant §11 |
| Salaire | `Modules/Finances/Resources/js/Pages/Salaires/SalaireForm.vue` | **~35** dont 12 avances 1..4 | Stepper 4 steps + lignes dynamiques pour avances. Champs calculés (salaire_brut/net/total_payé/restant) à retirer du form |
| Versement | `Modules/Finances/Resources/js/Pages/Versements/VersementForm.vue` | **~37** | Doublonne FacturationApprenant — fusionner |
| AchatDepense | `Modules/Finances/Resources/js/Pages/AchatsDepenses/AchatDepenseForm.vue` | **~24** dont 12 paiements 1..6 | Stepper 3 steps + lignes dynamiques |
| AutreRevenu | `Modules/Finances/Resources/js/Pages/AutresRevenus/AutreRevenuForm.vue` | 13 (9 lignes fixes autre1..6 + uniforme/tenues) | Stepper 2 + lignes dynamiques |

**Redondances fonctionnelles** :
- **Frais vs TypeFrais vs PosteRecette** : 3 entités quasi-identiques → fusionner Frais et TypeFrais, garder PosteRecette pour la comptabilité analytique
- **Ecolage.frais_dossier/inscription/scolarite (colonnes fixes) vs EcolageFrais (lignes dynamiques)** : double modélisation dans la même entité → les 3 colonnes sont mortes
- **FacturationApprenant vs Versement** : les 2 stockent frais + total_paye + restant + versements 1..12 → **Versement = doublon**
- **Depense vs AchatDepense** : deux entités dépenses aux schémas incompatibles. Depense est un stub, à supprimer
- **Paiement vs Versement vs paiements_1..6 vs avances_1..4** : 4 façons différentes de stocker des paiements échelonnés → unifier via table `paiements` polymorphe

---

### 10.5 · Module `RessourcesLogistique`

**13 controllers**, formulaires globalement OK sauf 3 stubs. **BLOQUANT** : collision de table `bibliotheques` (voir §11).

**Formulaires stubs** (3 champs nom/code/statut au lieu du fillable) :
- [BibliothequeForm.vue](Modules/RessourcesLogistique/Resources/js/Pages/Bibliotheques/BibliothequeForm.vue) : entité attend `ecole_id, adresse, capacite, responsable_id`
- [MaintenanceEquipementForm.vue](Modules/RessourcesLogistique/Resources/js/Pages/MaintenancesEquipements/MaintenanceEquipementForm.vue) : entité attend `equipement_id, type_maintenance, cout_cents, technicien_id, description`
- [CategorieEquipementForm.vue](Modules/RessourcesLogistique/Resources/js/Pages/CategoriesEquipements/CategorieEquipementForm.vue) : stub

**Auto-fill possibles** :
- `Exemplaire.code_exemplaire` : générer depuis `ouvrage_id + N° incrément` (`OUV-XXX-001`)
- `Emprunt.date_retour_prevue` : `date_emprunt + durée_pret` (paramètre bibliothèque)
- `Reservation.date_expiration` : `date_reservation + N jours`
- `Exemplaire.date_derniere_maintenance` : depuis MaintenanceEquipement la plus récente

**Bugs** : pas de validation `date_retour_prevue > date_emprunt`, ni `date_expiration > date_reservation`.

**Factorisation** : 3 formulaires `Categorie*` quasi-identiques → factoriser en `CategorieForm` générique.

---

### 10.6 · Module `Services`

**8 controllers, 8 entités** — dont un **doublon Menu/MenuCantine** à supprimer.

**Formulaires prioritaires** :

| Formulaire | Fichier | Nb champs | Action |
|---|---|---|---|
| ServiceCantine | [ServiceCantineForm.vue](Modules/Services/Resources/js/Pages/ServicesCantines/ServiceCantineForm.vue) | **16** | Stepper 5 steps (Base / Scolarité / Tarifs / Dates / État) |
| ServiceTransport | [ServiceTransportForm.vue](Modules/Services/Resources/js/Pages/ServicesTransports/ServiceTransportForm.vue) | **19** dont 10 `point_N` | Stepper + remplacer les `point_1..10` par tableau dynamique `waypoints[]` |
| Menu | ~~[MenuForm.vue](Modules/Services/Resources/js/Pages/Menus/MenuForm.vue)~~ | 2 + grille | **DOUBLON de MenuCantine — supprimer** |
| PassageCantine | [PassageCantineForm.vue](Modules/Services/Resources/js/Pages/PassagesCantines/PassageCantineForm.vue) | 3 (stub) | Entité attend `inscription_cantine_id, date_passage, heure_passage` |
| ConsultationInfirmerie | [ConsultationInfirmerieForm.vue](Modules/Services/Resources/js/Pages/ConsultationsInfirmeries/ConsultationInfirmerieForm.vue) | 3 (stub) | Entité attend `apprenant_id, date_consultation, motif, diagnostic, traitement, infirmier_id` |

**Bugs** :
- `PassageCantineController::store` valide `apprenant_id, menu_id, montant_cents, statut` mais aucun n'existe dans `PassageCantine::$fillable` → `create()` va throw
- `PassageCantineController::index` fait `whereHas('apprenant.user')` alors que le modèle n'a pas de relation `apprenant`
- Route model binding cassé pour PassageCantine (workaround "MANUAL FIX")
- `InscriptionTransportForm` : `SearchableSelect` prop `label` référence `item` avant définition
- `ConsultationInfirmerieForm` : lien `route('consultation-infirmerie.index')` vs nom réel `consultations-infirmeries.index`

---

### 10.7 · Module `Communication`

**5 controllers, 5 entités**. Formulaires courts mais **entièrement décalés** des backends.

**Bugs critiques** :

| Bug | Fichier | Impact |
|---|---|---|
| **`NotificationController::store` utilise règle Laravel INEXISTANTE `date_time`** | NotificationController | **Crash runtime Laravel** — la règle n'existe pas |
| NotificationForm envoie `contenu, lue, etat` ; entité attend `message, lu_at` | Notification | Aucun mapping, saisie perdue |
| NotificationController champ `destinataire_id` alors que fillable = `user_id` | idem | idem |
| AnnonceForm envoie `date_fin_publication` alors que l'entité stocke `date_expiration` | Annonce | Aucun mapping |
| CommentaireAnnonceForm : `annonce_id` absent | Commentaire | Hidden ailleurs à vérifier |

**Recommandations** :
- Corriger `date_time` → `date` dans NotificationController
- Aligner form ↔ entité pour Notification et Annonce
- `Annonce.auteur_id` = `auth()->id()` (non exposé côté form)
- `Message.expediteur_id` = `auth()->id()`
- Vérifier collision de `Traduction` (custom i18n ?) avec `vue-i18n` déjà utilisé partout

---

### 10.8 · Module `Administration`

**8 controllers** : Users (**656 lignes**), Roles (250), Features (241), Permissions (188), Sessions (189), Modules (164), ErrorLog (116), ChangePassword (69).

**RBAC bien structuré** via Spatie (Module → Feature → Permission → Role). Auto-création des 5 permissions CRUD (`-list, -create, -edit, -statut, -pdf`) au store d'une Feature (bon !).

**UserForm — le problème** :
- **670 lignes, 20 v-model actifs sur 23 champs** : nom, prenoms, tel, email, pays_id, role_id, statut, kyc_status, alias_smil, uuid (RO), full_login (RO), code_owner (RO), code_parrain (RO), type_piece, numero_piece, date_delivrance, lieu_delivrance, date_naissance, lieu_naissance, adresse, photoprofile, piecerecto, pieceverso
- **Bien trop pour une création admin** — mélange identité + KYC + pièce d'identité + statut + rôle en 1 seul écran
- `onMounted` et `watch(props.user)` **dupliquent littéralement** le mapping des 17 champs
- **Bugs** : pas de `unique:users,email`, pas de `unique:users,login`, `numero_piece` sans unicité

**Action** :
- Stepper 4 steps : Identité / Rôle & Statut / KYC (pièce) / Photo & Contact
- Extraire un `UserRegistrationService` hors de `UserController::store` (Hash password, QrCode, codeOwner, uuid, PasswordResetService::sendResetLinkSms)
- Ajouter les 3 unicités manquantes

---

### 10.9 · Modules `Rapport` + `SuiviAnalyse` — à FUSIONNER

**État** :
- **Rapport** = 3 controllers de **dashboards read-only** (agrégations DB avec Chart.js). Routes uniquement `index`
- **SuiviAnalyse** = CRUD sur 2 entités (Rapport, ModeleRapport). Formulaires **totalement déconnectés** du backend

**Bugs bloquants** :

| Bug | Détail |
|---|---|
| **Collision de route name `rapports.index`** | SuiviAnalyse/routes/web.php:24 vs Rapport/routes/web.php:11 → un des dashboards devient inaccessible selon l'ordre de chargement |
| RapportForm gère 3 champs (nom/code/statut) alors que `RapportController::store` en valide **11** (modele_rapport_id, titre, description, auteur_id, date_generation, periode_debut, periode_fin, contenu, fichier_url, type, statut, tous required sauf 4) | **Chaque soumission → 422 immédiat** |
| ModeleRapportForm : même problème (3 champs vs 8 attendus) | Idem |
| `Rapport/StatistiquesEcole/StatistiquesEcoleForm.vue` = **copie 100% identique** de `StatistiquesClasses/StatistiquesClassesForm.vue` (176 vs 176 lignes) | Code mort |
| Index Rapport lie `route('rapport.statistiques-ecole.index')` mais le nom réel est `statistiques-ecole.index` | Lien mort |
| 8 fichiers Vue morts (StatistiquesClasses + StatistiquesEcole : Create.vue, Edit.vue, Show.vue, Form.vue chacun) | Aucune route ne les instancie |

**Recommandation FUSION** : Rapport + SuiviAnalyse → 1 module `Rapport` avec 2 sous-domaines :
- `Dashboards/` (read-only, chartés)
- `Documents/` (Rapports + ModelesRapports CRUD)

Supprimer les 8 Vue mortes.

---

## 11 · Bugs bloquants transversaux (à traiter en Phase 0)

Ces bugs sont **au-delà de la dette SmilPay** — ils cassent effectivement l'usage courant :

### 11.1 Collision table `bibliotheques` — **CRITIQUE**
- `Academique/Bibliotheque` (catalogue livres : sujet, langue, type_manuel, quantite, auteurs) et `RessourcesLogistique/Bibliotheque` (lieu physique : ecole_id, adresse, capacite, responsable_id) pointent tous deux sur `bibliotheques`
- La migration `2026_02_09_152200_create_bibliotheques_table` crée le schéma **Académique** → `RessourcesLogistique\Bibliotheque` est **cassé au runtime**
- **Décision requise** : garder Academique (rapide accès prof/élève) ou RessourcesLogistique (plus cohérent gestion) ? Recommandation : garder RL (Ouvrage/Exemplaire/Emprunt couvrent tout mieux), migrer les données Academique/Bibliotheque + EntreeLivre + SortieLivre → Ouvrage/Exemplaire.

### 11.2 FacturationApprenant Form ↔ Entity — **DONNÉES PERDUES**
- Le form saisit : `apprenant_id, classe_id, frais_dossier, frais_inscription, frais_scolarite, total_paye, restant_a_payer, nature_versement_1..12, montant_versement_1..12`
- Entité `FacturationApprenant.php` fillable = `code, libelle, ligne_recette, unite_facturation, quantite, montant, date_debut/fin_exigibilite, compte_comptable`
- **AUCUN champ ne matche** → toutes les données saisies sont **silencieusement perdues**
- **Action immédiate** : soit refondre l'entité pour matcher le form, soit refondre le form pour matcher l'entité. Choisir un modèle unique puis migrer les données existantes.

### 11.3 Collision de route `rapports.index`
- Rapport module + SuiviAnalyse module déclarent tous deux `rapports.index`
- Un des 2 dashboards devient inaccessible aléatoirement selon l'ordre de chargement Nwidart
- **Fix** : préfixer les routes par module (`rapport.dashboard.index` + `suivi-analyse.rapports.index`)

### 11.4 NotificationController — règle Laravel inexistante
- `NotificationController::store` valide avec règle `date_time` — cette règle **n'existe pas** en Laravel
- Toute création de notification via UI crash immédiatement
- **Fix** : `date_time` → `date` + aligner tous les autres champs sur `Notification::$fillable`

### 11.5 Typo `nature_examens` vs `natures_examens`
- `NoteController.php:175` valide `exists:nature_examens,id` (singulier)
- `PlanificationExamenController.php:118` valide `exists:natures_examens,id` (pluriel)
- Un des deux **crash en runtime** (la table s'appelle `natures_examens` au pluriel dans les migrations)

### 11.6 `PersonnelAdministratifController` mal placé
- Physiquement dans `Modules/Academique/Http/Controllers/` alors qu'il traite d'agents administratifs
- **Fix** : déplacer dans `Modules/Personnel/` + adapter routes + tests

### 11.7 Modèle Depense vs Form Depense — création impossible
- Form saisit `{nom, code, statut}` mais entité `Depense::$fillable` = `{libelle, categorie, montant_cents, date_depense, facture_id, ecole_id, auteur_id}` (tous required sans default)
- **Résultat** : SQL error `Field 'libelle' doesn't have a default value` à chaque création
- **Fix** : refondre le form + valider correctement + éventuellement supprimer Depense (voir §10.4)

### 11.8 5 formulaires stubs 3-champs à travers 3 modules
- Finances : Paiement, Depense, Echeancier, Frais, TypeFrais
- RessourcesLogistique : Bibliotheque, MaintenanceEquipement, CategorieEquipement
- Services : PassageCantine, ConsultationInfirmerie
- SuiviAnalyse : Rapport, ModeleRapport

**Pattern commun** : `{nom, code, statut}` copié-collé, entité attend beaucoup plus. Résultat : soit crash SQL, soit données perdues silencieusement.

### 11.9 Routes cassées (nom singulier/pluriel)
Récap dans un tableau :

| Form | Nom utilisé | Nom réel |
|---|---|---|
| PaiementForm | `finances.paiement.index` | `finances.paiements.index` |
| DepenseForm | `finances.depense.index` | `finances.depenses.index` |
| EcheancierForm | `finances.echeancier.index` | `finances.echeanciers.index` |
| TypeFraisForm | `finances.type-frais.index` | `finances.types-frais.index` |
| ConsultationInfirmerieForm | `consultation-infirmerie.index` | `consultations-infirmeries.index` |
| Rapport Statistiques Ecole | `rapport.statistiques-ecole.index` | `statistiques-ecole.index` |

---

## 12 · Composants Vue partagés à extraire

Beaucoup de duplications structurelles pourraient être réduites via des composants réutilisables :

| Composant | Utilisé par | Économie estimée |
|---|---|---|
| `<PersonneAvecApprenants>` | Parent (4× Père/Mère/Tuteur1/Tuteur2), Accompagnateur (3×), Tuteur | **~800 lignes** dupliquées éliminables |
| `<PhotoUpload>` | AccompagnateurForm (3×), UserForm, EnseignantForm, ApprenantForm | ~200 lignes |
| `<HierarchyContextBar>` (existe déjà) | À propager sur ServiceCantine, ServiceTransport, Equipement, InscriptionCantine, InscriptionTransport, ConsultationInfirmerie | Auto-fill scolarité partout |
| `<TarifsSection>` | ServiceCantine, ServiceTransport (blocs mensuel/trimestriel/semestriel/annuel dupliqués) | ~150 lignes |
| `<ApprenantSelect>` | Emprunt, Reservation, InscriptionCantine, InscriptionTransport, ConsultationInfirmerie, FacturationApprenant, Versement | Format `${nom} ${prenoms}` unifié |
| `<CategorieForm>` générique | CategorieEquipement, CategorieFourniture, CategorieDocument | ~100 lignes |
| `<ChampsPaiement>` (lignes dynamiques) | FacturationApprenant, Versement, AchatDepense, Salaire (avances) | Remplace 24+24+12+12 champs hardcodés |
| `<FormStepper>` (à créer, cf. §2) | Parent, Apprenant, Enseignant, Inscription, User, ServiceCantine, ServiceTransport, ExamenEnLigne, EmploiTemps, Institution, Ecole, FacturationApprenant, Salaire, AchatDepense | Base commune 13+ formulaires |
| `<PersonneFormBlock>` (Nom+Prénoms+Genre+DateNaissance+LieuNaissance+Nationalité+Photo+Adresse) | UserForm, EnseignantForm, ApprenantForm, ParentForm, PersonnelAdministratifForm | Uniformise identités |

**ROI estimé** : ~2000 lignes de Vue supprimées, cohérence UX renforcée.

---

## 13 · Ordre d'exécution recommandé — RÉVISÉ

Le plan §6 est enrichi pour intégrer les bugs bloquants du §11 :

### Phase 0 — Urgences (1 jour)
1. `db:seed --class=NouveauxReferentielsRbacSeeder`
2. Fix bug `menu_url` dash/underscore
3. Purger modules orphelins 15, 19-21, 27
4. **Fix NotificationController** (`date_time` → `date`)
5. **Fix typo nature_examens/natures_examens**
6. **Fix routes cassées** (§11.9, 6 routes)
7. Déplacer `PersonnelAdministratifController` → `Modules/Personnel/`
8. Résoudre collision route `rapports.index`

### Phase 1 — Nettoyage bloquant (semaine 1)
1. Suppression Matière → MatiereUnite (§1.1)
2. Suppression Civilités, TypeContrat (§1.2, §1.3)
3. Suppression Niveau, Zone, Langue, CategorieApprenant, TypeEtablissementSpe (§10.2)
4. Résolution **collision table `bibliotheques`** (§11.1) — migration données Academique vers RL
5. **Fix FacturationApprenant Form↔Entity** (§11.2) — refonte modèle
6. Suppression 5 formulaires stubs Finances + refactorisation entités correspondantes
7. Suppression 3 stubs RessourcesLogistique
8. Suppression 2 stubs Services (PassageCantine, ConsultationInfirmerie)
9. Suppression 2 stubs SuiviAnalyse + fusion Rapport+SuiviAnalyse

### Phase 2 — Composants partagés + Steppers (semaine 2)
1. Créer `<FormStepper>`, `<PersonneAvecApprenants>`, `<HierarchyContextBar>` (existe, propager), `<ApprenantSelect>`, `<ChampsPaiement>`, `<CategorieForm>`, `<TarifsSection>`
2. Refonte 5 formulaires prioritaires (Parent, Apprenant, Enseignant, Inscription, User)

### Phase 3 — Refactor structurels (semaine 3)
1. **AffectationEnseignant** : `matiere_1_id`..`matiere_21_id` → pivot
2. **GroupeMatiere** : `matiere_1_id`..`matiere_10_id` → pivot
3. **Paiements** unifiés en table polymorphe (`versement_1..12`, `paiement_1..6`, `avance_1..4`)
4. **Evaluation/Devoir/Examen** unifiés en `Evaluation.type` enum
5. **Presence/Absence** : Presence = source unique
6. Fusion Rapport + SuiviAnalyse

### Phase 4 — Formulaires massifs restants (semaine 4)
1. Stepper Note (20 → 3-4 champs saisis)
2. Stepper Salaire (35 → dérivations serveur)
3. Stepper FacturationApprenant, ServiceCantine, ServiceTransport, ExamenEnLigne, EmploiTemps, Institution, Ecole
4. Ajout unicités DB (bulletins, presences, users.email/login/numero_piece)

### Phase 5 — RBAC dynamique (semaine 5)
1. Seed features Agree Sikul depuis `DEFAULT_MENU_CONFIG`
2. `TheSidebar.vue` 100% dynamique
3. Purge résidus SmilPay dans `feature` + `module`

### Phase 6 — Tests + CI (semaine 6)
1. Tests unitaires (§5) — objectif 60% Academique/Parametrage/Personnel
2. CI hook pré-push idempotence migrations
3. Hash `mot_de_passe` ExamenEnLigne

### Phase 7 — Optionnel : squash migrations (semaine 7)

---

## Annexes

- Rapport détaillé migration Matière → MatiereUnite : voir §1.1
- Rapport détaillé steppers formulaires : voir §2
- Rapport détaillé par module : voir §10 (9 sous-sections)
- Bugs bloquants transversaux : voir §11 (9 bugs identifiés)
- Composants partagés à extraire : voir §12 (9 composants ~2000 lignes économisées)
- Notes de contexte : `<scratchpad>/_notes_context.md`
