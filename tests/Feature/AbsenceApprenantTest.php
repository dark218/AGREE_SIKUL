<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\Academique\Entities\Apprenant;
use Modules\Academique\Entities\AbsenceApprenant;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Parametrage\Entities\Classe;
use App\Models\User;

class AbsenceApprenantTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $apprenant;
    protected $matiere;
    protected $classe;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and authenticate
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create test data with proper relationships
        $this->apprenant = Apprenant::create([
            'nom' => 'Dupont',
            'prenoms' => 'Jean',
            'date_naissance' => '2010-03-12',
            'sexe' => 'M',
            'lieu_naissance' => 'Paris',
            'nationalite' => 'Française',
            'groupe_sanguin' => 'O+',
            'statut' => 'actif',
            'user_id' => null,
        ]);

        $this->matiere = MatiereUnite::create([
            'libelle' => 'Mathématiques',
            'code' => 'MATH',
            'statut' => 'actif',
        ]);

        $this->classe = Classe::create([
            'nom' => 'Classe 1A',
            'code' => 'CLS1A',
            'statut' => 'actif',
            'capacite_max' => 30,
        ]);

        // Authenticate the user
        $this->actingAs($this->user);
    }

    /**
     * Test: Index page loads with paginated absences
     */
    public function test_index_page_loads_successfully()
    {
        // Create some absence records
        AbsenceApprenant::factory(5)->create([
            'apprenant_id' => $this->apprenant->id,
        ]);

        $response = $this->get(route('academique.absences_apprenants.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Academique::AbsencesApprenants/Index')
        );
    }

    /**
     * Test: Create page loads with reference data
     */
    public function test_create_page_loads_with_reference_data()
    {
        $response = $this->get(route('academique.absences_apprenants.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Academique::AbsencesApprenants/Create')
                ->has('apprenants')
                ->has('matieres')
                ->has('classes')
                ->has('title')
        );
    }

    /**
     * Test: Store a new absence with valid data
     */
    public function test_store_absence_with_valid_data()
    {
        $data = [
            'apprenant_id' => $this->apprenant->id,
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
            'date_debut' => '2026-03-12T09:00',
            'date_fin' => '2026-03-12T10:30',
            'nombre_heures' => 1.5,
            'motif' => 'Maladie',
            'statut' => 'non_justifiee',
            'etat' => 'actif',
        ];

        $response = $this->post(route('academique.absences_apprenants.store'), $data);

        $response->assertRedirect(route('academique.absences_apprenants.index'));
        $this->assertDatabaseHas('absence_apprenants', [
            'apprenant_id' => $this->apprenant->id,
            'motif' => 'Maladie',
            'statut' => 'non_justifiee',
        ]);
    }

    /**
     * Test: Store validates required fields
     */
    public function test_store_validates_required_fields()
    {
        $data = [
            // Missing required fields: apprenant_id, date_debut, date_fin, statut
            'nombre_heures' => 1.5,
        ];

        $response = $this->post(route('academique.absences_apprenants.store'), $data);

        $response->assertSessionHasErrors(['apprenant_id', 'date_debut', 'date_fin', 'statut']);
    }

    /**
     * Test: Store validates date_fin must be after_or_equal to date_debut
     */
    public function test_store_validates_date_fin_after_date_debut()
    {
        $data = [
            'apprenant_id' => $this->apprenant->id,
            'date_debut' => '2026-03-12T10:30',
            'date_fin' => '2026-03-12T09:00',  // Before date_debut
            'statut' => 'non_justifiee',
        ];

        $response = $this->post(route('academique.absences_apprenants.store'), $data);

        $response->assertSessionHasErrors('date_fin');
    }

    /**
     * Test: Store validates statut enum
     */
    public function test_store_validates_statut_enum()
    {
        $data = [
            'apprenant_id' => $this->apprenant->id,
            'date_debut' => '2026-03-12T09:00',
            'date_fin' => '2026-03-12T10:30',
            'statut' => 'invalid_status',  // Invalid enum value
        ];

        $response = $this->post(route('academique.absences_apprenants.store'), $data);

        $response->assertSessionHasErrors('statut');
    }

    /**
     * Test: Store validates file upload (max 5MB, valid MIME types)
     */
    public function test_store_validates_file_upload()
    {
        $data = [
            'apprenant_id' => $this->apprenant->id,
            'date_debut' => '2026-03-12T09:00',
            'date_fin' => '2026-03-12T10:30',
            'statut' => 'justifiee',
            'justificatif_path' => $this->faker->file(storage_path('app'), '', false),  // Invalid file
        ];

        $response = $this->post(route('academique.absences_apprenants.store'), $data);

        // File validation error expected
        $response->assertSessionHasErrors('justificatif_path');
    }

    /**
     * Test: Show page displays absence with formatted dates
     */
    public function test_show_page_displays_absence()
    {
        $absence = AbsenceApprenant::factory()->create([
            'apprenant_id' => $this->apprenant->id,
            'date_debut' => '2026-03-12 09:00:00',
            'date_fin' => '2026-03-12 10:30:00',
        ]);

        $response = $this->get(route('academique.absences_apprenants.show', $absence));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Academique::AbsencesApprenants/Show')
                ->has('absence')
                ->has('apprenants')
                ->has('matieres')
                ->has('classes')
        );
    }

    /**
     * Test: Edit page loads with pre-filled data
     */
    public function test_edit_page_loads_with_data()
    {
        $absence = AbsenceApprenant::factory()->create([
            'apprenant_id' => $this->apprenant->id,
        ]);

        $response = $this->get(route('academique.absences_apprenants.edit', $absence));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Academique::AbsencesApprenants/Edit')
                ->has('absence')
                ->has('apprenants')
                ->has('matieres')
                ->has('classes')
        );
    }

    /**
     * Test: Update absence with valid data
     */
    public function test_update_absence_with_valid_data()
    {
        $absence = AbsenceApprenant::factory()->create([
            'apprenant_id' => $this->apprenant->id,
            'motif' => 'Old motif',
            'statut' => 'non_justifiee',
        ]);

        $data = [
            'apprenant_id' => $this->apprenant->id,
            'date_debut' => '2026-03-13T09:00',
            'date_fin' => '2026-03-13T10:30',
            'motif' => 'Updated motif',
            'statut' => 'justifiee',
        ];

        $response = $this->put(route('academique.absences_apprenants.update', $absence), $data);

        $response->assertRedirect(route('academique.absences_apprenants.show', $absence));
        $this->assertDatabaseHas('absence_apprenants', [
            'id' => $absence->id,
            'motif' => 'Updated motif',
            'statut' => 'justifiee',
        ]);
    }

    /**
     * Test: Statut toggle (activate method)
     */
    public function test_toggle_statut_actif_to_inactif()
    {
        $absence = AbsenceApprenant::factory()->create([
            'etat' => 'actif',
        ]);

        $response = $this->put(route('academique.absences_apprenants.statut', $absence));

        $response->assertRedirect(route('academique.absences_apprenants.index'));
        $this->assertDatabaseHas('absence_apprenants', [
            'id' => $absence->id,
            'etat' => 'inactif',
        ]);
    }

    /**
     * Test: Statut toggle inactif to actif
     */
    public function test_toggle_statut_inactif_to_actif()
    {
        $absence = AbsenceApprenant::factory()->create([
            'etat' => 'inactif',
        ]);

        $response = $this->put(route('academique.absences_apprenants.statut', $absence));

        $this->assertDatabaseHas('absence_apprenants', [
            'id' => $absence->id,
            'etat' => 'actif',
        ]);
    }

    /**
     * Test: Delete absence (soft delete)
     */
    public function test_destroy_absence()
    {
        $absence = AbsenceApprenant::factory()->create();

        $response = $this->delete(route('academique.absences_apprenants.destroy', $absence));

        $response->assertRedirect();
        $this->assertSoftDeleted('absence_apprenants', ['id' => $absence->id]);
    }

    /**
     * Test: Index search by apprenant name
     */
    public function test_index_search_by_apprenant_name()
    {
        $absence = AbsenceApprenant::factory()->create([
            'apprenant_id' => $this->apprenant->id,
        ]);

        $response = $this->get(route('academique.absences_apprenants.index', [
            'search' => $this->apprenant->user->nom,
        ]));

        $response->assertStatus(200);
    }

    /**
     * Test: Index filter by activate status
     */
    public function test_index_filter_by_activate()
    {
        AbsenceApprenant::factory(2)->create(['activate' => true]);
        AbsenceApprenant::factory(3)->create(['activate' => false]);

        $response = $this->get(route('academique.absences_apprenants.index', [
            'activate' => true,
        ]));

        $response->assertStatus(200);
    }

    /**
     * Test: Verify relationships load correctly
     */
    public function test_absence_relationships_load()
    {
        $absence = AbsenceApprenant::factory()->create([
            'apprenant_id' => $this->apprenant->id,
            'matiere_id' => $this->matiere->id,
            'classe_id' => $this->classe->id,
        ]);

        $absence->load('apprenant', 'matiere', 'classe');

        $this->assertNotNull($absence->apprenant);
        $this->assertNotNull($absence->matiere);
        $this->assertNotNull($absence->classe);
    }

    /**
     * Test: Store with file upload (mock file)
     */
    public function test_store_with_file_upload()
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('justificatif.pdf', 100, 'application/pdf');

        $data = [
            'apprenant_id' => $this->apprenant->id,
            'date_debut' => '2026-03-12T09:00',
            'date_fin' => '2026-03-12T10:30',
            'statut' => 'justifiee',
            'justificatif_path' => $file,
        ];

        $response = $this->post(route('academique.absences_apprenants.store'), $data);

        $response->assertRedirect(route('academique.absences_apprenants.index'));
        $this->assertDatabaseHas('absence_apprenants', [
            'apprenant_id' => $this->apprenant->id,
            'statut' => 'justifiee',
        ]);

        // Verify file was stored
        \Storage::disk('public')->assertExists('absences_apprenants/' . $file->hashName());
    }

    /**
     * Test: Update absence and replace file
     */
    public function test_update_absence_with_file_replacement()
    {
        $absence = AbsenceApprenant::factory()->create([
            'apprenant_id' => $this->apprenant->id,
            'justificatif_path' => 'absences_apprenants/old_file.pdf',
        ]);

        $newFile = \Illuminate\Http\UploadedFile::fake()->create('new_justificatif.pdf', 100, 'application/pdf');

        $data = [
            'apprenant_id' => $this->apprenant->id,
            'date_debut' => '2026-03-13T09:00',
            'date_fin' => '2026-03-13T10:30',
            'statut' => 'justifiee',
            'justificatif_path' => $newFile,
        ];

        $response = $this->put(route('academique.absences_apprenants.update', $absence), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('absence_apprenants', [
            'id' => $absence->id,
        ]);
    }

    /**
     * Test: Download file endpoint
     */
    public function test_download_file()
    {
        $filePath = 'absences_apprenants/test_file.pdf';
        \Storage::disk('public')->put($filePath, 'test content');

        $response = $this->get(route('academique.download_absence_file', ['filePath' => $filePath]));

        // File should download successfully
        $response->assertStatus(200);
    }

    /**
     * Test: Download file that doesn't exist returns 404
     */
    public function test_download_nonexistent_file_returns_404()
    {
        $response = $this->get(route('academique.download_absence_file', ['filePath' => 'nonexistent.pdf']));

        $response->assertStatus(404);
    }

    /**
     * Test: Verify absence data types and casting
     */
    public function test_absence_data_casting()
    {
        $absence = AbsenceApprenant::factory()->create([
            'nombre_heures' => 1.5,
            'date_debut' => '2026-03-12 09:00:00',
            'date_fin' => '2026-03-12 10:30:00',
        ]);

        // Reload from database
        $absence = $absence->fresh();

        // Verify decimal casting
        $this->assertIsFloat($absence->nombre_heures);
        $this->assertEquals(1.5, $absence->nombre_heures);

        // Verify datetime casting
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $absence->date_debut);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $absence->date_fin);
    }

    /**
     * Test: Verify all validation rules work correctly
     */
    public function test_complete_validation_flow()
    {
        $testCases = [
            [
                'data' => ['statut' => 'invalid'],
                'errors' => ['statut'],
                'description' => 'Invalid statut'
            ],
            [
                'data' => ['etat' => 'invalid'],
                'errors' => ['etat'],
                'description' => 'Invalid etat'
            ],
            [
                'data' => ['nombre_heures' => -1],
                'errors' => ['nombre_heures'],
                'description' => 'Negative nombre_heures'
            ],
        ];

        foreach ($testCases as $testCase) {
            $data = array_merge([
                'apprenant_id' => $this->apprenant->id,
                'date_debut' => '2026-03-12T09:00',
                'date_fin' => '2026-03-12T10:30',
                'statut' => 'non_justifiee',
            ], $testCase['data']);

            $response = $this->post(route('academique.absences_apprenants.store'), $data);

            foreach ($testCase['errors'] as $error) {
                $response->assertSessionHasErrors($error, null, $testCase['description']);
            }
        }
    }
}
