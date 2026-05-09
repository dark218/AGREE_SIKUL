<?php

namespace Modules\Pos\Http\Controllers;

use App\Services\MoneyService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Administration\Http\Controllers\ErrorLogController;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\Terminal;
use Modules\Pos\Entities\SessionCaisse;
use Modules\Pos\Entities\VentePos;

class SessionCaisseCaissierController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:session-caisse-caissier-list', ['only' => ['index','current']]);
        $this->middleware('permission.check:session-caisse-caissier-ouverture', ['only' => ['ouverture']]);
        $this->middleware('permission.check:session-caisse-caissier-fermerture', ['only' => ['fermerture']]);
    }

    /**
     * Interface principale du caissier (POS)
     */
    public function index()
    {
        try {
            $user = auth()->user();
            if ($user->pays_id == null) {
                return Inertia::render('Pos::SessionCaisseCaissier/Index', [
                    'sessionActive' => null,
                    'devises' => [],
                    'error' => trans('pays_required'),
                ]);
            }
            $employe = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.caissier'))
                ->first();
            if (!$employe) {
                return Inertia::render('Pos::SessionCaisseCaissier/Index', [
                    'sessionActive' => null,
                    'devises' => [],
                    'error' => trans('caissier_feature'),
                ]);
            }

            // Récupérer la session en attente OU ouverte
            $session = SessionCaisse::with(['caisse', 'caisse.pointVente', 'caissier.user', 'terminal'])
                ->where('caissier_id', $employe->id)
                ->whereIn('statut', [
                    config('appconstants.session_caisse_statut.attente'),
                    config('appconstants.session_caisse_statut.ouverte')
                ])
                ->first();

            // Récupérer les devises autorisées pour le pays
            $devisesAutorisees = [];
            if ($user->pays) {
                $devisesAutorisees = $user->pays
                    ->paysDevises()
                    ->with('devise')
                    ->get()
                    ->map(fn($pd) => ['value' => $pd->devise->code, 'label' => $pd->devise->code])
                    ->toArray();
            }

            // Préparer les données de session pour le frontend
            $sessionData = null;
            if ($session) {
                $sessionData = [
                    'id' => $session->id,
                    'reference' => $session->reference,
                    'point_vente_nom'=>
                        ($session->caisse->pointVente->nom ?? 'N/A') ,
                    'caisse_nom' =>
                        ($session->caisse->code ?? 'N/A') .
                        ' - ' .
                        ($session->caisse->nom ?? 'N/A'),
                    'caissier_nom' =>
                        ($session->caissier->user->nom ?? '') . ' ' .
                        ($session->caissier->user->prenoms ?? ''),
                    'terminal_numero' =>
                        $session->terminal->numero_serie ?? 'N/A',
                    'date_ouverture' => $session->opened_at?->format('d/m/Y H:i'),
                    'date_fermeture' => $session->closed_at?->format('d/m/Y H:i'),
                    'fond_ouverture' => $session->fond_ouverture_cents !== null && $session->devise
                        ? (float) str_replace(' ', '', MoneyService::toDisplay($session->fond_ouverture_cents, $session->devise))
                        : null,
                    'fond_ouverture_cents' => $session->fond_ouverture_cents,
                    'total_encaisse' => $session->total_encaisse_cents !== null && $session->devise
                        ? (float) str_replace(' ', '', MoneyService::toDisplay($session->total_encaisse_cents, $session->devise))
                        : null,
                    'total_encaisse_cents' => $session->total_encaisse_cents ?? 0,
                    'total_reel_cents' => $session->total_reel_cents ?? null,
                    'ecart_cents' => $session->ecart_cents ?? null,
                    'statut' => $session->statut,
                    'devise' => $session->devise,
                ];
            }

            // Récupérer les 5 dernières ventes de cette session
            $dernieresVentes = [];
            if ($session && $session->statut === config('appconstants.session_caisse_statut.ouverte')) {
                $ventes = VentePos::with(['employe.user'])
                    ->where('sessions_caisse_id', $session->id)
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get();

                $dernieresVentes = $ventes->map(function($vente) {
                    $statutLabels = [
                        'en_attente' => trans('pos.ventePos.statuts.en_attente'),
                        'validee' => trans('pos.ventePos.statuts.validee'),
                        'annulee' => trans('pos.ventePos.statuts.annulee'),
                        'remboursee' => trans('pos.ventePos.statuts.remboursee'),
                        'partielle' => trans('pos.ventePos.statuts.partielle'),
                    ];
                    $statutClasses = [
                        'en_attente' => 'warning',
                        'validee' => 'success',
                        'annulee' => 'danger',
                        'remboursee' => 'info',
                        'partielle' => 'secondary',
                    ];
                    return [
                        'id' => $vente->id,
                        'reference' => $vente->reference,
                        'total' => $vente->total_cents ? (float) str_replace(' ', '', MoneyService::toDisplay($vente->total_cents, $vente->devise)) : 0,
                        'devise' => $vente->devise,
                        'mode_paiement' => $vente->mode_paiement,
                        'statut' => $vente->statut,
                        'statut_label' => $statutLabels[$vente->statut] ?? $vente->statut,
                        'statut_class' => $statutClasses[$vente->statut] ?? 'secondary',
                        'date' => $vente->created_at->format('H:i'),
                        'session_id' => $vente->sessions_caisse_id,
                        'caissier' => trim(($vente->employe?->user?->prenoms ?? '') . ' ' . ($vente->employe?->user?->nom ?? '')) ?: 'N/A',
                    ];
                })->toArray();
            }

            return Inertia::render('Pos::SessionCaisseCaissier/Index', [
                'sessionActive' => $sessionData,
                'devises' => $devisesAutorisees,
                'dernieresVentes' => $dernieresVentes,
            ]);

        } catch (\Throwable $e) {
            log_error("SessionCaisseCaissier", "index", $e->getMessage());
            // Afficher l'interface POS avec une erreur plutôt que de rediriger
            return Inertia::render('Pos::SessionCaisseCaissier/Index', [
                'sessionActive' => null,
                'devises' => [],
                'error' => trans('error_loading_form'),
            ]);
        }
    }

    /**
     * Ouverture de la caisse par le caissier
     */
    public function ouverture(Request $request)
    {
        try {
            $user = auth()->user();
            if ($user->pays_id == null) {
                return back()->with('error', trans('pays_required'));
            }

            // Récupérer les devises autorisées
            $devisesAutorisees = $user->pays
                ->paysDevises()
                ->with('devise')
                ->get()
                ->pluck('devise.code')
                ->toArray();

            // Validation initiale
            $validatedData = $request->validate([
                'fond_ouverture' => 'required|numeric|min:0',
                'session_id' => 'required|exists:sessions_caisse,id',
                'devise' => 'required|in:' . implode(',', $devisesAutorisees),
            ]);

            // Vérifier que l'utilisateur est bien caissier
            $employe = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.caissier'))
                ->first();
            if (!$employe) {
                return back()->with('error', trans('caissier_feature'));
            }

            // Charger la session
            $session = SessionCaisse::findOrFail($validatedData['session_id']);

            // Vérifier que la session appartient bien à ce caissier
            if ($session->caissier_id !== $employe->id) {
                return back()->with('error', trans('session_not_owner'));
            }

            // Vérifier si la session est déjà ouverte
            if ($session->statut === config('appconstants.session_caisse_statut.ouverte')) {
                return back()->with('error', trans('session_ouverte'));
            }

            // Vérifier si la session est bien en attente
            if ($session->statut !== config('appconstants.session_caisse_statut.attente')) {
                return back()->with('error', trans('session_not_found'));
            }

            // Convertir le montant en cents avec MoneyService
            $fondOuvertureCents = MoneyService::toDatabase(
                $validatedData['fond_ouverture'],
                $validatedData['devise']
            );

            // Mettre à jour la session
            $session->update([
                'fond_ouverture_cents' => $fondOuvertureCents,
                'statut' => config('appconstants.session_caisse_statut.ouverte'),
                'opened_at' => now(),
                'devise' => $validatedData['devise'],
                'total_encaisse_cents' => 0,
            ]);

            return redirect()
                ->back()
                ->with('success', trans('session_ouverture_success'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            log_error("SessionCaisseCaissier", "ouverture", $e->getMessage());
            return back()->with('error', trans('session_error'))->withInput();
        }
    }
    public function current()
    {
        try {
            $user = auth()->user();

            $employe = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.caissier'))
                ->first();

            if (!$employe) {
                return back()->with('error', trans('caissier_feature'));
            }

            $session = SessionCaisse::where('caissier_id', $user->id)
                ->where('statut', config('appconstants.session_caisse_statut.ouverte'))
                ->first();

            if (!$session) {
                return back()->with('error', trans('session_not_open'));
            }

            return Inertia::render('Pos::SessionCaisseCaissier/Active', [
                'session' => [
                    'id' => $session->id,
                    'caisse' => $session->caisse->nom ?? 'N/A',
                    'fond_ouverture' =>(float) str_replace(' ', '',  MoneyService::toDisplay(
                        $session->fond_ouverture_cents,
                        $session->devise
                    )),
                    'total_encaisse' => (float) str_replace(' ', '', MoneyService::toDisplay(
                        $session->total_encaisse_cents,
                        $session->devise
                    )),
                    'devise' => $session->devise,
                ],
            ]);

        } catch (\Throwable $e) {
            log_error("SessionCaisseCaissier", "current", $e->getMessage());
            return back()->with('error', trans('error_loading_session'));
        }
    }


    public function fermerture(Request $request)
    {
        try {
            $user = auth()->user();
            // Validation (montant lisible)
            $validatedData = $request->validate([
                'session_id' => 'required|exists:sessions_caisse,id',
                'total_reel' => 'required|numeric|min:0',
            ]);

            // Vérifier que l'utilisateur est bien caissier
            $employe = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.caissier'))
                ->first();
            if (!$employe) {
                return back()->with('error', trans('caissier_feature'));
            }
            // Charger la session
            $session = SessionCaisse::findOrFail($validatedData['session_id']);
            // Vérifier propriétaire
            if ($session->caissier_id !== $employe->id) {
                return back()->with('error', trans('session_not_owner'));
            }

            // Vérifier statut
            if ($session->statut !== config('appconstants.session_caisse_statut.ouverte')) {
                return back()->with('error', trans('session_not_open'));
            }

            // Vérifier s'il existe des ventes en attente sur cette session
            $ventesEnAttente = VentePos::where('sessions_caisse_id', $session->id)
                ->where('statut', config('appconstants.statut_vente_pos.en_attente'))
                ->exists();

            if ($ventesEnAttente) {
                return back()->with(
                    'error',
                    trans('session_has_pending_sales')
                );
            }

            // Conversion du montant réel selon la devise de la session
            $totalReelCents = MoneyService::toDatabase(
                $validatedData['total_reel'],
                $session->devise
            );

            // Totaux : fond de caisse + encaissements
            $fondCaisse = (int) ($session->fond_ouverture_cents ?? 0);
            $totalEncaisse = (int) ($session->total_encaisse_cents ?? 0);
            $totalTheorique = $fondCaisse + $totalEncaisse;
            $ecart = $totalReelCents - $totalTheorique;

            // Cas écart ≠ 0 → validation manager
            if ($ecart !== 0) {
                $session->update([
                    'statut' => config('appconstants.session_caisse_statut.attente_validation'),
                    'total_reel_cents' => $totalReelCents,
                    'ecart_cents' => $ecart,
                ]);

                return back()->with(
                    'warning',
                    trans('session_attente_validation_manager')
                );
            }
            // Cas normal → clôture directe
            $session->update([
                'statut' => config('appconstants.session_caisse_statut.fermee'),
                'closed_at' => now(),
                'total_reel_cents' => $totalReelCents,
                'ecart_cents' => 0,
            ]);

            return redirect()
                ->back()
                ->with('success', trans('session_cloture_success'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            log_error("SessionCaisseCaissier", "fermerture", $e->getMessage());
            return back()->with('error', trans('session_cloture_error'))->withInput();
        }
    }
}
