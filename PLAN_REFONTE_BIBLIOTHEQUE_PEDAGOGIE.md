# Plan de refonte — Bibliothèque & Pédagogie

> État : **PLAN À VALIDER** — aucune ligne de code écrite. Basé sur un inventaire réel du dépôt (2026-07).
> Principe directeur demandé : **zéro redondance de champ**, **remontée auto des données liées (chaîne hiérarchique)**, **UX/UI soignée (stepper si formulaire long)**.

---

## 0. Résumé exécutif

La spec couvre 7 sujets. Après inventaire du code, voici la réalité :

| Sujet spec | État réel | Travail |
|---|---|---|
| Menu « Document » → « Bibliothèque » | Bibliothèque déjà dans module **RessourcesLogistique** (Bibliotheque/Ouvrage/Exemplaire/Emprunt). Menu piloté par la table `feature` (backend). | **Restructurer le menu** (seeder + data) |
| Bibliothèque › **Liste** (Code, Libellé, Localisation, Campus, Responsable, Statut) | Table `bibliotheque_structures` **créée** avec exactement ces colonnes, mais **aucun code** (ni contrôleur, ni Vue, ni route). | **À implémenter (CRUD complet)** |
| Bibliothèque › **Entrée de livres** | Table `entrees_livres` **créée** (type_entree emprunt/achat/don…), **aucun code**. | **À implémenter** |
| Bibliothèque › **Sortie de livres** | Table `sorties_livres` **créée** (type_sortie pret/vente/don…), **aucun code**. | **À implémenter** |
| Bibliothèque › **Inventaire** | Aucune table/vue dédiée. C'est une **vue agrégée** (stock = entrées − sorties). | **À implémenter (lecture seule)** |
| **Manuels & fournitures** | Déjà implémenté (3 blocs répétables : Livres / Cahiers / Autres fournitures). | **Ajustements mineurs** |
| **Emploi du temps** — renommer « Définition de la semaine » → « Définition des périodes » | Le form actuel est un stepper ; le libellé du step 1 est « Semaine » (pas « Définition de la semaine »). | **Renommage + réordonnancement** |
| **Note** — onglets Contexte / Résultat | Déjà 2 étapes (Contexte / Résultat) via `FormStepper`. | **Réordonnancement des champs** |
| **Absences** — ordre des champs + retirer « Absences » du menu AUTRES | Absences Apprenant **déjà supprimé** ✅ ; Absences Enseignant existe. | **Réordonnancement des champs** |

**Conclusion :** ~70 % du gros travail = **implémenter la Bibliothèque (Liste + Entrée + Sortie + Inventaire)**. Le reste = ajustements UX/ordre de champs sur des écrans existants.

---

## 1. Briques réutilisables — DÉJÀ EXISTANTES (à réutiliser, ne pas recréer)

L'architecture « anti-redondance + cascade » demandée existe **déjà en partie**. On la réutilise :

| Brique | Fichier | Rôle |
|---|---|---|
| Stepper générique | `resources/js/Components/Common/FormStepper.vue` | Wizard multi-étapes (déjà utilisé par Apprenant, EmploiTemps, Note) |
| Cascade géo | `resources/js/Composables/useGeoCascade.js` | Quartier → Commune → Département → Région → Pays |
| Cascade classe | `resources/js/Composables/useClasseCascade.js` (+ `Modules/Academique/.../useClasseAutoFill.js`) | Classe → École, Campus, Section, Cycle, Année |
| Cascade école | `resources/js/Composables/useEcoleCascade.js` | École → Campus, Institution |
| Barre contexte | `HierarchyContextBar.vue` / `InheritedContextBar.vue` | Affiche le contexte hérité en lecture seule |
| Select recherche | `SearchableSelect.vue` | Select unique (avec X + réouverture, corrigés) |
| Barre de filtres | `FilterBar.vue` | Barre de recherche unifiée (déjà déployée sur ~125 listes) |

**Chaîne hiérarchique effective (confirmée par les modèles) :**
```
Campus → École → (Section / NiveauEtude / CycleEnseignement) → Classe → Apprenant
                          Année scolaire = transversale
```
La table `classes` porte **toutes** les FK (ecole_id, campus_id, section_id, niveau_id, cycle_id, annee_scolaire_id) → d'où le pattern **« je choisis la Classe et tout le reste se remplit »**. C'est la logique à généraliser.

### Brique NOUVELLE à créer (petite) : `ContexteScolaire.vue`
Un composant qui encapsule le bloc « Année / École / Campus / Cycle / Niveau / Section / Classe » avec la cascade bidirectionnelle, pour les écrans **sans classe** (Manuels, Écolage, EDT) qui répètent ce bloc à la main. Il s'appuie sur les composables existants. **~1 fichier**, réutilisé par 4-5 écrans.

---

## 2. Décisions à trancher AVANT de coder (arbitrages)

> Ces points changent l'implémentation — à valider par toi.

1. **« Bibliothèque › Liste » = quelle table ?**
   La spec (Code, Libellé, Localisation, Campus, Responsable, Statut) correspond **exactement** à `bibliotheque_structures`, PAS à l'entité `Bibliotheque` existante (nom, ecole_id, responsable_id, capacite, adresse).
   → **Option A (recommandée)** : « Liste » = CRUD sur `bibliotheque_structures` (colle à la spec). On garde l'entité `Bibliotheque` (RL) pour le catalogue d'ouvrages, ou on fusionne plus tard.
   → **Option B** : renommer/adapter `Bibliotheque` existante aux colonnes de la spec (plus destructif).

2. **Livres des Entrées/Sorties : catalogue ou saisie libre ?**
   Les champs « Titre, Auteur(s), Éditeur(s), Langue, Sujet/Matière, Année d'édition » existent déjà sur `Ouvrage` (RL). 
   → **Option A (recommandée, anti-redondance)** : l'Entrée référence un **Ouvrage** existant (select) → titre/auteur/éditeur remontent **auto**. On ne re-saisit que ce qui est propre à l'entrée (type, quantité, date, tiers, état physique).
   → **Option B** : re-saisir tous les champs livre à chaque entrée (redondant — déconseillé, contraire à ta demande).

3. **Inventaire = calcul ou table ?**
   → **Recommandé** : vue **calculée** en lecture seule : `Stock disponible = Σ quantités entrées − Σ quantités sorties`, groupé par ouvrage. Pas de nouvelle table.

4. **Emploi du temps — « Définition de la semaine »** : ce libellé n'existe plus tel quel dans le form actuel (step = « Semaine »). → Confirmer : on renomme le step **« Semaine » → « Définition des périodes »** et on réordonne selon la spec ? (Le form actuel est orienté « semaine » ; la spec est orientée « périodes ».)

5. **Menu Bibliothèque** : la spec veut **1 fonctionnalité « Bibliothèque »** avec 4 sous-features (Liste, Entrée, Sortie, Inventaire). Le menu est piloté par la table `feature`. → On crée un **sous-groupe « Bibliothèque »** dans la sidebar (comme les sous-modules Paramétrage/Académique déjà faits) regroupant ces 4 features.

---

## 3. Phases d'implémentation

### PHASE 0 — Décisions (toi) + petite fondation
- Valider les 5 arbitrages ci-dessus.
- Créer le composant `ContexteScolaire.vue` (bloc hiérarchique réutilisable).
- **Livrable :** 1 composant + décisions figées. **Risque : faible.**

### PHASE 1 — Bibliothèque (le gros morceau) 🎯
Ordre interne :
1. **Menu** : « Document » → « Bibliothèque » + sous-groupe (Liste / Entrée / Sortie / Inventaire) dans la sidebar + seeder `feature`.
2. **Liste** (`bibliotheque_structures`) : Model + Controller + Routes + Vue (Index avec `FilterBar` + Create/Edit/Show/Form). Champs : Code, Libellé, Localisation, **Campus** (select), **Responsable**, Statut. (Campus → remontée auto Institution possible.)
3. **Entrée de livres** : Model `EntreeLivre` + Controller + Routes + Vue. Champs : Bibliothèque(structure), **Ouvrage** (select → titre/auteur/éditeur/langue/sujet/année **auto**), Type d'entrée (emprunt/achat/don), Date d'entrée, Quantité, Date de retour, Prêteur/Vendeur/Donateur, État physique, Statut.
4. **Sortie de livres** : idem avec Type de sortie (prêt/vente/don), Emprunteur/Acheteur/Donateur. + **décrément du stock**.
5. **Inventaire** : vue tableau lecture seule (Titre, Sujet, Langue, Auteur, Éditeur, Année, **Quantité initiale**, **Sorties/Prêts**, **Stock disponible**) calculée entrées−sorties.
- **Livrable :** module Bibliothèque complet. **Risque : moyen** (nouveau code, mais tables prêtes). **Effort : le plus gros.**

### PHASE 2 — Ajustements UX des écrans existants (quick wins)
- **Emploi du temps** : renommer step « Semaine » → « Définition des périodes » + réordonner (Année, Niveau, Section, Cycle, École, Campus, Institution, Période, Libellé, Date début, Date fin, Durée, Statut).
- **Note › Contexte** : réordonner (Année, Classe, Niveau, Section, Cycle, École, Campus, Institution, Période, Nature d'examen, Type d'examen, Matière, Groupe de matière, Enseignant, Note max prévue, Note normalisée) ; Onglet Résultat = tableau (Apprenant × Note / Mention / Observation) — vérifier que « Mention/Observation » existent (aujourd'hui `remarques`).
- **Absence Enseignant** : réordonner (Classe, Matière, Enseignant, Date début, Date fin, Durée, Motif, Statut, Justificatif, État).
- **Manuels & fournitures** : vérifier l'ordre des blocs et brancher `ContexteScolaire` sur l'en-tête (au lieu des 6 selects manuels).
- **Écolage & frais** : brancher `ContexteScolaire` sur le bloc de base (déjà de l'auto-fill via niveau) — cohérence.
- **Livrable :** écrans alignés spec. **Risque : faible** (réordonnancement + réutilisation).

### PHASE 3 — Harmonisation (optionnel, si tu veux pousser la cohérence)
- Généraliser `ContexteScolaire` partout où le bloc est répété.
- Résoudre les incohérences enum relevées (Exemplaire etat/statut ; EmploiTemps statut) — dette technique.
- Arbitrer le doublon `ApprenantForm.vue` (Academique vs Parametrage).

---

## 4. Mapping spec → fichiers (aide-mémoire d'implémentation)

| Fonctionnalité | Fichiers à créer / modifier |
|---|---|
| Menu Bibliothèque | `resources/js/Components/Layout/TheSidebar.vue` (sous-groupe) + `database/seeders/TFeatureSeeder.php` + migration data |
| Biblio › Liste | `Modules/RessourcesLogistique/Entities/BibliothequeStructure.php` (new), `Http/Controllers/BibliothequeStructureController.php` (new), `Routes/web.php` (+groupe), `Resources/js/Pages/BibliothequeStructures/{Index,Create,Edit,Show,Form}.vue` (new) |
| Biblio › Entrée | `Entities/EntreeLivre.php`, `Http/Controllers/EntreeLivreController.php`, Vues `EntreesLivres/*`, route |
| Biblio › Sortie | `Entities/SortieLivre.php`, `Http/Controllers/SortieLivreController.php`, Vues `SortiesLivres/*`, route |
| Biblio › Inventaire | `Http/Controllers/InventaireLivresController.php` (index calculé), Vue `InventaireLivres/Index.vue`, route |
| Emploi du temps | `Modules/Academique/Resources/js/Pages/EmploisTemps/EmploiTempsForm.vue` |
| Note | `Modules/Academique/Resources/js/Pages/Notes/NoteForm.vue` (+ Controller si Mention/Observation à ajouter) |
| Absence Enseignant | `Modules/Academique/Resources/js/Pages/AbsencesEnseignants/AbsenceEnseignantForm.vue` |
| Manuels | `Modules/Academique/Resources/js/Pages/ListesManuels/ListeManuelsForm.vue` |
| Écolage | `Modules/Finances/Resources/js/Pages/Ecolage/EcolageForm.vue` |
| Composant partagé | `resources/js/Components/Common/ContexteScolaire.vue` (new) |

---

## 5. Risques, dépendances, déploiement

- **Tables Bibliothèque déjà migrées** (`entrees_livres`, `sorties_livres`, `bibliotheque_structures`) → pas de migration de schéma, on branche du code dessus. ✅
- **Permissions** : chaque nouvelle feature Bibliothèque a besoin de ses permissions (`bibliotheque-structures-list`, etc.) → seeder à compléter, sinon invisible pour non-super-admin.
- **Menu backend** : le menu réel vient de la table `feature` → toute modif menu nécessite **seeder + redeploy** (comme d'habitude Dokploy).
- **Déploiement** : chaque phase = commit + push + **redeploy Dokploy** (`npm build` + `migrate --force` automatiques). Rappel : rien n'apparaît en ligne sans redeploy.
- **Anti-redondance** : la règle d'or = **si une donnée existe déjà sur une entité liée (Ouvrage, Classe…), on la référence par select et on l'affiche en lecture seule — on ne la re-saisit jamais.**

---

## 6. Proposition de démarrage

Je recommande : **valider la Phase 0 (les 5 décisions)** → puis j'attaque la **Phase 1 (Bibliothèque)** écran par écran, avec build vérifié à chaque étape.

👉 Prochaine action attendue : tes réponses aux **5 arbitrages du §2**, en particulier :
- Liste = `bibliotheque_structures` ? (recommandé oui)
- Entrées/Sorties référencent un **Ouvrage** existant ? (recommandé oui — c'est le cœur de l'anti-redondance)
- Inventaire = calculé ? (recommandé oui)
