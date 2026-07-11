<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\RessourcesLogistique\Entities\Bibliotheque;
use Modules\RessourcesLogistique\Entities\EntreeLivre;
use Modules\RessourcesLogistique\Entities\Ouvrage;
use Modules\RessourcesLogistique\Entities\SortieLivre;
use Tests\TestCase;

/**
 * Tests fonctionnels — Bibliothèque (Phase 1) :
 *  - Liste (bibliotheque_structures) : création via la route.
 *  - Entrée / Sortie de livres : création + validation du type.
 *  - Inventaire : calcul Stock = Σ entrées − Σ sorties.
 */
class BibliothequeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function makeOuvrage(): Ouvrage
    {
        $biblio = Bibliotheque::create(['nom' => 'Catalogue Test', 'etat' => 'actif']);
        return Ouvrage::create([
            'bibliotheque_id'    => $biblio->id,
            'titre'              => 'Le Petit Prince',
            'auteur'             => 'Saint-Exupéry',
            'editeur'            => 'Gallimard',
            'langue'             => 'Français',
            'categorie'          => 'Roman',
            'annee_publication'  => 1943,
            'nombre_exemplaires' => 1,
        ]);
    }

    /** @test */
    public function une_bibliotheque_peut_etre_creee_via_la_route(): void
    {
        $this->withoutMiddleware();

        $this->post(route('bibliotheque-structures.store'), [
            'code'                 => 'BIB-01',
            'libelle'              => 'Bibliothèque Centrale',
            'localisation'         => 'Bâtiment A',
            'responsable'          => 'M. Diallo',
            'statut_disponibilite' => 'disponible',
            'etat'                 => 'actif',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bibliotheque_structures', [
            'code'    => 'BIB-01',
            'libelle' => 'Bibliothèque Centrale',
        ]);
    }

    /** @test */
    public function une_entree_de_livre_reference_un_ouvrage(): void
    {
        $this->withoutMiddleware();
        $ouvrage = $this->makeOuvrage();

        $this->post(route('entrees-livres.store'), [
            'ouvrage_id'  => $ouvrage->id,
            'type_entree' => 'achat',
            'quantite'    => 5,
            'tiers'       => 'Librairie X',
            'etat'        => 'actif',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('entrees_livres', [
            'ouvrage_id'  => $ouvrage->id,
            'type_entree' => 'achat',
            'quantite'    => 5,
        ]);
    }

    /** @test */
    public function un_type_d_entree_invalide_est_rejete(): void
    {
        $this->withoutMiddleware();
        $ouvrage = $this->makeOuvrage();

        $this->post(route('entrees-livres.store'), [
            'ouvrage_id'  => $ouvrage->id,
            'type_entree' => 'vol', // hors emprunt/achat/don
            'quantite'    => 1,
        ])->assertSessionHasErrors('type_entree');
    }

    /** @test */
    public function une_sortie_de_livre_est_enregistree(): void
    {
        $this->withoutMiddleware();
        $ouvrage = $this->makeOuvrage();

        $this->post(route('sorties-livres.store'), [
            'ouvrage_id'  => $ouvrage->id,
            'type_sortie' => 'pret',
            'quantite'    => 2,
            'tiers'       => 'Élève Y',
            'etat'        => 'actif',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sorties_livres', [
            'ouvrage_id'  => $ouvrage->id,
            'type_sortie' => 'pret',
            'quantite'    => 2,
        ]);
    }

    /** @test */
    public function l_inventaire_calcule_le_stock_disponible(): void
    {
        // On garde le middleware Inertia (pour assertInertia) mais on saute
        // uniquement la vérification de permission.
        $this->withoutMiddleware(CheckPermission::class);

        $ouvrage = $this->makeOuvrage();
        EntreeLivre::create(['ouvrage_id' => $ouvrage->id, 'type_entree' => 'achat', 'quantite' => 5, 'etat' => 'actif']);
        EntreeLivre::create(['ouvrage_id' => $ouvrage->id, 'type_entree' => 'don',   'quantite' => 3, 'etat' => 'actif']);
        SortieLivre::create(['ouvrage_id' => $ouvrage->id, 'type_sortie' => 'pret',  'quantite' => 2, 'etat' => 'actif']);

        // Quantité initiale = 5 + 3 = 8 ; Sorties = 2 ; Stock = 6
        $this->get(route('inventaire-livres.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('RessourcesLogistique::InventaireLivres/Index', false)
                ->where('inventaire.data.0.quantite_initiale', 8)
                ->where('inventaire.data.0.sorties', 2)
                ->where('inventaire.data.0.stock_disponible', 6)
            );
    }
}
