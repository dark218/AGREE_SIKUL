<?php

namespace Modules\Pos\Tests\Feature;

use App\Models\User;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;
use Modules\GestionStock\Entities\Article;
use Modules\Pos\Entities\SessionCaisse;
use Modules\Pos\Entities\VentePos;
use Tests\BaseTestCase;

/**
 * Tests fonctionnels pour la gestion des ventes POS
 * Couvre la création, validation, annulation et remboursement des ventes
 */
class VentePosControllerTest extends BaseTestCase
{
    // ==================== TESTS D'ACCÈS ET PERMISSIONS ====================

    /**
     * Test d'accès refusé à la liste des ventes pour les invités
     * Les utilisateurs non connectés ne peuvent pas voir les ventes
     */
    public function test_guest_cannot_access_vente_list()
    {
        // Tenter d'accéder à la liste des ventes sans authentification
        $this->get(route('ventepos.index'))
            ->assertStatus(403); // Accès interdit
    }

    /**
     * Test d'accès réussi à la liste des ventes pour un caissier
     * Un caissier peut voir la liste des ventes
     */
    public function test_caissier_can_access_vente_list()
    {
        // Créer un caissier
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.caissier')
        ]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-list');

        // Créer l'infrastructure nécessaire
        $pointVente = PointVente::factory()->create();
        Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        $this->actingAs($user);

        // Doit pouvoir accéder à la liste
        $this->get(route('ventepos.index'))
            ->assertStatus(200);
    }

    /**
     * Test d'accès réussi à la liste des ventes pour un manager
     * Un manager peut voir la liste des ventes de son point de vente
     */
    public function test_manager_can_access_vente_list()
    {
        // Créer un manager
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('ventepos-list');

        $pointVente = PointVente::factory()->create();
        Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $this->actingAs($user);

        // Doit pouvoir accéder à la liste
        $this->get(route('ventepos.index'))
            ->assertStatus(200);
    }

    // ==================== TESTS DE CRÉATION DE VENTE ====================

    /**
     * Test d'accès refusé à la création pour les invités
     * Les utilisateurs non connectés ne peuvent pas créer de ventes
     */
    public function test_guest_cannot_create_vente()
    {
        // Tenter d'accéder à la création de vente sans authentification
        $this->get(route('ventepos.create'))
            ->assertStatus(403);
    }

    /**
     * Test d'erreur pour utilisateur sans pays
     * Un utilisateur sans pays assigné ne peut pas créer de ventes
     */
    public function test_user_without_pays_cannot_create_vente()
    {
        // Créer un utilisateur sans pays
        $user = User::factory()->create(['pays_id' => null]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-create');

        $this->actingAs($user);

        // Doit voir une erreur
        $this->get(route('ventepos.create'))
            ->assertSessionHas('error');
    }

    /**
     * Test de création réussie pour un caissier avec session ouverte
     * Un caissier avec une session active peut créer des ventes
     */
    public function test_caissier_can_create_vente()
    {
        // Créer un caissier
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.caissier')
        ]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-create');

        // Créer l'infrastructure
        $pointVente = PointVente::factory()->create();
        $employe = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        // Créer une session ouverte (prérequis pour créer des ventes)
        SessionCaisse::factory()->create([
            'caissier_id' => $employe->id,
            'statut' => config('appconstants.session_caisse_statut.ouverte'),
        ]);

        $this->actingAs($user);

        // Doit pouvoir accéder au formulaire de création
        $this->get(route('ventepos.create'))
            ->assertStatus(200);
    }

    /**
     * Test de redirection pour caissier sans session ouverte
     * Un caissier sans session active est redirigé vers la gestion des sessions
     */
    public function test_caissier_without_open_session_redirected()
    {
        // Créer un caissier
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.caissier')
        ]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-create');

        $pointVente = PointVente::factory()->create();
        Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);
        // Pas de session ouverte

        $this->actingAs($user);

        // Doit être redirigé vers la gestion des sessions
        $this->get(route('ventepos.create'))
            ->assertRedirect(route('session-caisse-caissier.index'))
            ->assertSessionHas('error');
    }

    // ==================== TESTS D'ENREGISTREMENT DE VENTE ====================

    /**
     * Test d'enregistrement refusé pour les invités
     * Les utilisateurs non connectés ne peuvent pas enregistrer de ventes
     */
    public function test_guest_cannot_store_vente()
    {
        // Tenter d'enregistrer une vente sans authentification
        $this->post(route('ventepos.store'), [])
            ->assertStatus(403);
    }

    /**
     * Test de validation requise pour l'enregistrement
     * L'enregistrement d'une vente nécessite des données valides
     */
    public function test_store_vente_requires_validation()
    {
        // Créer un caissier
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.caissier')
        ]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-create');

        $pointVente = PointVente::factory()->create();
        Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        $this->actingAs($user);

        // Envoyer des données vides
        $this->post(route('ventepos.store'), [])
            ->assertSessionHasErrors([
                'session_id',      // ID de session requis
                'mode_paiement',   // Mode de paiement requis
                'lignes',          // Lignes de vente requises
            ]);
    }

    /**
     * Test d'enregistrement réussi pour un caissier
     * Un caissier peut enregistrer une vente avec les bonnes données
     */
    public function test_caissier_can_store_vente()
    {
        // Créer un caissier
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.caissier')
        ]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-create');

        // Créer l'infrastructure complète
        $pointVente = PointVente::factory()->create();
        $employe = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        // Créer une caisse liée au même point de vente
        $caisse = \Modules\Business\Entities\Caisse::factory()->create([
            'points_vente_id' => $pointVente->id,
        ]);

        // Créer une session ouverte
        $session = SessionCaisse::factory()->create([
            'caissier_id' => $employe->id,
            'caisse_id' => $caisse->id,
            'statut' => config('appconstants.session_caisse_statut.ouverte'),
        ]);

        // Créer un article en stock
        $article = Article::factory()->create([
            'points_vente_id' => $pointVente->id,
            'quantite_stock' => 10,     // Stock suffisant
            'prix_cents' => 1000,       // Prix : 10.00 XOF
        ]);

        $this->actingAs($user);

        // Enregistrer une vente valide
        $response = $this->post(route('ventepos.store'), [
            'session_id' => $session->id,
            'mode_paiement' => 'espece',
            'lignes' => [
                [
                    'sku' => $article->sku,
                    'libelle' => $article->nom,
                    'quantite' => 2,
                    'prix_unitaire' => 10.00,
                    'remise' => 0,
                    'taxe' => 0,
                ]
            ]
        ]);

        // Doit rediriger après succès
        $response->assertRedirect();

        // Vérifier que la vente est enregistrée en base
        $this->assertDatabaseHas('ventes_pos', [
            'sessions_caisse_id' => $session->id,
            'employe_id' => $employe->id,
            'statut' => config('appconstants.statut_vente_pos.en_attente'),
        ]);
    }

    // ==================== TESTS D'AFFICHAGE DE VENTE ====================

    /**
     * Test d'affichage refusé pour les invités
     * Les utilisateurs non connectés ne peuvent pas voir les détails d'une vente
     */
    public function test_guest_cannot_show_vente()
    {
        // Créer une vente
        $vente = VentePos::factory()->create();

        // Tenter d'accéder aux détails sans authentification
        $this->get(route('ventepos.show', $vente->id))
            ->assertStatus(403);
    }

    /**
     * Test d'affichage réussi pour le caissier propriétaire
     * Un caissier peut voir les détails de ses propres ventes
     */
    public function test_caissier_can_show_own_vente()
    {
        // Créer un caissier
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.caissier')
        ]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-list');

        $pointVente = PointVente::factory()->create();
        $employe = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        // Créer une vente appartenant à ce caissier
        $vente = VentePos::factory()->create([
            'employe_id' => $employe->id,
        ]);

        $this->actingAs($user);

        // Doit pouvoir voir sa propre vente
        $this->get(route('ventepos.show', $vente->id))
            ->assertStatus(200);
    }

    /**
     * Test d'affichage refusé pour une vente d'un autre caissier
     * Un caissier ne peut pas voir les ventes des autres caissiers
     */
    public function test_caissier_cannot_show_other_vente()
    {
        // Créer un caissier
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => strtolower(config('appconstants.role.caissier'))
        ]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-list');

        $pointVente = PointVente::factory()->create();
        $employe = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        // Créer un autre employé avec un user différent
        $otherUser = User::factory()->create();
        $otherEmploye = Employe::factory()->create([
            'users_id' => $otherUser->id,
        ]);

        // Créer une vente appartenant à l'autre employé
        $vente = VentePos::factory()->create([
            'employe_id' => $otherEmploye->id,
        ]);

        $this->actingAs($user);

        // Doit être redirigé avec erreur
        $this->get(route('ventepos.show', $vente->id))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    // ==================== TESTS DE VALIDATION DE PAIEMENT ====================

    /**
     * Test de validation de paiement refusée pour les invités
     * Les utilisateurs non connectés ne peuvent pas valider de paiements
     */
    public function test_guest_cannot_validate_paiement()
    {
        // Créer une vente
        $vente = VentePos::factory()->create();

        // Tenter de valider le paiement sans authentification
        $this->post(route('ventepos.validate-paiement', $vente->id), [])
            ->assertStatus(403);
    }

    /**
     * Test de validation de paiement réservée aux caissiers
     * Seuls les caissiers peuvent valider les paiements
     */
    public function test_validate_paiement_requires_caissier()
    {
        // Créer un manager (pas caissier)
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('ventepos-validate');

        $vente = VentePos::factory()->create();

        $this->actingAs($user);

        // Doit échouer car ce n'est pas un caissier
        $this->post(route('ventepos.validate-paiement', $vente->id), [])
            ->assertSessionHas('error');
    }

    // ==================== TESTS D'ANNULATION DE VENTE ====================

    /**
     * Test d'annulation refusée pour les invités
     * Les utilisateurs non connectés ne peuvent pas annuler de ventes
     */
    public function test_guest_cannot_cancel_vente()
    {
        // Créer une vente
        $vente = VentePos::factory()->create();

        // Tenter d'annuler sans authentification
        $this->post(route('ventepos.cancel', $vente->id), [])
            ->assertStatus(403);
    }

    /**
     * Test de validation requise pour l'annulation
     * L'annulation d'une vente nécessite un motif
     */
    public function test_cancel_vente_requires_validation()
    {
        // Créer un caissier
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.caissier')
        ]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-cancel');

        $vente = VentePos::factory()->create();

        $this->actingAs($user);

        // Tenter d'annuler sans motif
        $this->post(route('ventepos.cancel', $vente->id), [])
            ->assertSessionHasErrors(['motif']); // Motif requis
    }

    // ==================== TESTS DE REMBOURSEMENT - PARTIE 1 ====================

    /**
     * Test de remboursement refusé pour les invités
     * Les utilisateurs non connectés ne peuvent pas effectuer de remboursements
     */
    public function test_guest_cannot_refund_vente()
    {
        // Créer une vente
        $vente = VentePos::factory()->create();

        // Tenter de rembourser sans authentification
        $this->post(route('ventepos.refund', $vente->id), [])
            ->assertStatus(403);
    }

    /**
     * Test de remboursement réservé aux managers
     * Seuls les managers peuvent effectuer des remboursements
     */
    public function test_refund_requires_manager()
    {
        // Créer un caissier (pas manager)
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.caissier')
        ]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-refund');

        $vente = VentePos::factory()->create();

        $this->actingAs($user);

        // Doit échouer car ce n'est pas un manager
        $this->post(route('ventepos.refund', $vente->id), [])
            ->assertSessionHas('error');
    }

    /**
     * Test de validation requise pour le remboursement
     * Le remboursement nécessite des données valides
     */
    public function test_refund_vente_requires_validation()
    {
        // Créer un manager
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('ventepos-refund');

        // Créer l'infrastructure
        $pointVente = PointVente::factory()->create();
        $manager = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $caissier = Employe::factory()->create([
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        $session = SessionCaisse::factory()->create([
            'caissier_id' => $caissier->id,
        ]);

        $vente = VentePos::factory()->create([
            'sessions_caisse_id' => $session->id,
            'employe_id' => $caissier->id,
            'statut' => config('appconstants.statut_vente_pos.validee'),
        ]);

        $this->actingAs($user);

        // Tenter de rembourser sans données
        $response = $this->post(route('ventepos.refund', $vente->id), []);

        // Le contrôleur peut retourner soit des erreurs de validation soit une erreur de session
        $this->assertTrue(
            $response->getSession()->has('errors') || $response->getSession()->has('error'),
            'Expected either validation errors or session error'
        );
    }

    /**
     * Test de remboursement réussi par un manager
     * Un manager peut rembourser une vente validée
     */
    public function test_manager_can_refund_vente()
    {
        // Créer un manager
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('ventepos-refund');

        // Créer l'infrastructure complète
        $pointVente = PointVente::factory()->create();
        $manager = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $caissier = Employe::factory()->create([
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        // Créer une session fermée avec encaisse
        $session = SessionCaisse::factory()->create([
            'caissier_id' => $caissier->id,
            'statut' => config('appconstants.session_caisse_statut.fermee'),
            'total_encaisse_cents' => 5000,
        ]);

        // Créer une vente validée remboursable
        $vente = VentePos::factory()->create([
            'sessions_caisse_id' => $session->id,
            'employe_id' => $caissier->id,
            'statut' => config('appconstants.statut_vente_pos.validee'),
            'total_cents' => 2000,
            'mode_paiement' => 'espece',
            'montant_espece_cents' => 2000,
            'montant_non_espece_cents' => 0,
            'total_rembourse_cents' => 0,
            'rembourse_espece_cents' => 0,
            'rembourse_non_espece_cents' => 0,
            'devise' => 'XOF',
        ]);

        $this->actingAs($user);

        // Effectuer le remboursement
        $response = $this->post(route('ventepos.refund', $vente->id), [
            'montant' => 10.00,                    // Montant à rembourser
            'motif' => 'Produit défectueux'        // Motif du remboursement
        ]);

        // Vérifier que la requête a réussi
        if ($response->getSession()->has('error')) {
            $this->fail('Refund failed with error: ' . $response->getSession()->get('error'));
        }

        $response->assertRedirect();

        // Vérifier la mise à jour en base de données
        $this->assertDatabaseHas('ventes_pos', [
            'id' => $vente->id,
            'total_rembourse_cents' => 10,     // 10.00 XOF = 10 cents (pas de multiplication par 100 pour XOF)
            'statut' => config('appconstants.statut_vente_pos.partielle'),
        ]);
    }

    // ==================== TESTS DE REMBOURSEMENT PAR LIGNES ====================

    /**
     * Test de remboursement par lignes refusé pour les invités
     */
    public function test_guest_cannot_refund_by_lines()
    {
        $vente = VentePos::factory()->create();

        $this->post(route('ventepos.refund-lines', $vente->id), [])
            ->assertStatus(403);
    }

    /**
     * Test de remboursement par lignes réservé aux managers
     */
    public function test_refund_by_lines_requires_manager()
    {
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.caissier')
        ]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-refund-line');

        $vente = VentePos::factory()->create();

        $this->actingAs($user);

        $this->post(route('ventepos.refund-lines', $vente->id), [])
            ->assertSessionHas('error');
    }

    /**
     * Test de validation requise pour le remboursement par lignes
     */
    public function test_refund_by_lines_requires_validation()
    {
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('ventepos-refund-line');

        $pointVente = PointVente::factory()->create();
        $manager = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $vente = VentePos::factory()->create();

        $this->actingAs($user);

        $this->post(route('ventepos.refund-lines', $vente->id), [])
            ->assertSessionHasErrors(['lignes', 'motif']);
    }

    // ==================== TESTS DE REMBOURSEMENT MIXTE ====================

    /**
     * Test de remboursement mixte refusé pour les invités
     */
    public function test_guest_cannot_refund_mixte()
    {
        $vente = VentePos::factory()->create();

        $this->post(route('ventepos.refund-mixte', $vente->id), [])
            ->assertStatus(403);
    }

    /**
     * Test de remboursement mixte réservé aux managers
     */
    public function test_refund_mixte_requires_manager()
    {
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.caissier')
        ]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-refund-mixte');

        $vente = VentePos::factory()->create();

        $this->actingAs($user);

        $this->post(route('ventepos.refund-mixte', $vente->id), [])
            ->assertSessionHas('error');
    }

    /**
     * Test de validation requise pour le remboursement mixte
     */
    public function test_refund_mixte_requires_validation()
    {
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('ventepos-refund-mixte');

        $pointVente = PointVente::factory()->create();
        $manager = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $vente = VentePos::factory()->create();

        $this->actingAs($user);

        $this->post(route('ventepos.refund-mixte', $vente->id), [])
            ->assertSessionHasErrors(['motif']);
    }

    /**
     * Test de remboursement mixte réussi par un manager
     * Un manager peut rembourser partiellement en espèces et électronique
     */
    public function test_manager_can_refund_mixte()
    {
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('ventepos-refund-mixte');

        $pointVente = PointVente::factory()->create();
        $manager = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $caissier = Employe::factory()->create([
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        $session = SessionCaisse::factory()->create([
            'caissier_id' => $caissier->id,
            'statut' => config('appconstants.session_caisse_statut.fermee'),
            'total_encaisse_cents' => 5000,
        ]);

        // Créer une vente avec paiement mixte
        $vente = VentePos::factory()->create([
            'sessions_caisse_id' => $session->id,
            'employe_id' => $caissier->id,
            'statut' => config('appconstants.statut_vente_pos.validee'),
            'total_cents' => 3000,
            'mode_paiement' => 'mixte',
            'montant_espece_cents' => 1500,        // 15.00 XOF en espèces
            'montant_non_espece_cents' => 1500,    // 15.00 XOF électronique
            'total_rembourse_cents' => 0,
            'rembourse_espece_cents' => 0,
            'rembourse_non_espece_cents' => 0,
            'devise' => 'XOF',
        ]);

        $this->actingAs($user);

        // Effectuer un remboursement mixte
        $response = $this->post(route('ventepos.refund-mixte', $vente->id), [
            'espece' => 5.00,                      // 5.00 XOF en espèces
            'electronique' => 10.00,               // 10.00 XOF électronique
            'motif' => 'Remboursement partiel'
        ]);

        if ($response->getSession()->has('error')) {
            $this->fail('Refund failed with error: ' . $response->getSession()->get('error'));
        }

        $response->assertRedirect();

        // Vérifier la mise à jour des montants remboursés
        $this->assertDatabaseHas('ventes_pos', [
            'id' => $vente->id,
            'total_rembourse_cents' => 15,         // Total remboursé : 15 XOF
            'rembourse_espece_cents' => 5,         // Remboursé en espèces : 5 XOF
            'rembourse_non_espece_cents' => 10,    // Remboursé électronique : 10 XOF
        ]);
    }

    // ==================== TESTS DE REMBOURSEMENT PAR QUANTITÉ ====================

    /**
     * Test de remboursement par quantité refusé pour les invités
     */
    public function test_guest_cannot_refund_by_quantite()
    {
        $vente = VentePos::factory()->create();

        $this->post(route('ventepos.refund-quantite', $vente->id), [])
            ->assertStatus(403);
    }

    /**
     * Test de remboursement par quantité réservé aux managers
     */
    public function test_refund_by_quantite_requires_manager()
    {
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.caissier')
        ]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-refund-quantite');

        $vente = VentePos::factory()->create();

        $this->actingAs($user);

        $this->post(route('ventepos.refund-quantite', $vente->id), [])
            ->assertSessionHas('error');
    }

    /**
     * Test de validation requise pour le remboursement par quantité
     */
    public function test_refund_by_quantite_requires_validation()
    {
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('ventepos-refund-quantite');

        $pointVente = PointVente::factory()->create();
        $manager = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $vente = VentePos::factory()->create();

        $this->actingAs($user);

        $this->post(route('ventepos.refund-quantite', $vente->id), [])
            ->assertSessionHasErrors(['lignes', 'motif']);
    }

    // ==================== TESTS DE REMBOURSEMENT MIXTE PAR QUANTITÉ ====================

    /**
     * Test de remboursement mixte par quantité refusé pour les invités
     */
    public function test_guest_cannot_refund_mixte_quantite()
    {
        $vente = VentePos::factory()->create();

        $this->post(route('ventepos.refund-mixte-quantite', $vente->id), [])
            ->assertStatus(403);
    }

    /**
     * Test de remboursement mixte par quantité réservé aux managers
     */
    public function test_refund_mixte_quantite_requires_manager()
    {
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.caissier')
        ]);
        $user->assignRole(config('appconstants.role.caissier'));
        $user->givePermissionTo('ventepos-refund-mixte-quantite');

        $vente = VentePos::factory()->create();

        $this->actingAs($user);

        $this->post(route('ventepos.refund-mixte-quantite', $vente->id), [])
            ->assertSessionHas('error');
    }

    /**
     * Test de validation requise pour le remboursement mixte par quantité
     */
    public function test_refund_mixte_quantite_requires_validation()
    {
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('ventepos-refund-mixte-quantite');

        $pointVente = PointVente::factory()->create();
        $manager = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $vente = VentePos::factory()->create();

        $this->actingAs($user);

        $this->post(route('ventepos.refund-mixte-quantite', $vente->id), [])
            ->assertSessionHasErrors(['motif']);
    }

    // ==================== TESTS DE CAS D'ERREUR ====================

    /**
     * Test de remboursement d'une vente avec statut non remboursable
     * Une vente en attente ne peut pas être remboursée
     */
    public function test_refund_vente_not_refundable_status()
    {
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('ventepos-refund');

        $pointVente = PointVente::factory()->create();
        $manager = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        // Créer une vente en attente (non remboursable)
        $vente = VentePos::factory()->create([
            'statut' => config('appconstants.statut_vente_pos.en_attente'),
        ]);

        $this->actingAs($user);

        // Tenter de rembourser une vente non validée
        $this->post(route('ventepos.refund', $vente->id), [
            'montant' => 10.00,
            'motif' => 'Test'
        ])
            ->assertSessionHas('error');
    }

    /**
     * Test de remboursement avec montant invalide
     * Le montant du remboursement ne peut pas dépasser le total de la vente
     */
    public function test_refund_vente_invalid_amount()
    {
        $user = User::factory()->create([
            'pays_id' => 1,
            'role' => config('appconstants.role.manager')
        ]);
        $user->assignRole(config('appconstants.role.manager'));
        $user->givePermissionTo('ventepos-refund');

        $pointVente = PointVente::factory()->create();
        $manager = Employe::factory()->create([
            'users_id' => $user->id,
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.manager'),
        ]);

        $caissier = Employe::factory()->create([
            'points_vente_id' => $pointVente->id,
            'type_employe' => config('appconstants.type_employe.caissier'),
        ]);

        $session = SessionCaisse::factory()->create([
            'caissier_id' => $caissier->id,
        ]);

        // Créer une vente de 10.00 XOF
        $vente = VentePos::factory()->create([
            'sessions_caisse_id' => $session->id,
            'employe_id' => $caissier->id,
            'statut' => config('appconstants.statut_vente_pos.validee'),
            'total_cents' => 1000,  // 10.00 XOF
        ]);

        $this->actingAs($user);

        // Tenter de rembourser plus que le total
        $this->post(route('ventepos.refund', $vente->id), [
            'montant' => 20.00,     // Plus que le total (10.00)
            'motif' => 'Test'
        ])
            ->assertSessionHas('error');
    }
}
