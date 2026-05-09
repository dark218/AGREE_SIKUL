<?php

namespace Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MoneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Business\Entities\Marchand;
use Modules\Wallet\Entities\Wallet;
use Modules\Parametrage\Entities\PaysDevise;
use Modules\Wallet\Entities\WalletMouvement;

class WalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:wallet-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:wallet-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:wallet-statut', ['only' => ['statut']]);
        $this->middleware('permission.check:wallet-show-by-owner', ['only' => ['showByOwner']]);
    }

    public function index(Request $request)
    {
        try {
            $paysCurrent = auth()->user()->pays_id;
            $owner_type = $request->input('owner_type');
            $statut = $request->input('statut');
            $pays_devise_id = $request->input('pays_devise_id');
            $search = $request->input('search');
            $paysDevises = PaysDevise::with(['pays', 'devise'])->get();
            $statuts = [
                ['value' => 'actif', 'label' => trans('statuts.actif')],
                ['value' => 'suspendu', 'label' => trans('statuts.suspendu')],
                ['value' => 'ferme', 'label' => trans('statuts.ferme')],
            ];

            $ownerTypes = [
                ['value' => 'client', 'label' => 'Client'],
                ['value' => 'marchand', 'label' => 'Marchand'],
                ['value' => 'agent', 'label' => 'Agent'],
                ['value' => 'plateforme', 'label' => 'Plateforme'],
            ];

            $query = Wallet::with(['paysDevise.devise', 'paysDevise.pays'])
                ->orderBy('created_at', 'DESC');

            if (!empty($owner_type)) {
                $query->where('owner_type', $owner_type);
            }

            if (!empty($statut)) {
                $query->where('statut', $statut);
            }

            if (!empty($pays_devise_id)) {
                $query->where('pays_devise_id', $pays_devise_id);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    // Recherche pour les users (client, agent, utilisateur)
                    $q->whereExists(function ($subQ) use ($search) {
                        $subQ->select(DB::raw(1))
                              ->from('users')
                              ->whereRaw('users.id = wallets.owner_id')
                              ->whereIn('wallets.owner_type', ['client', 'agent', 'utilisateur'])
                              ->where(function ($userQ) use ($search) {
                                  $userQ->where('users.nom', 'LIKE', "%{$search}%")
                                        ->orWhere('users.prenoms', 'LIKE', "%{$search}%")
                                        ->orWhere('users.email', 'LIKE', "%{$search}%")
                                        ->orWhere('users.login', 'LIKE', "%{$search}%");
                              });
                    })
                    // Recherche pour les marchands
                    ->orWhereExists(function ($subQ) use ($search) {
                        $subQ->select(DB::raw(1))
                              ->from('marchands')
                              ->whereRaw('marchands.id = wallets.owner_id')
                              ->where('wallets.owner_type', 'marchand')
                              ->where(function ($marchandQ) use ($search) {
                                  $marchandQ->where('marchands.raison_sociale', 'LIKE', "%{$search}%")
                                           ->orWhere('marchands.identifiant_fiscal', 'LIKE', "%{$search}%");
                              });
                    })
                    // Recherche dans les propriétaires des marchands
                    ->orWhereExists(function ($subQ) use ($search) {
                        $subQ->select(DB::raw(1))
                              ->from('marchands')
                              ->join('users', 'users.id', '=', 'marchands.proprietaire_id')
                              ->whereRaw('marchands.id = wallets.owner_id')
                              ->where('wallets.owner_type', 'marchand')
                              ->where(function ($userQ) use ($search) {
                                  $userQ->where('users.nom', 'LIKE', "%{$search}%")
                                        ->orWhere('users.prenoms', 'LIKE', "%{$search}%")
                                        ->orWhere('users.email', 'LIKE', "%{$search}%");
                              });
                    });
                });
            }

            if (!is_null($paysCurrent)) {
                $query->whereHas('paysDevise', function ($q) use ($paysCurrent) {
                    $q->where('pays_id', $paysCurrent);
                });
            }

            $wallets = $query->paginate(10)->withQueryString();


            $wallets->getCollection()->transform(function ($wallet) {
                $owner = null;

                // Charger l'owner selon le type
                if ($wallet->owner_type === Wallet::OWNER_TYPE_MARCHAND) {
                    $owner = Marchand::with('proprietaire')->find($wallet->owner_id);
                } else {
                    // Pour client, agent, utilisateur
                    $owner = User::find($wallet->owner_id);
                }

                $ownerData = [
                    'nom' => '',
                    'prenoms' => '',
                    'email' => '',
                    'login' => '',
                    'raison_sociale' => '',
                ];

                if ($owner) {
                    if ($wallet->owner_type === Wallet::OWNER_TYPE_MARCHAND) {
                        $ownerData = [
                            'nom' => $owner->proprietaire->nom ?? '',
                            'prenoms' =>  $owner->proprietaire->prenoms." (". $owner->raison_sociale .")"?? '',
                            'email' => $owner->proprietaire?->email ?? '',
                            'login' => $owner->proprietaire?->full_login ?? '',
                            'raison_sociale' => $owner->raison_sociale ?? '',
                        ];
                    } else {
                        // Pour client, agent, utilisateur
                        $ownerData = [
                            'nom' => $owner->nom ?? '',
                            'prenoms' => $owner->prenoms ?? '',
                            'email' => $owner->email ?? '',
                            'login' => $owner->full_login ?? '',
                            'raison_sociale' => '',
                        ];
                    }
                }

                return [
                    'id' => $wallet->id,
                    'owner_type' => $wallet->owner_type,
                    'owner_nom' => $ownerData['nom'],
                    'owner_prenoms' => $ownerData['prenoms'],
                    'owner_email' => $ownerData['email'],
                    'owner_login' => $ownerData['login'],
                    'owner_raison_sociale' => $ownerData['raison_sociale'],
                    'pays_devise' => $wallet->paysDevise?->pays?->libelle . ' - ' . $wallet->paysDevise?->devise?->code ?? '',
                    'solde' => $wallet->solde,
                    'solde_bloque' => $wallet->solde_bloque,
                    'statut' => $wallet->statut,
                    'created_at' => $wallet->created_at?->format('d/m/Y H:i'),
                ];
            });

            
            return Inertia::render('Wallet::Wallet/Index', [
                'wallets' => $wallets,
                'paysDevises' => $paysDevises,
                'statuts' => $statuts,
                'ownerTypes' => $ownerTypes,
                'filters' => [
                    'owner_type' => $owner_type,
                    'statut' => $statut,
                    'pays_devise_id' => $pays_devise_id,
                    'search' => $search,
                ],
                'paysCurrent' => $paysCurrent,
            ]);
        } catch (\Throwable $e) {
            log_error("Wallet", "index", $e->getMessage());
            return redirect()->route('home')->with('error', trans('Erreur'));
        }
    }

    public function show($id)
    {
        try {
            $wallet = Wallet::with(['owner', 'paysDevise.devise', 'paysDevise.pays', 'mouvements'])
                           ->findOrFail($id);

            return Inertia::render('Wallet::Wallet/Show', [
                'wallet' => $this->transformWallet($wallet),
                'statuts' => [
                    Wallet::STATUT_ACTIF => 'Actif',
                    Wallet::STATUT_SUSPENDU => 'Suspendu',
                    Wallet::STATUT_FERME => 'Fermé',
                ],
            ]);
        } catch (\Throwable $e) {
            log_error("Wallet", "show", $e->getMessage());
            return redirect()->route('wallet.index')->with('error', trans('Erreuraffichage'));
        }
    }

    public function edit($id)
    {
        try {
            $wallet = Wallet::with(['owner', 'paysDevise'])->findOrFail($id);

            $statuts = [
                ['value' => 'actif', 'label' => 'Actif'],
                ['value' => 'suspendu', 'label' => 'Suspendu'],
                ['value' => 'ferme', 'label' => 'Fermé'],
            ];

            return Inertia::render('Wallet::Wallet/Edit', [
                'wallet' => $this->transformWallet($wallet),
                'statuts' => $statuts,
            ]);
        } catch (\Throwable $e) {
            log_error("Wallet", "edit", $e->getMessage());
            return redirect()->route('wallet.index')->with('error', trans('Erreuraffichage'));
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $wallet = Wallet::findOrFail($id);

            $validatedData = $request->validate([
                'statut' => 'required|in:actif,suspendu,ferme',
            ]);

            $wallet->update([
                'statut' => $validatedData['statut'],
            ]);

            DB::commit();
            return redirect()->route('wallet.index')->with('success', trans('modifsucces'));
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Wallet", "update", $e->getMessage());
            return redirect()->route('wallet.edit', $id)->with('error', trans('erreurmaj') . ' : ' . $e->getMessage());
        }
    }

    public function statut($id)
    {
        try {
            DB::beginTransaction();

            $wallet = Wallet::withTrashed()->findOrFail($id);

            if ($wallet->trashed()) {
                $wallet->restore();
                $message = trans('restaurationsucces');
            } else {
                $wallet->delete();
                $message = trans('suppressionsucces');
            }

            DB::commit();
            return redirect()->route('wallet.index')->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error("Wallet", "statut", $e->getMessage());
            return redirect()->route('wallet.index')->with('error', trans('Erreur'));
        }
    }
    /**
     * Affiche le portefeuille d'un propriétaire spécifique
     */
    public function showByOwner(string $ownerType, int $ownerId)
    {
        try {
            // Vérifier que le type de propriétaire est valide
            if (!Wallet::peutAvoirWallet($ownerType)) {
                return redirect()->back()->with('error', 'Type de propriétaire non autorisé');
            }
            // Récupérer le wallet avec les relations nécessaires
            $wallet = Wallet::with([
                'paysDevise.pays',
                'paysDevise.devise',
                'mouvements' => function ($query) {
                    $query->latest()->take(50);
                },
                'mouvements.user',
                'mouvements.emplacement'
            ])
                ->where('owner_type', $ownerType)
                ->where('owner_id', $ownerId)
                ->firstOrFail();

            // Transformer les données pour la vue
            $data = $this->transformWallet($wallet);

            // Charger l'owner manuellement selon le type
            $owner = null;
            if ($wallet->owner_type === Wallet::OWNER_TYPE_MARCHAND) {
                $owner = Marchand::with('proprietaire')->find($wallet->owner_id);
            } else {
                $owner = User::find($wallet->owner_id);
            }

            // Ajouter des informations supplémentaires spécifiques au propriétaire
            $data['owner_info'] = $this->getOwnerInfo($ownerType, $owner);

            return Inertia::render('Wallet::Wallet/ShowByOwner', $data);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            log_error("Wallet", "showByOwner", "Portefeuille non trouvé pour $ownerType ID $ownerId");
            return redirect()->back()->with('error', 'Portefeuille non trouvé');
        } catch (\Throwable $e) {
            log_error("Wallet", "showByOwner", $e->getMessage());
            return redirect()->route('home')->with('error', 'Une erreur est survenue lors de la récupération du portefeuille');
        }
    }

    /**
     * Récupère les informations spécifiques au type de propriétaire
     */
    private function getOwnerInfo(string $ownerType, $owner): array
    {

        if (!$owner) {
            return ['type' => $ownerType, 'error' => 'Propriétaire non trouvé'];
        }

        $baseInfo = [
            'id' => $owner->id,
            'nom_complet' => trim(($owner->prenoms ?? '') . ' ' . ($owner->nom ?? '')),
            'email' => $owner->email ?? null,
            'telephone' => $owner->login ?? $owner->telephone ?? null,
            'created_at' => $owner->created_at?->format('d/m/Y H:i'),
        ];

        switch ($ownerType) {
            case Wallet::OWNER_TYPE_MARCHAND:
                // Pour les marchands, récupérer les infos depuis le propriétaire (user)
                $proprietaire = $owner->proprietaire;
                $userInfo = $proprietaire ? [
                    'id' => $proprietaire->id,
                    'nom_complet' => trim(($proprietaire->prenoms ?? '') . ' ' . ($proprietaire->nom ?? '')),
                    'email' => $proprietaire->email ?? null,
                    'telephone' => $proprietaire->login ?? $proprietaire->telephone ?? null,
                    'created_at' => $proprietaire->created_at?->format('d/m/Y H:i'),
                ] : $baseInfo;

                return array_merge($userInfo, [
                    'type' => 'marchand',
                    'raison_sociale' => $owner->raison_sociale ?? null,
                    'identifiant_fiscal' => $owner->identifiant_fiscal ?? null,
                    'statut' => $owner->statut ?? null,
                    'kyc_status' => $owner->kyc_status ?? null,
                    'marchand_id' => $owner->id,
                ]);

            case Wallet::OWNER_TYPE_CLIENT:
                return array_merge($baseInfo, [
                    'type' => 'client',
                    'compte_verifie' => $owner->email_verified_at !== null,
                    'date_inscription' => $owner->created_at?->format('d/m/Y'),
                ]);

            case Wallet::OWNER_TYPE_AGENT:
                return array_merge($baseInfo, [
                    'type' => 'agent',
                    'matricule' => $owner->matricule ?? null,
                    'agence' => $owner->agence?->nom ?? null,
                    'statut' => $owner->statut ?? null,
                ]);

            case Wallet::OWNER_TYPE_PLATEFORME:
                return array_merge($baseInfo, [
                    'type' => 'plateforme',
                    'nom' => $owner->name ?? 'Plateforme',
                ]);

            default:
                return array_merge($baseInfo, ['type' => $ownerType]);
        }
    }
    private function transformWallet(Wallet $wallet): array
    {
        // Charger les relations nécessaires en une seule requête
        $wallet->loadMissing([
            'paysDevise.pays',
            'paysDevise.devise',
            'mouvements' => function ($query) {
                $query->latest()->take(50); // Limiter le nombre de mouvements chargés
            },
            'mouvements.user',
            'mouvements.emplacement'
        ]);

        // Charger l'owner manuellement selon le type
        $owner = null;
        if ($wallet->owner_type === Wallet::OWNER_TYPE_MARCHAND) {
            $owner = Marchand::with('proprietaire')->find($wallet->owner_id);
        } else {
            $owner = User::find($wallet->owner_id);
        }

        // Récupérer la devise une seule fois
        $deviseCode = $wallet->paysDevise->devise->code ?? 'XOF';

        // Transformer les mouvements
        $mouvements = $wallet->mouvements->map(function ($mouvement) use ($deviseCode) {
            return [
                'id' => $mouvement->id,
                'reference' => $mouvement->reference,
                'type_mouvement' => $mouvement->type_mouvement,
                'type_mouvement_label' => $this->getTypeMouvementLabel($mouvement->type_mouvement),
                'source_type' => $mouvement->source_type,
                'source_reference' => $mouvement->source_reference ?? null,
                'montant' => MoneyService::toDisplay($mouvement->montant_cents, $deviseCode),
                'montant_cents' => $mouvement->montant_cents,
                'solde_avant' => MoneyService::toDisplay($mouvement->solde_avant_cents, $deviseCode),
                'solde_apres' => MoneyService::toDisplay($mouvement->solde_apres_cents, $deviseCode),
                'solde_avant_cents' => $mouvement->solde_avant_cents,
                'solde_apres_cents' => $mouvement->solde_apres_cents,
                'emplacement' => $mouvement->emplacement?->only(['id', 'nom', 'adresse']),
                'utilisateur' => $mouvement->user?->only(['id', 'nom', 'prenoms', 'email']),
                'description' => $mouvement->description,
                'created_at' => $mouvement->created_at?->format('d/m/Y H:i'),
                'meta' => $mouvement->meta_json ?? [],
            ];
        });

        // Calculer les statistiques
        $stats = [
            'total_entrees' => $wallet->mouvements->whereIn('type_mouvement', [
                WalletMouvement::TYPE_CREDIT,
                WalletMouvement::TYPE_DEBLOCAGE
            ])->sum('montant_cents'),
            'total_sorties' => $wallet->mouvements->whereIn('type_mouvement', [
                WalletMouvement::TYPE_DEBIT,
                WalletMouvement::TYPE_BLOCAGE,
                WalletMouvement::TYPE_COMMISSION
            ])->sum('montant_cents'),
            'nombre_operations' => $wallet->mouvements->count(),
            'derniere_operation' => $wallet->mouvements->first()?->created_at?->format('d/m/Y H:i'),
        ];

        return [
            // Informations de base
            'id' => $wallet->id,
            'reference' => $wallet->reference,
            'statut' => $wallet->statut,
            'statut_label' => trans('statuts.' . $wallet->statut),
            'created_at' => $wallet->created_at?->format('d/m/Y H:i'),
            'updated_at' => $wallet->updated_at?->format('d/m/Y H:i'),

            // Soldes
            'solde' => $wallet->solde,
            'solde_cents' => $wallet->solde_cents,
            'solde_bloque' => $wallet->solde_bloque,
            'solde_bloque_cents' => $wallet->solde_bloque_cents,
            'solde_commission' => $wallet->solde_commission,
            'solde_commission_cents' => $wallet->solde_commission_cents,
            'solde_attente' => $wallet->solde_attente,
            'solde_attente_cents' => $wallet->solde_attente_cents,

            // Propriétaire
            'owner' => $owner ? [
                'id' => $owner->id,
                'type' => $wallet->owner_type,
                'nom' => $owner->nom ?? '',
                'prenoms' => $owner->prenoms ?? '',
                'email' => $owner->email ?? '',
                'login' => $owner->login ?? '',
                'telephone' => $owner->telephone ?? null,
            ] : null,

            // Devise
            'pays_devise' => [
                'id' => $wallet->pays_devise_id,
                'pays' => $wallet->paysDevise->pays?->libelle ?? '',
                'pays_id' => $wallet->paysDevise->pays_id ?? null,
                'devise' => $wallet->paysDevise->devise?->code ?? '',
                'devise_id' => $wallet->paysDevise->devise_id ?? null,
                'symbole' => $wallet->paysDevise->devise?->symbole ?? '',
            ],

            // Mouvements et statistiques
            'mouvements' => $mouvements,
            'statistiques' => $stats,

            // Métadonnées
            'meta' => $wallet->meta_json ?? [],
        ];
    }

    /**
     * Retourne le libellé du type de mouvement
     */
    private function getTypeMouvementLabel(string $type): string
    {
        $types = [
            WalletMouvement::TYPE_CREDIT => 'Crédit',
            WalletMouvement::TYPE_DEBIT => 'Débit',
            WalletMouvement::TYPE_BLOCAGE => 'Blocage de fonds',
            WalletMouvement::TYPE_DEBLOCAGE => 'Déblocage de fonds',
            WalletMouvement::TYPE_COMMISSION => 'Commission',
            WalletMouvement::TYPE_REMBOURSEMENT => 'Remboursement',
            WalletMouvement::TYPE_AJUSTEMENT => 'Ajustement',
        ];

        return $types[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
}
