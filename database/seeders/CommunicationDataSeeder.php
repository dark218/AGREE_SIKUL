<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Communication\Entities\Message;
use Modules\Communication\Entities\Annonce;
use Modules\Communication\Entities\CommentaireAnnonce;
use Modules\Communication\Entities\Notification;
use App\Models\User;

class CommunicationDataSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->count() < 2) {
            $users = User::factory(10)->create();
        }

        // Créer messages
        Message::factory(30)->create();

        // Créer annonces
        $annonces = Annonce::factory(15)->create();

        // Créer commentaires sur annonces
        $annonces->each(function ($annonce) use ($users) {
            CommentaireAnnonce::factory(rand(2, 5))
                ->for($annonce)
                ->create();
        });

        // Créer notifications
        $users->each(function ($user) {
            Notification::factory(rand(5, 15))
                ->state(['utilisateur_id' => $user->id])
                ->create();
        });

        $this->command->info('Communication data seeded successfully!');
    }
}
