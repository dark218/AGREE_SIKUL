<?php

/**
 * EXEMPLES D'UTILISATION DES FACTORIES
 *
 * Ce fichier contient des exemples pratiques pour utiliser les factories
 * dans les tests et le développement.
 */

// ============================================================================
// EXEMPLES BASIQUES
// ============================================================================

// Créer une institution simple
$institution = Institution::factory()->create();

// Créer 5 campuses
$campuses = Campus::factory(5)->create();

// Créer avec associations
$ecole = Ecole::factory()
    ->for($campus)
    ->create();

// ============================================================================
// EXEMPLES AVEC RELATIONS
// ============================================================================

// Créer une classe avec ses relations
$classe = Classe::factory()
    ->for($ecole)
    ->for($niveau)
    ->create();

// Créer apprenants avec tuteurs et inscriptions
$apprenant = Apprenant::factory()
    ->has(Tuteur::factory(2), 'tuteurs')
    ->has(Inscription::factory(), 'inscriptions')
    ->create();

// Créer une institution avec campus, écoles et classes
$institution = Institution::factory()
    ->has(Campus::factory(2)->has(Ecole::factory(2)), 'campus')
    ->create();

// ============================================================================
// EXEMPLES AVEC ÉTATS
// ============================================================================

// Créer une inscription validée
$inscription = Inscription::factory()->validee()->create();

// Créer un paiement validé
$paiement = Paiement::factory()->valide()->create();

// Créer une présence (absent)
$absence = PresenceSeance::factory()->absent()->create();

// Créer un emprunt retourné
$emprunt = Emprunt::factory()->retourne()->create();

// Créer une annonce publiée
$annonce = Annonce::factory()->publiee()->create();

// Créer une année académique courante
$anneeCourante = AnneeScolaire::factory()->courante()->create();

// ============================================================================
// EXEMPLES COMPLEXES POUR TESTS
// ============================================================================

// Scénario: Écoles avec hiérarchie complète
public function testSchoolHierarchy()
{
    $institution = Institution::factory()
        ->has(
            Campus::factory(2)
                ->has(
                    Ecole::factory(2)
                        ->has(
                            Niveau::factory(3)
                                ->has(Classe::factory(2), 'classes'),
                            'niveaux'
                        ),
                    'ecoles'
                ),
            'campus'
        )
        ->create();

    $this->assertEquals(2, $institution->campus()->count());
    $this->assertEquals(4, $institution->campus[0]->ecoles()->count());
}

// Scénario: Apprenants inscrits avec notes
public function testStudentGrades()
{
    $apprenant = Apprenant::factory()
        ->has(
            Inscription::factory()
                ->validee(),
            'inscriptions'
        )
        ->create();

    $evaluations = Evaluation::factory(5)->create();
    $evaluations->each(function ($eval) use ($apprenant) {
        Note::factory()
            ->for($apprenant)
            ->for($eval)
            ->create();
    });

    $this->assertEquals(5, $apprenant->notes()->count());
}

// Scénario: Cantine avec inscriptions
public function testCantineService()
{
    $cantine = ServiceCantine::factory()
        ->has(Menu::factory(20), 'menus')
        ->create();

    $apprenants = Apprenant::factory(30)->create();
    $apprenants->each(function ($apprenant) use ($cantine) {
        InscriptionCantine::factory()
            ->for($apprenant)
            ->for($cantine)
            ->create();

        PassageCantine::factory(10)
            ->for($apprenant)
            ->for($cantine)
            ->create();
    });

    $this->assertEquals(30, $cantine->inscriptions()->count());
    $this->assertEquals(300, $cantine->passages()->count());
}

// ============================================================================
// EXEMPLES AVEC SEQUENCES
// ============================================================================

// Créer des cours avec la même matière
$cours = Cours::factory(5)
    ->sequence(fn($sequence) => ['matiere_id' => $matiere->id])
    ->create();

// Créer des notes variées
$notes = Note::factory(20)
    ->sequence(
        ['note' => 15],
        ['note' => 18],
        ['note' => 12],
        ['note' => 20]
    )
    ->create();

// ============================================================================
// EXEMPLES POUR FIXTURES DE TESTS
// ============================================================================

// Fixture: Données académiques complètes
public function setupAcademicData()
{
    $anneeCourante = AnneeScolaire::factory()->courante()->create();

    $institution = Institution::factory()
        ->has(Campus::factory(1), 'campus')
        ->create();

    $campus = $institution->campus()->first();

    $ecole = Ecole::factory()
        ->for($campus)
        ->create();

    $niveaux = Niveau::factory(2)
        ->for($ecole)
        ->create();

    $classes = [];
    $niveaux->each(function ($niveau) use ($ecole, &$classes) {
        $classes = array_merge(
            $classes,
            Classe::factory(2)
                ->for($ecole)
                ->for($niveau)
                ->create()
                ->toArray()
        );
    });

    $matieres = Matiere::factory(6)
        ->for($ecole)
        ->create();

    return compact('institution', 'campus', 'ecole', 'niveaux', 'classes', 'matieres', 'anneeCourante');
}

// ============================================================================
// EXEMPLES POUR SEEDING
// ============================================================================

// Dans un Seeder ou Tinker:

// Créer une structure complète
foreach (range(1, 5) as $i) {
    Institution::factory()
        ->has(
            Campus::factory(2)
                ->has(
                    Ecole::factory(2)
                        ->has(Matiere::factory(8), 'matieres')
                        ->has(
                            Niveau::factory(3)
                                ->has(
                                    Classe::factory(2)
                                        ->afterCreating(function ($classe) {
                                            Apprenant::factory(20)
                                                ->has(
                                                    Inscription::factory()
                                                        ->validee(),
                                                    'inscriptions'
                                                )
                                                ->create();
                                        }),
                                    'classes'
                                ),
                            'niveaux'
                        ),
                    'ecoles'
                ),
            'campus'
        )
        ->create();
}

// ============================================================================
// COMMANDES UTILES EN TINKER
// ============================================================================

/*
php artisan tinker

// Créer rapidement des données
Apprenant::factory(100)->create()

// Vérifier les données
Institution::count()
Campus::count()
Apprenant::count()

// Requêtes utiles
Institution::with('campus.ecoles.classes.inscriptions')->get()
Apprenant::whereDoesntHave('inscriptions')->get()
Classe::with('inscriptions.apprenant')->get()

// Tester les permissions
$user = User::first();
$user->givePermissionTo('apprenants-list');

// Exporter les données
Apprenant::all()->map(fn($a) => $a->only(['id', 'matricule', 'statut']))
*/

// ============================================================================
// PATTERNS RECOMMANDÉS
// ============================================================================

/**
 * Pattern 1: Tester une relation
 */
public function testApprenantHasTuteurs()
{
    $apprenant = Apprenant::factory()
        ->has(Tuteur::factory(2), 'tuteurs')
        ->create();

    $this->assertCount(2, $apprenant->tuteurs);
}

/**
 * Pattern 2: Tester avec états
 */
public function testInscriptionStatuses()
{
    $validee = Inscription::factory()->validee()->create();
    $enAttente = Inscription::factory()->create();

    $this->assertEquals('validee', $validee->statut);
    $this->assertNotEquals('validee', $enAttente->statut);
}

/**
 * Pattern 3: Boucle créant relations complexes
 */
public function testBulletinsForYear()
{
    $annee = AnneeScolaire::factory()->courante()->create();
    $apprenants = Apprenant::factory(10)->create();

    $apprenants->each(function ($apprenant) use ($annee) {
        foreach ([1, 2, 3] as $trimestre) {
            Bulletin::factory()
                ->for($apprenant)
                ->state(['trimestre' => $trimestre])
                ->for($annee)
                ->create();
        }
    });

    $this->assertEquals(30, Bulletin::count());
}

/**
 * Pattern 4: Créer des données avec contexte
 */
public function setupTestData()
{
    $institution = Institution::factory()->create();
    $campus = Campus::factory()->for($institution)->create();
    $ecole = Ecole::factory()->for($campus)->create();
    $niveau = Niveau::factory()->for($ecole)->create();
    $classe = Classe::factory()
        ->for($ecole)
        ->for($niveau)
        ->create();

    $apprenants = Apprenant::factory(10)->create();
    $annee = AnneeScolaire::factory()->courante()->create();

    $apprenants->each(function ($apprenant) use ($classe, $annee) {
        Inscription::factory()
            ->for($apprenant)
            ->for($classe)
            ->validee()
            ->for($annee)
            ->create();
    });

    return [
        'institution' => $institution,
        'campus' => $campus,
        'ecole' => $ecole,
        'classe' => $classe,
        'apprenants' => $apprenants,
        'annee' => $annee
    ];
}
