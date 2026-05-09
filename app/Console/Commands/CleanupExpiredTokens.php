<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jwt:cleanup-expired-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoyer les tokens JWT expirés et la blacklist';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Démarrage du nettoyage des tokens JWT expirés...');

        $deletedTokens = 0;
        $deletedBlacklist = 0;

        try {
            // 1. Nettoyer les tokens expirés dans jwt_tokens
            $expiredTokens = DB::table('jwt_tokens')
                ->where('expires_at', '<', now())
                ->orWhere('is_active', false)
                ->get();

            foreach ($expiredTokens as $token) {
                DB::table('jwt_tokens')
                    ->where('id', $token->id)
                    ->delete();
                $deletedTokens++;
            }

            // 2. Nettoyer la blacklist des tokens expirés
            $expiredBlacklist = DB::table('jwt_blacklist')
                ->where('expires_at', '<', now())
                ->get();

            foreach ($expiredBlacklist as $blacklist) {
                DB::table('jwt_blacklist')
                    ->where('id', $blacklist->id)
                    ->delete();
                $deletedBlacklist++;
            }

            // 3. Nettoyer les anciens tokens inactifs (plus de 30 jours)
            $oldInactiveTokens = DB::table('jwt_tokens')
                ->where('is_active', false)
                ->where('updated_at', '<', now()->subDays(30))
                ->get();

            foreach ($oldInactiveTokens as $token) {
                DB::table('jwt_tokens')
                    ->where('id', $token->id)
                    ->delete();
                $deletedTokens++;
            }

            $this->info("Nettoyage terminé avec succès !");
            $this->info("Tokens JWT supprimés : {$deletedTokens}");
            $this->info("Entrées blacklist supprimées : {$deletedBlacklist}");

            Log::info('Nettoyage des tokens JWT expirés', [
                'deleted_tokens' => $deletedTokens,
                'deleted_blacklist' => $deletedBlacklist,
                'command' => 'jwt:cleanup-expired-tokens',
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Erreur lors du nettoyage des tokens : ' . $e->getMessage());
            
            Log::error('Erreur lors du nettoyage des tokens JWT', [
                'error' => $e->getMessage(),
                'command' => 'jwt:cleanup-expired-tokens',
            ]);

            return Command::FAILURE;
        }
    }
}
