<?php

namespace App\Console\Commands;

use App\Models\TauxdeChange;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class UpdateTauxDeChange extends Command
{
    protected $signature = 'taux:update';
    protected $description = 'Met à jour les taux de change depuis ExchangeRate API';

    public function handle()
    {
        $apiKey = config('services.exchangerate.key');
        $symbols = ['CAD', 'USD', 'EUR', 'XOF', 'XAF', 'CDF', 'GNF']; // à ajuster selon tes besoins
        $base = 'EUR';

        $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$base}");

        if ($response->failed()) {
            $this->error('Erreur lors de l’appel à ExchangeRate API');
            return 1;
        }

        $rates = $response->json('conversion_rates');
        if (!$rates) {
            $this->error('Réponse invalide de ExchangeRate API');
            return 1;
        }

        $now = Carbon::now();

        foreach ($symbols as $from) {
            foreach ($symbols as $to) {
                if ($from === $to) continue;

                if (!isset($rates[$from]) || !isset($rates[$to])) {
                    $this->warn("Taux manquant pour $from ou $to");
                    continue;
                }

                // Convertir: from -> to = rate(to) / rate(from)
                $taux = $rates[$to] / $rates[$from];

                // Marge à appliquer
                $marge = 0; // Par exemple : on ajoute 0.1 au taux

                $tauxAvecMarge = $taux + $marge;

                TauxdeChange::updateOrCreate(
                    [
                        'devise_source' => $from,
                        'devise_cible' => $to,
                    ],
                    [
                        'taux' => round($tauxAvecMarge, 4),
                        'fetched_at' => $now,
                    ]
                );

            }
        }

        $this->info('Taux de change mis à jour avec succès.');
        return 0;
    }
}
