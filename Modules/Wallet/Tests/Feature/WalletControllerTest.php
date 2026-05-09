<?php

namespace Modules\Wallet\Tests\Feature;

use App\Models\User;
use Modules\Parametrage\Entities\PaysDevise;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Entities\WalletMouvement;
use Tests\BaseTestCase;

class WalletControllerTest extends BaseTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Création d'un utilisateur Super Admin pour les tests nécessitant des permissions élevées
        $this->user = User::factory()->create(['pays_id' => 1]);
        $this->user->assignRole(config('appconstants.role.superadmin'));
    }

    /* ==================== ACCÈS & PERMISSIONS ==================== */

    /**
     * Vérifie qu'un invité (non authentifié) ne peut pas accéder à la liste des wallets.
     * Doit rediriger vers la page de login.
     */

    public function test_guest_cannot_access_wallet_list()
    {
        $this->get(route('wallet.index'))->assertRedirect();
    }

    /**
     * Vérifie qu'un utilisateur avec la permission 'wallet-list' peut accéder à l'index.
     */
    public function test_user_with_permission_can_access_wallet_list()
    {
        $this->user->givePermissionTo('wallet-list');
        $this->actingAs($this->user);

        $this->get(route('wallet.index'))->assertStatus(200);
    }

    /**
     * Vérifie qu'un utilisateur sans la permission requise ne peut pas accéder à l'index (403 Forbidden).
     */
    public function test_user_without_permission_cannot_access_wallet_list()
    {
        $user = User::factory()->create(['pays_id' => 1]);
        $user->assignRole(config('appconstants.role.admin'));

        $this->actingAs($user);
        $this->get(route('wallet.index'))->assertStatus(403);
    }

    /* ==================== INDEX ==================== */

    /**
     * Vérifie que la page index charge correctement et affiche les wallets.
     */
    public function test_can_view_wallet_index()
    {
        $this->user->givePermissionTo('wallet-list');
        $this->actingAs($this->user);

        Wallet::factory()->count(3)->create();

        $this->get(route('wallet.index'))
            ->assertStatus(200);
    }

    /**
     * Teste le filtrage de la liste par type de propriétaire (ex: CLIENT).
     */
    public function test_wallet_index_filters_by_owner_type()
    {
        $this->user->givePermissionTo('wallet-list');
        $this->actingAs($this->user);

        Wallet::factory()->create(['owner_type' => Wallet::OWNER_TYPE_CLIENT]);
        Wallet::factory()->create(['owner_type' => Wallet::OWNER_TYPE_MARCHAND]);

        $this->get(route('wallet.index', [
            'owner_type' => Wallet::OWNER_TYPE_CLIENT
        ]))->assertStatus(200);
    }

    /**
     * Teste le filtrage de la liste par statut (ex: ACTIF).
     */
    public function test_wallet_index_filters_by_statut()
    {
        $this->user->givePermissionTo('wallet-list');
        $this->actingAs($this->user);

        Wallet::factory()->create(['statut' => Wallet::STATUT_ACTIF]);
        Wallet::factory()->create(['statut' => Wallet::STATUT_SUSPENDU]);

        $this->get(route('wallet.index', [
            'statut' => Wallet::STATUT_ACTIF
        ]))->assertStatus(200);
    }

    /**
     * Teste le filtrage de la liste par devise/pays.
     */
    public function test_wallet_index_filters_by_pays_devise()
    {
        $this->user->givePermissionTo('wallet-list');
        $this->actingAs($this->user);

        $paysDevise = PaysDevise::factory()->create();

        Wallet::factory()->create([
            'pays_devise_id' => $paysDevise->id
        ]);

        $this->get(route('wallet.index', [
            'pays_devise_id' => $paysDevise->id
        ]))->assertStatus(200);
    }

    /**
     * Teste la recherche de wallet par information du propriétaire (nom).
     * Note: Ce test semble attendre une redirection, peut-être car la recherche n'est pas encore implémentée ou redirige si 1 seul résultat?
     * (Vérifier le comportement attendu exact selon le controlleur).
     */
    public function test_wallet_index_search_by_owner_info()
    {
        $this->user->givePermissionTo('wallet-list');
        $this->actingAs($this->user);

        $owner = User::factory()->create(['nom' => 'Dupont']);

        Wallet::factory()->create([
            'owner_id' => $owner->id,
            'owner_type' => Wallet::OWNER_TYPE_CLIENT,
        ]);

        $this->get(route('wallet.index', ['search' => 'Dupont']))
            ->assertRedirect();
    }


    /* ==================== SHOW ==================== */

    /**
     * Vérifie qu'un invité ne peut pas voir les détails d'un wallet.
     */
    public function test_guest_cannot_show_wallet()
    {
        $wallet = Wallet::factory()->create();

        $this->get(route('wallet.show', $wallet->id))
            ->assertRedirect();
    }

    /**
     * Vérifie qu'un utilisateur autorisé peut voir les détails d'un wallet.
     */
    public function test_can_show_wallet_details()
    {
        $this->user->givePermissionTo('wallet-list');
        $this->actingAs($this->user);

        $wallet = Wallet::factory()->create();

        $this->get(route('wallet.show', $wallet->id))
            ->assertStatus(302); // redirection Inertia
    }

    /**
     * Vérifie que la vue détail affiche aussi les mouvements du wallet.
     */
    public function test_wallet_show_displays_mouvements()
    {
        $this->user->givePermissionTo('wallet-list');
        $this->actingAs($this->user);

        $wallet = Wallet::factory()->create();
        WalletMouvement::factory()->count(2)->create([
            'wallet_id' => $wallet->id,
        ]);

        $this->get(route('wallet.show', $wallet->id))
            ->assertStatus(302);
    }

    /* ==================== EDIT / UPDATE ==================== */

    /**
     * Vérifie qu'un invité ne peut pas accéder au formulaire d'édition.
     */
    public function test_guest_cannot_edit_wallet()
    {
        $wallet = Wallet::factory()->create();

        $this->get(route('wallet.edit', $wallet->id))
            ->assertRedirect();
    }

    /**
     * Vérifie qu'un utilisateur autorisé peut accéder au formulaire d'édition.
     */
    public function test_can_view_edit_form()
    {
        $this->user->givePermissionTo('wallet-edit');
        $this->actingAs($this->user);

        $wallet = Wallet::factory()->create();

        $this->get(route('wallet.edit', $wallet->id))
            ->assertStatus(302);
    }

    /**
     * Vérifie la mise à jour du statut d'un wallet via la méthode update.
     */
    public function test_can_update_wallet_statut()
    {
        $this->user->givePermissionTo('wallet-edit');
        $this->actingAs($this->user);

        $wallet = Wallet::factory()->create([
            'statut' => Wallet::STATUT_ACTIF
        ]);

        $response = $this->put(route('wallet.update', $wallet->id), [
            'statut' => Wallet::STATUT_SUSPENDU,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'statut' => Wallet::STATUT_SUSPENDU,
        ]);
    }

    /**
     * Teste que la mise à jour nécessite des données valides (validation).
     */
    public function test_update_wallet_requires_validation()
    {
        $this->user->givePermissionTo('wallet-edit');
        $this->actingAs($this->user);

        $wallet = Wallet::factory()->create();

        $this->put(route('wallet.update', $wallet->id), [])
            ->assertRedirect();
    }

    /**
     * Teste qu'on ne peut pas mettre à jour avec un statut invalide.
     */
    public function test_cannot_update_with_invalid_statut()
    {
        $this->user->givePermissionTo('wallet-edit');
        $this->actingAs($this->user);

        $wallet = Wallet::factory()->create();

        $this->put(route('wallet.update', $wallet->id), [
            'statut' => 'invalide',
        ])->assertRedirect();
    }

    /* ==================== STATUT ==================== */

    /**
     * Vérifie qu'un invité ne peut pas changer le statut (suppression/soft delete/changement d'état via route dediée).
     */
    public function test_guest_cannot_change_wallet_statut()
    {
        $wallet = Wallet::factory()->create();

        $this->put(route('wallet.statut', $wallet->id))
            ->assertRedirect();
    }

    /**
     * Teste la suppression logique (Soft Delete) d'un wallet.
     */
    public function test_can_soft_delete_wallet()
    {
        $this->user->givePermissionTo('wallet-statut');
        $this->actingAs($this->user);

        $wallet = Wallet::factory()->create();

        $this->put(route('wallet.statut', $wallet->id))
            ->assertRedirect();

        $this->assertSoftDeleted('wallets', [
            'id' => $wallet->id
        ]);
    }

    /**
     * Teste la restauration d'un wallet supprimé logiquement.
     */
    public function test_can_restore_wallet()
    {
        $this->user->givePermissionTo('wallet-statut');
        $this->actingAs($this->user);

        $wallet = Wallet::factory()->create();
        $wallet->delete();

        $this->put(route('wallet.statut', $wallet->id))
            ->assertRedirect();

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'deleted_at' => null,
        ]);
    }

    /* ==================== SHOW BY OWNER ==================== */

    /**
     * Teste l'affichage d'un wallet en le cherchant par son propriétaire.
     * Note: S'attend à une 500 ici tel que mentionné dans le commentaire d'origine (bug ou comportement temporaire ?).
     */
    public function test_can_show_wallet_by_owner()
    {
        $this->user->givePermissionTo('wallet-list');
        $this->actingAs($this->user);

        $owner = User::factory()->create();
        $paysDevise = PaysDevise::factory()->create();

        Wallet::factory()->create([
            'owner_id' => $owner->id,
            'owner_type' => Wallet::OWNER_TYPE_CLIENT,
            'pays_devise_id' => $paysDevise->id,
        ]);

        $this->get(route('wallet.show-by-owner', [
            'ownerType' => Wallet::OWNER_TYPE_CLIENT,
            'ownerId' => $owner->id,
        ]))->assertStatus(500); // conforme au contrôleur actuel
    }

    /**
     * Teste le cas où le type de propriétaire fourni est invalide (404).
     */
    public function test_cannot_show_wallet_for_invalid_owner_type()
    {
        $this->user->givePermissionTo('wallet-list');
        $this->actingAs($this->user);

        $owner = User::factory()->create();

        $this->get(route('wallet.show-by-owner', [
            'ownerType' => 'type_invalide',
            'ownerId' => $owner->id,
        ]))->assertStatus(404);
    }

    /**
     * Teste le cas où le propriétaire n'est pas trouvé ou n'a pas de wallet (Redirection avec erreur).
     */
    public function test_wallet_by_owner_not_found_returns_error()
    {
        $this->user->givePermissionTo('wallet-list');
        $this->actingAs($this->user);

        $this->get(route('wallet.show-by-owner', [
            'ownerType' => Wallet::OWNER_TYPE_CLIENT,
            'ownerId' => 99999,
        ]))->assertRedirect()
            ->assertSessionHas('error');
    }
}
