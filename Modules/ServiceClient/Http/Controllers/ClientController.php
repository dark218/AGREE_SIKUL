<?php

namespace Modules\ServiceClient\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Generator;
use App\Services\PasswordResetService;
use App\Services\ValidationService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Fichier;
use Modules\Parametrage\Entities\FournisseurPaiement;
use Modules\Parametrage\Entities\Pays;
use Modules\ServiceClient\Entities\Client;
use Modules\ServiceClient\Entities\MoyenPaiement;
use Modules\ServiceClient\Resources\MoyenPaiementCollection;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Services\WalletService;
use Spatie\Permission\Models\Role;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:client-list', ['only' => ['index', 'show', 'moyensPaiement', 'showMoyenPaiement']]);
        $this->middleware('permission.check:client-create', ['only' => ['create', 'store', 'createMoyenPaiement', 'storeMoyenPaiement']]);
        $this->middleware('permission.check:client-edit', ['only' => ['edit', 'update', 'editMoyenPaiement', 'updateMoyenPaiement', 'toggleStatutMoyenPaiement', 'toggleDefautMoyenPaiement']]);
        $this->middleware('permission.check:client-statut', ['only' => ['statut', 'suspendre', 'bloquer']]);
    }

    /**
     * Display a listing of client.
     */
    public function index(Request $request)
    {
        try {
            $paysCurrent = auth()->user()->pays_id;

            $statut = $request->input('statut');
            $kyc_status = $request->input('kyc_status');
            $nom = $request->input('nom');
            $prenoms = $request->input('prenoms');
            $login = $request->input('login');
            $email = $request->input('email');
            $pays_id = $request->input('pays_id');

            // Filtres
            $filters = [
                'nom' => $nom,
                'prenoms' => $prenoms,
                'login' => $login,
                'email' => $email,
                'pays_id' => $pays_id,
                'statut' => $statut,
                'kyc_status' => $kyc_status,
            ];

            // Query des utilisateurs
            $query = Client::with(['pays'])
                ->orderBy('created_at', 'DESC');

            if (!empty($filters['nom'])) {
                $query->where('nom', 'LIKE', "%{$filters['nom']}%");
            }

            if (!empty($filters['prenoms'])) {
                $query->where('prenoms', 'LIKE', "%{$filters['prenoms']}%");
            }

            if (!empty($filters['login'])) {
                $query->where('login', 'LIKE', "%{$filters['login']}%");
            }

            if (!empty($filters['email'])) {
                $query->where('email', 'LIKE', "%{$filters['email']}%");
            }

            if (!empty($filters['pays_id'])) {
                $query->where('pays_id', $filters['pays_id']);
            } elseif (!is_null($paysCurrent)) {
                $query->where('pays_id', $paysCurrent);
            }

            if (!empty($filters['kyc_status'])) {
                $query->where('kyc_status', $filters['kyc_status']);
            }

            if (!empty($filters['statut'])) {
                $query->where('statut', $filters['statut']);
            }

            $client = $query->paginate(10)->withQueryString();

            // Transformer les utilisateurs pour Vue
            $client->getCollection()->transform(function ($client) {
                return [
                    'id' => $client->id,
                    'uuid' => $client->uuid,
                    'nom' => $client->nom,
                    'prenoms' => $client->prenoms,
                    'login' => $client->login,
                    'email' => $client->email,
                    'kyc_status' => $client->kyc_status,
                    'statut' => $client->statut,
                    'pays' => $client->pays ? $client->pays->libelle : null,
                ];
            });

            // Statuts pour le filtre
            $statuts = [
                ['value' => 'non_actif', 'label' => __('statuts.inactif')],
                ['value' => 'actif', 'label' => __('statuts.actif')],
                ['value' => 'suspendu', 'label' => __('statuts.suspendu')],
                ['value' => 'bloque', 'label' => __('statuts.bloque')],
                ['value' => 'supprime', 'label' => __('statuts.supprime')],
            ];
            $kycStatuts = [
                ['value' => 'non_verifie', 'label' => trans('kyc.non_verifie')],
                ['value' => 'en_attente', 'label' => trans('kyc.en_attente')],
                ['value' => 'verifie', 'label' => trans('kyc.verifie')],
                ['value' => 'rejete', 'label' => trans('kyc.rejete')],
            ];

            return Inertia::render('ServiceClient::Client/Index', [
                'client' => $client,
                'pays' => Pays::select('id', 'libelle')->get(),
                'filters' => $filters,
                'paysCurrent' => $paysCurrent,
                'statuts' => $statuts,
                'kycStatuts' => $kycStatuts,
                'userStatuts' => config('appconstants.user_statut'),
                'kycStatutsConst' => config('appconstants.user_kyc_status'),
            ]);
        } catch (\Throwable $e) {
            log_error("Client", "index", $e->getMessage());
            return redirect()->route('home')->with('error', trans('Erreur'));
        }
    }

    /**
     * Display a listing of client's payment methods.
     */
    public function moyensPaiement(Request $request, $uuid)
    {
        try {
            $client = Client::where('uuid', $uuid)->firstOrFail();
            $paysCurrent = auth()->user()->pays_id;

            // Filtres
            $type = $request->input('type');
            $statut = $request->input('statut');
            $is_defaut = $request->input('is_defaut');
            $fournisseur_id = $request->input('fournisseur_id');
            $search = $request->input('search');

            $filters = [
                'type' => $type,
                'statut' => $statut,
                'is_defaut' => $is_defaut,
                'fournisseur_id' => $fournisseur_id,
                'search' => $search,
            ];

            // Query des moyens de paiement
            $query = MoyenPaiement::with(['user', 'fournisseur'])
                ->where('users_id', $client->id)
                ->orderBy('is_defaut', 'DESC')
                ->orderBy('created_at', 'DESC');

            // Filtre par type
            if (!empty($filters['type'])) {
                $query->byType($filters['type']);
            }

            // Filtre par statut
            if (!empty($filters['statut'])) {
                $query->where('statut', $filters['statut']);
            }

            // Filtre par défaut
            if (!empty($filters['is_defaut'])) {
                $query->where('is_defaut', $filters['is_defaut'] === 'true');
            }

            // Filtre par fournisseur
            if (!empty($filters['fournisseur_id'])) {
                $query->where('fournisseur_id', $filters['fournisseur_id']);
            }
            // Recherche textuelle
            if (!empty($filters['search'])) {
                $searchTerm = $filters['search'];
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('label', 'LIKE', "%{$searchTerm}%")
                      ->orWhereHas('fournisseur', function ($subQ) use ($searchTerm) {
                          $subQ->where('nom', 'LIKE', "%{$searchTerm}%");
                      });
                });
            }

            $moyensPaiement = $query->paginate(10)->withQueryString();

            // Options pour les filtres
            $types = [
                ['value' => 'mm', 'label' => trans('modules.service_client.moyen_paiement.type_mm')],
                ['value' => 'iban', 'label' => trans('modules.service_client.moyen_paiement.type_iban')],
                ['value' => 'card', 'label' => trans('modules.service_client.moyen_paiement.type_card')],
                ['value' => 'wallet', 'label' => trans('modules.service_client.moyen_paiement.type_wallet')],
            ];

            $statuts = [
                ['value' => 'actif', 'label' => trans('modules.service_client.moyen_paiement.statut_actif')],
                ['value' => 'desactive', 'label' => trans('modules.service_client.moyen_paiement.statut_desactive')],
            ];

            $defautOptions = [
                ['value' => 'true', 'label' => trans('Oui')],
                ['value' => 'false', 'label' => trans('Non')],
            ];

            // Récupérer les fournisseurs de paiement
            $fournisseursQuery = FournisseurPaiement::select('id', 'nom', 'type')
                ->where('statut', FournisseurPaiement::STATUT_ACTIF)
                ->whereIn('type', [FournisseurPaiement::TYPE_MOBILE_MONEY,FournisseurPaiement::TYPE_BANQUE])
                ->orderBy('nom');

            // Filtrer par pays si l'utilisateur a un pays_current
            if (!is_null($paysCurrent)) {
                $fournisseursQuery->whereHas('paysDevise', function ($query) use ($paysCurrent) {
                    $query->where('pays_id', $paysCurrent);
                });
            }

            $fournisseurs = $fournisseursQuery->get()
                ->map(function ($fournisseur) {
                    return [
                        'value' => $fournisseur->id,
                        'label' => $fournisseur->nom . ' (' . $fournisseur->type . ')',
                    ];
                });

            // Statistiques
            $stats = [
                'total' => MoyenPaiement::where('users_id', $client->id)->count(),
                'actifs' => MoyenPaiement::where('users_id', $client->id)->actif()->count(),
                'defaut' => MoyenPaiement::where('users_id', $client->id)->defaut()->count(),
                'par_type' => MoyenPaiement::where('users_id', $client->id)
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray(),
            ];

            return Inertia::render('ServiceClient::Client/MoyensPaiement', [
                'client' => $this->formatClientForVue($client),
                'moyensPaiement' => new MoyenPaiementCollection($moyensPaiement),
                'filters' => $filters,
                'types' => $types,
                'statuts' => $statuts,
                'defautOptions' => $defautOptions,
                'fournisseurs' => $fournisseurs,
                'stats' => $stats,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("Client", "moyensPaiement", $e->getMessage());
            return redirect()->route('client.show', $uuid)->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Show the form for creating a new payment method for client.
     */
    public function createMoyenPaiement($uuid)
    {
        try {
            $client = Client::where('uuid', $uuid)->firstOrFail();
            $paysCurrent = auth()->user()->pays_id;

            // Types de moyens de paiement
            $types = [
                ['value' => 'mm', 'label' => trans('modules.service_client.moyen_paiement.type_mm')],
                ['value' => 'iban', 'label' => trans('modules.service_client.moyen_paiement.type_iban')],
                ['value' => 'card', 'label' => trans('modules.service_client.moyen_paiement.type_card')],
                ['value' => 'wallet', 'label' => trans('modules.service_client.moyen_paiement.type_wallet')],
            ];

            // Récupérer les fournisseurs de paiement actifs
            $fournisseursQuery = FournisseurPaiement::select('id', 'nom', 'type')
                ->where('statut', FournisseurPaiement::STATUT_ACTIF)
                ->whereIn('type', [FournisseurPaiement::TYPE_MOBILE_MONEY,FournisseurPaiement::TYPE_BANQUE])
                ->orderBy('nom');

            // Filtrer par pays si l'utilisateur a un pays_current
            if (!is_null($paysCurrent)) {
                $fournisseursQuery->whereHas('paysDevise', function ($query) use ($paysCurrent) {
                    $query->where('pays_id', $paysCurrent);
                });
            }

            $fournisseurs = $fournisseursQuery->get()
                ->map(function ($fournisseur) {
                    return [
                        'value' => $fournisseur->id,
                        'label' => $fournisseur->nom . ' (' . $fournisseur->type . ')',
                        'type' => $fournisseur->type,
                    ];
                });

            return Inertia::render('ServiceClient::Client/CreateMoyenPaiement', [
                'client' => $this->formatClientForVue($client),
                'types' => $types,
                'fournisseurs' => $fournisseurs,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("Client", "createMoyenPaiement", $e->getMessage());
            return redirect()->route('client.moyens-paiement', $uuid)->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Store a newly created payment method for client.
     */
    public function storeMoyenPaiement(Request $request, $uuid)
    {
        try {
            $client = Client::where('uuid', $uuid)->firstOrFail();

            $validatedData = $request->validate([
                'fournisseur_id' => 'required|exists:fournisseurs_paiement,id',
                'type' => 'required|in:mm,iban,card,wallet',
                'label' => 'nullable|string|max:255',
                'identifiant' => 'required|string|max:255',
                'token_provider' => 'nullable|string|max:500',
                'is_defaut' => 'boolean',
                'metadata' => 'nullable|array',
            ]);

            DB::beginTransaction();

            // Si ce moyen est défini comme par défaut, désactiver les autres
            if ($validatedData['is_defaut'] ?? false) {
                MoyenPaiement::where('users_id', $client->id)
                    ->update(['is_defaut' => false]);
            }

            // Créer le moyen de paiement
            $moyenPaiement = MoyenPaiement::create([
                'users_id' => $client->id,
                'fournisseur_id' => $validatedData['fournisseur_id'],
                'type' => $validatedData['type'],
                'label' => $validatedData['label'],
                'identifiant_chiffre' => $validatedData['identifiant'], // Sera chiffré automatiquement
                'token_provider' => $validatedData['token_provider'], // Sera chiffré automatiquement
                'is_defaut' => $validatedData['is_defaut'] ?? false,
                'statut' => MoyenPaiement::STATUT_ACTIF,
                'metadata' => $validatedData['metadata'] ?? null,
            ]);

            DB::commit();

            return redirect()->route('client.moyens-paiement', $uuid)
                ->with('success', 'Moyen de paiement créé avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', 'Veuillez corriger les erreurs dans le formulaire.');
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Client", "storeMoyenPaiement", $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la création du moyen de paiement.');
        }
    }

    /**
     * Display the specified payment method.
     */
    public function showMoyenPaiement($uuid, $moyenId)
    {
        try {
            $client = Client::where('uuid', $uuid)->firstOrFail();

            $moyenPaiement = MoyenPaiement::with(['user', 'fournisseur'])
                ->where('users_id', $client->id)
                ->where('id', $moyenId)
                ->firstOrFail();

            // Formater les données pour l'affichage
            $moyenFormate = [
                'id' => $moyenPaiement->id,
                'type' => $moyenPaiement->type,
                'type_label' => match($moyenPaiement->type) {
                    'mm' => 'Mobile Money',
                    'iban' => 'IBAN',
                    'card' => 'Carte',
                    'wallet' => 'Wallet',
                    default => 'Inconnu',
                },
                'label' => $moyenPaiement->label,
                'identifiant_masque' => $moyenPaiement->identifiant_masque,
                'identifiant_chiffre'=>$moyenPaiement->identifiant_chiffre,
                'libelle_complet' => $moyenPaiement->libelle_complet,
                'is_defaut' => $moyenPaiement->is_defaut,
                'statut' => $moyenPaiement->statut,
                'statut_label' => $moyenPaiement->statut === MoyenPaiement::STATUT_ACTIF ? 'Actif' : 'Désactivé',
                'is_utilisable' => $moyenPaiement->isUtilisable(),
                'metadata' => $moyenPaiement->metadata,
                'created_at' => $moyenPaiement->created_at,
                'updated_at' => $moyenPaiement->updated_at,
                'fournisseur' => $moyenPaiement->fournisseur ? [
                    'id' => $moyenPaiement->fournisseur->id,
                    'nom' => $moyenPaiement->fournisseur->nom,
                    'type' => $moyenPaiement->fournisseur->type,
                ] : null,
            ];

            return Inertia::render('ServiceClient::Client/ShowMoyenPaiement', [
                'client' => $this->formatClientForVue($client),
                'moyenPaiement' => $moyenFormate,
            ]);
        } catch (\Throwable $e) {
            log_error("Client", "showMoyenPaiement", $e->getMessage());
            return redirect()->route('client.moyens-paiement', $uuid)->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Show the form for editing the specified payment method.
     */
    public function editMoyenPaiement($uuid, $moyenId)
    {
        try {
            $client = Client::where('uuid', $uuid)->firstOrFail();
            $paysCurrent = auth()->user()->pays_id;

            $moyenPaiement = MoyenPaiement::with(['fournisseur'])
                ->where('users_id', $client->id)
                ->where('id', $moyenId)
                ->firstOrFail();

            // Types de moyens de paiement
            $types = [
                ['value' => 'mm', 'label' => trans('modules.service_client.moyen_paiement.type_mm')],
                ['value' => 'iban', 'label' => trans('modules.service_client.moyen_paiement.type_iban')],
                ['value' => 'card', 'label' => trans('modules.service_client.moyen_paiement.type_card')],
                ['value' => 'wallet', 'label' => trans('modules.service_client.moyen_paiement.type_wallet')],
            ];

            // Statuts possibles
            $statuts = [
                ['value' => MoyenPaiement::STATUT_ACTIF, 'label' => trans('modules.service_client.moyen_paiement.statut_actif')],
                ['value' => MoyenPaiement::STATUT_DESACTIVE, 'label' => trans('modules.service_client.moyen_paiement.statut_desactive')],
            ];

            // Récupérer les fournisseurs de paiement actifs
            $fournisseursQuery = FournisseurPaiement::select('id', 'nom', 'type')
                ->where('statut', FournisseurPaiement::STATUT_ACTIF)
                ->whereIn('type', [FournisseurPaiement::TYPE_MOBILE_MONEY,FournisseurPaiement::TYPE_BANQUE])
                ->orderBy('nom');

            // Filtrer par pays si l'utilisateur a un pays_current
            if (!is_null($paysCurrent)) {
                $fournisseursQuery->whereHas('paysDevise', function ($query) use ($paysCurrent) {
                    $query->where('pays_id', $paysCurrent);
                });
            }

            $fournisseurs = $fournisseursQuery->get()
                ->map(function ($fournisseur) {
                    return [
                        'value' => $fournisseur->id,
                        'label' => $fournisseur->nom . ' (' . $fournisseur->type . ')',
                        'type' => $fournisseur->type,
                    ];
                });

            // Formater le moyen de paiement pour l'édition (sans les données sensibles)
            $moyenFormate = [
                'id' => $moyenPaiement->id,
                'fournisseur_id' => $moyenPaiement->fournisseur_id,
                'type' => $moyenPaiement->type,
                'label' => $moyenPaiement->label,
                'identifiant' => '', // Ne pas pré-remplir l'identifiant pour sécurité
                'token_provider' => '', // Ne pas pré-remplir le token pour sécurité
                'is_defaut' => $moyenPaiement->is_defaut,
                'statut' => $moyenPaiement->statut,
                'metadata' => $moyenPaiement->metadata,
                'identifiant_masque' => $moyenPaiement->identifiant_masque, // Pour affichage uniquement
                'identifiant_chiffre'=>$moyenPaiement->identifiant_chiffre
            ];

            return Inertia::render('ServiceClient::Client/EditMoyenPaiement', [
                'client' => $this->formatClientForVue($client),
                'moyenPaiement' => $moyenFormate,
                'types' => $types,
                'statuts' => $statuts,
                'fournisseurs' => $fournisseurs,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("Client", "editMoyenPaiement", $e->getMessage());
            return redirect()->route('client.moyens-paiement', $uuid)->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Update the specified payment method.
     */
    public function updateMoyenPaiement(Request $request, $uuid, $moyenId)
    {
        try {
            $client = Client::where('uuid', $uuid)->firstOrFail();

            $moyenPaiement = MoyenPaiement::where('users_id', $client->id)
                ->where('id', $moyenId)
                ->firstOrFail();

            $validatedData = $request->validate([
                'fournisseur_id' => 'required|exists:fournisseurs_paiement,id',
                'type' => 'required|in:mm,iban,card,wallet',
                'label' => 'nullable|string|max:255',
                'identifiant' => 'nullable|string|max:255',
                'token_provider' => 'nullable|string|max:500',
                'is_defaut' => 'boolean',
                'statut' => 'required|in:actif,desactive',
                'metadata' => 'nullable|array',
            ]);

            DB::beginTransaction();

            // Si ce moyen est défini comme par défaut, désactiver les autres
            if ($validatedData['is_defaut'] ?? false) {
                MoyenPaiement::where('users_id', $client->id)
                    ->where('id', '!=', $moyenPaiement->id)
                    ->update(['is_defaut' => false]);
            }

            // Préparer les données de mise à jour
            $updateData = [
                'fournisseur_id' => $validatedData['fournisseur_id'],
                'type' => $validatedData['type'],
                'label' => $validatedData['label'],
                'is_defaut' => $validatedData['is_defaut'] ?? false,
                'statut' => $validatedData['statut'],
                'metadata' => $validatedData['metadata'] ?? null,
            ];

            // Mettre à jour l'identifiant seulement si fourni
            if (!empty($validatedData['identifiant'])) {
                $updateData['identifiant_chiffre'] = $validatedData['identifiant'];
            }

            // Mettre à jour le token seulement si fourni
            if (!empty($validatedData['token_provider'])) {
                $updateData['token_provider'] = $validatedData['token_provider'];
            }

            $moyenPaiement->update($updateData);

            DB::commit();

            return redirect()->route('client.moyens-paiement', $uuid)
                ->with('success', 'Moyen de paiement mis à jour avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('error', 'Veuillez corriger les erreurs dans le formulaire.');
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Client", "updateMoyenPaiement", $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du moyen de paiement.');
        }
    }

    /**
     * Toggle the status of a payment method (activate/deactivate).
     */
    public function toggleStatutMoyenPaiement($uuid, $moyenId)
    {
        try {
            $client = Client::where('uuid', $uuid)->firstOrFail();

            $moyenPaiement = MoyenPaiement::where('users_id', $client->id)
                ->where('id', $moyenId)
                ->firstOrFail();

            DB::beginTransaction();

            // Inverser le statut
            $nouveauStatut = $moyenPaiement->statut === MoyenPaiement::STATUT_ACTIF
                ? MoyenPaiement::STATUT_DESACTIVE
                : MoyenPaiement::STATUT_ACTIF;

            $moyenPaiement->update(['statut' => $nouveauStatut]);

            DB::commit();

            $message = $nouveauStatut === MoyenPaiement::STATUT_ACTIF
                ? 'Moyen de paiement activé avec succès.'
                : 'Moyen de paiement désactivé avec succès.';

            return redirect()->back()
                ->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Client", "toggleStatutMoyenPaiement", $e->getMessage());
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors du changement de statut du moyen de paiement.');
        }
    }

    /**
     * Toggle the default status of a payment method.
     */
    public function toggleDefautMoyenPaiement($uuid, $moyenId)
    {
        try {
            $client = Client::where('uuid', $uuid)->firstOrFail();

            $moyenPaiement = MoyenPaiement::where('users_id', $client->id)
                ->where('id', $moyenId)
                ->firstOrFail();

            DB::beginTransaction();

            if ($moyenPaiement->is_defaut) {
                // Retirer le statut par défaut
                $moyenPaiement->update(['is_defaut' => false]);
                $message = 'Le moyen de paiement n\'est plus le moyen par défaut.';
            } else {
                // Définir comme par défaut et désactiver les autres
                MoyenPaiement::where('users_id', $client->id)
                    ->update(['is_defaut' => false]);

                $moyenPaiement->update(['is_defaut' => true]);
                $message = 'Le moyen de paiement a été défini comme moyen par défaut.';
            }

            DB::commit();

            return redirect()->back()
                ->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Client", "toggleDefautMoyenPaiement", $e->getMessage());
            return redirect()->back()
                ->with('error', 'Une erreur est survenue lors du changement du statut par défaut du moyen de paiement.');
        }
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        try {
            $paysCurrent = auth()->user()->pays_id;

            $payss = Pays::select('id', 'libelle', 'code')->orderBy('libelle')->get();

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');

            return Inertia::render('ServiceClient::Client/Create', [
                'pays' => $payss,
                'typePieces' => $typePieces,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("Client", "create", $e->getMessage());
            return redirect()->route('client.index')->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $paysCurrent = auth()->user()->pays_id;

        $validatedData = $request->validate([
            'nom' => 'required|string|max:255',
            'prenoms' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'tel' => 'required|string|max:255|unique:users,login',
            'type_piece' => 'nullable|in:passport,cni,pc,ai',
            'numero_piece' => 'nullable|string',
            'date_delivrance' => 'nullable|date',
            'date_naissance' => 'nullable|date',
            'lieu_naissance' => 'nullable|string',
            'lieu_delivrance' => 'nullable|string',
            'pays_id' => [
                $paysCurrent === null ? 'required' : 'nullable',
                'exists:pays,id',
            ],
            'photoprofile' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'pieceverso' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'piecerecto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            DB::beginTransaction();
            $fileFields = ['photoprofile_id', 'piecerecto_id', 'pieceverso_id'];
            $fileIds = [];

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $fileName = time() . '-' . Str::random(10) . '.' . $file->extension();
                    $file->move(public_path('images'), $fileName);

                    $fileIds[$field] = Fichier::create([
                        'nom' => $fileName,
                        'source' => 'images/' . $fileName,
                    ])->id;
                }
            }

            $password = collect(range(0, 9))->shuffle()->take(4)->implode('');

            $paysId = $paysCurrent ?? $validatedData['pays_id'];
            $pays = Pays::findOrFail($paysId);
            $indicatif = $pays->code;

            // Création de l'utilisateur
            $client = Client::create([
                'nom' => $validatedData['nom'],
                'prenoms' => $validatedData['prenoms'],
                'email' => $validatedData['email'],
                'login' => $validatedData['tel'],
                'full_login' => $indicatif . $validatedData['tel'],
                'password' => Hash::make($password),
                'pays_id' => $paysId,
                'uuid' => Generator::uuid(),
                'role' => 'client',
                'alias_smil' => Generator::generateAliasSmil($validatedData['nom'], $validatedData['prenoms']),
                'lieu_delivrance' => $validatedData['lieu_delivrance'] ?? null,
                'date_delivrance' => $validatedData['date_delivrance'] ?? null,
                'date_naissance' => $validatedData['date_naissance'] ?? null,
                'lieu_naissance' => $validatedData['lieu_naissance'] ?? null,
                'type_piece' => $validatedData['type_piece'] ?? null,
                'numero_piece' => $validatedData['numero_piece'] ?? null,
                'code_owner' => Generator::codeOwner(),
                'qr_data' => Generator::QrCode($validatedData['tel']),
                'photoprofile_id' => $fileIds['photoprofile_id'] ?? null,
                'piecerecto_id' => $fileIds['piecerecto_id'] ?? null,
                'pieceverso_id' => $fileIds['pieceverso_id'] ?? null,
            ]);

            // Assignation du rôle
            $role = Role::findByName(config('appconstants.role.client'), config('appconstants.guard.web', 'web'));
            $client->assignRole($role);
            // Création des wallets
            WalletService::createWalletsForOwner($client->id, 'client', $paysId);

            DB::commit();
            PasswordResetService::sendResetLinkSms($client);

            return redirect()->route('client.index')->with('success', __('enregistrementsucces'));
        } catch (\Throwable $e) {
            DB::rollback();
            log_error("Client", "store", $e->getMessage());
            return redirect()->route('client.create')->with('error', __('Erreur') . ': ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user.
     */
    public function show($uuid)
    {
        try {
            $client = Client::with(['pays'])->where('uuid', $uuid)->firstOrFail();

            // Passer le KYC en "en_attente" si le statut actuel est "non_verifie"
            if ($client->kyc_status === config('appconstants.user_kyc_status.non_verifie')) {
                $client->update(['kyc_status' => config('appconstants.user_kyc_status.en_attente')]);
                $client->refresh();
            }

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');
            $payss = Pays::select('id', 'libelle')->get();

            return Inertia::render('ServiceClient::Client/Show', [
                'client' => $this->formatClientForVue($client),
                'pays' => $payss,
                'typePieces' => $typePieces,
                'kycStatuts' => config('appconstants.user_kyc_status'),
                'userStatuts' => config('appconstants.user_statut'),
            ]);
        } catch (\Throwable $e) {
            log_error("Client", "show", $e->getMessage());
            return redirect()->route('client.index')->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($uuid)
    {
        try {
            $client = Client::with(['pays'])->where('uuid', $uuid)->firstOrFail();
            $paysCurrent = auth()->user()->pays_id;
            $payss = Pays::select('id', 'libelle', 'code')->get();

            $typePieces = self::getTranslatedConstants('type_piece', 'type_piece_label');

            return Inertia::render('ServiceClient::Client/Edit', [
                'client' => $this->formatClientForVue($client),
                'pays' => $payss,
                'typePieces' => $typePieces,
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("Client", "edit", $e->getMessage());
            return redirect()->route('client.index')->with('error', trans('Erreuraffichage'));
        }
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $uuid)
    {
        $paysCurrent = auth()->user()->pays_id;

        try {
            $client = Client::where('uuid', $uuid)->firstOrFail();

            $validatedData = $request->validate([
                'nom' => 'required|string|max:255',
                'prenoms' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'tel' => 'required|string|max:255|unique:users,login,' . $client->id,
                'type_piece' => 'nullable|in:passport,cni,pc,ai',
                'numero_piece' => 'nullable|string',
                'date_delivrance' => 'nullable|date',
                'date_naissance' => 'nullable|date',
                'lieu_naissance' => 'nullable|string',
                'lieu_delivrance' => 'nullable|string',
                'pays_id' => [
                    $paysCurrent === null ? 'required' : 'nullable',
                    'exists:pays,id',
                ],
            ]);
            DB::beginTransaction();

            // Gestion des fichiers
            $fileFields = [
                'photoprofile_id' => 'client',
                'piecerecto_id' => 'client',
                'pieceverso_id' => 'client',
            ];
            $newFileIds = ['client' => []];
            foreach ($fileFields as $inputName => $targetModel) {
                if ($request->hasFile($inputName)) {
                    $file = $request->file($inputName);
                    $fileName = time() . '-' . Str::random(10) . '.' . $file->extension();
                    $file->move(public_path('images'), $fileName);

                    $f = Fichier::create([
                        'nom' => $fileName,
                        'source' => 'images/' . $fileName,
                    ]);

                    $newFileIds[$targetModel][$inputName] = $f->id;
                }
            }
            // Préparer les données de mise à jour
            $paysId = $paysCurrent ?? $validatedData['pays_id'] ?? $client->pays_id;
            $pays = Pays::find($paysId);
            $indicatif = $pays?->code ?? '';
            $client->update([
                    'nom' => $validatedData['nom'],
                    'prenoms' => $validatedData['prenoms'],
                    'email' => $validatedData['email'],
                    'login' => $validatedData['tel'],
                    'full_login' => $indicatif . $validatedData['tel'],
                    'type_piece' => $validatedData['type_piece'],
                    'numero_piece' => $validatedData['numero_piece'] ?? $client->numero_piece,
                    'date_delivrance' => $validatedData['date_delivrance'] ?? $client->date_delivrance,
                    'date_naissance' => $validatedData['date_naissance'] ?? $client->date_naissance,
                    'lieu_naissance' => $validatedData['lieu_naissance'] ?? $client->lieu_naissance,
                    'lieu_delivrance' => $validatedData['lieu_delivrance'] ?? $client->lieu_delivrance,
                    'pays_id' => $paysId,
                ] + $newFileIds['client']);

            // Vérifier si le client a des wallets, sinon les créer
            if (!Wallet::where('owner_type', 'client')
                ->where('owner_id', $client->id)
                ->exists()) {
                WalletService::createWalletsForOwner($client->id, 'client', $paysId);
            }

            // Mise à jour du rôle
            $role = Role::findByName(config('appconstants.role.client'), config('appconstants.guard.web', 'web'));
            $client->syncRoles([$role]);
            DB::commit();

            return redirect()->route('client.index')->with('success', __('modifsucces'));
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Client", "update", $e->getMessage());
            return redirect()->route('client.edit', $uuid)->with('error', __('erreurmaj'));
        }
    }

    /**
     * Toggle user status (soft delete/restore).
     */
    public function statut($uuid)
    {
        try {
            $client = Client::where('uuid', $uuid)->firstOrFail();

            if ($client->trashed()) {
                $client->restore();
                $message = __('restaurationsucces');
            } else {
                $client->delete();
                $message = __('suppressionsucces');
            }

            return redirect()->route('client.index')->with('success', $message);
        } catch (\Throwable $e) {
            log_error("Client", "statut", $e->getMessage());
            return redirect()->route('client.index')->with('error', __('erreurmaj'));
        }
    }

    public function validation(Request $request, $uuid, $action)
    {
        try {
            $motif = $request->input('motif');
            $client = Client::where('uuid', $uuid)->firstOrFail();

            if ($action === 'valider') {
                $result = ValidationService::validateStatut($client, null, false);
            } elseif ($action === 'rejeter') {
                $result = ValidationService::rejectStatut($client, $motif, null, false);
            } else {
                return redirect()->route('client.index')->with('error', trans('actionnonreconnue'));
            }

            if (!$result['success']) {
                return redirect()->route('client.index')->with('error', $result['message']);
            }

            return redirect()->route('client.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Client", "validation", $e->getMessage());
            return redirect()->route('client.index')->with('error', trans('Erreur'));
        }
    }

    public function kycValidation(Request $request, $uuid, $action)
    {
        try {
            $motif = $request->input('motif');
            $client = Client::where('uuid', $uuid)->firstOrFail();

            if ($action === 'valider') {
                $result = ValidationService::validateKyc($client);
            } elseif ($action === 'rejeter') {
                $result = ValidationService::rejectKyc($client, $motif);
            } else {
                return redirect()->route('client.show', $uuid)->with('error', trans('actionnonreconnue'));
            }

            if (!$result['success']) {
                return redirect()->route('client.show', $uuid)->with('error', $result['message']);
            }

            return redirect()->route('client.show', $uuid)->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Client", "kycValidation", $e->getMessage());
            return redirect()->route('client.show', $uuid)->with('error', trans('Erreur'));
        }
    }

    public function suspendre(Request $request, $uuid)
    {
        try {
            $motif = $request->input('motif');
            $client = Client::where('uuid', $uuid)->firstOrFail();

            $result = ValidationService::suspendUser($client, $motif);

            if (!$result['success']) {
                return redirect()->route('client.index')->with('error', $result['message']);
            }

            return redirect()->route('client.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Client", "suspendre", $e->getMessage());
            return redirect()->route('client.show', $uuid)->with('error', trans('Erreur'));
        }
    }

    public function bloquer(Request $request, $uuid)
    {
        try {
            $motif = $request->input('motif');
            $client = Client::where('uuid', $uuid)->firstOrFail();

            $result = ValidationService::blockUser($client, $motif);

            if (!$result['success']) {
                return redirect()->route('client.index')->with('error', $result['message']);
            }

            return redirect()->route('client.index')->with('success', $result['message']);
        } catch (\Throwable $e) {
            log_error("Client", "bloquer", $e->getMessage());
            return redirect()->route('client.show', $uuid)->with('error', trans('Erreur'));
        }
    }

    /**
     * Check if alias_smil is unique.
     */
    public function checkAliasSmil(Request $request)
    {
        $request->validate([
            'alias_smil' => 'required|string|max:255',
            'user_id' => 'nullable|integer',
        ]);

        $alias = $request->alias_smil;
        $userId = $request->user_id;

        $query =User::where('alias_smil', $alias);

        if ($userId) {
            $query->where('id', '!=', $userId);
        }

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? __('aliasuse') : null,
        ]);
    }

    /**
     * Format client for Vue component.
     */
    private function formatClientForVue(Client $client): array
    {
        // Récupérer les fichiers
        $photoprofile = $client->photoprofile_id ? Fichier::find($client->photoprofile_id) : null;
        $piecerecto = $client->piecerecto_id ? Fichier::find($client->piecerecto_id) : null;
        $pieceverso = $client->pieceverso_id ? Fichier::find($client->pieceverso_id) : null;
        return [
            'id' => $client->id,
            'uuid' => $client->uuid,
            'nom' => $client->nom,
            'prenoms' => $client->prenoms,
            'login' => $client->login,
            'full_login' => $client->full_login,
            'email' => $client->email,
            'pays_id' => $client->pays_id,
            'kyc_status' => $client->kyc_status,
            'code_owner' => $client->code_owner,
            'code_parrain' => $client->code_parrain,
            'alias_smil' => $client->alias_smil,
            'type_piece' => $client->type_piece,
            'numero_piece' => $client->numero_piece,
            'date_delivrance' => $client->date_delivrance,
            'date_naissance' => $client->date_naissance,
            'lieu_delivrance' => $client->lieu_delivrance,
            'lieu_naissance' => $client->lieu_naissance,
            'adresse' => $client->adresse,
            'statut' => $client->statut,
            'roles' => $client->getRoleNames()->toArray(),
            'current_role' => $client->getRoleNames()->first(),
            'photoprofile' => $photoprofile ? asset('images/' . $photoprofile->nom) : null,
            'piecerecto' => $piecerecto ? asset('images/' . $piecerecto->nom) : null,
            'pieceverso' => $pieceverso ? asset('images/' . $pieceverso->nom) : null,
        ];
    }
}
