<?php

namespace Modules\Parametrage\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Entities\Commune;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\Departement;
use Modules\Parametrage\Entities\Devises;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Institution;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\Quartier;
use Modules\Parametrage\Entities\Region;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\TypeCours;
use Modules\Parametrage\Entities\TypeEnseignement;
use Modules\Parametrage\Entities\TypeEtablissement;

/**
 * Lookups centralisés pour les formulaires Parametrage.
 *
 * Toutes les listes sont cachées 1 heure (sauf devises 10 min) — les listes
 * référentielles (pays, régions, etc.) changent rarement donc pas besoin
 * de les requêter à chaque chargement de formulaire.
 *
 * Pour invalider manuellement : Cache::forget('parametrage.lookups.localisation')
 * ou utiliser le tag (si driver le supporte) `Cache::tags('parametrage_lookups')->flush()`.
 */
trait ProvidesParametrageLookups
{
    private const CACHE_TTL_LONG = 3600;   // 1h pour referentiels stables
    private const CACHE_TTL_SHORT = 600;   // 10min pour listes modifiables

    /**
     * Listes de localisation (pays, régions, départements, communes, quartiers).
     * Mises en cache 1h car les référentiels géographiques changent rarement.
     */
    protected function localisationLookups(): array
    {
        return Cache::remember('parametrage.lookups.localisation', self::CACHE_TTL_LONG, function () {
            return [
                'paysList' => Pays::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray(),
                'regions' => Region::orderBy('libelle')->get(['id', 'libelle', 'code', 'pays_id'])->toArray(),
                'departements' => Departement::orderBy('libelle')->get(['id', 'libelle', 'code', 'region_id', 'pays_id'])->toArray(),
                'communes' => Commune::orderBy('libelle')->get(['id', 'libelle', 'code', 'departement_id'])->toArray(),
                'quartiers' => Quartier::with(['commune:id,departement_id', 'commune.departement:id,region_id', 'commune.departement.region:id,pays_id'])
                    ->orderBy('libelle')
                    ->get(['id', 'libelle', 'code', 'commune_id'])
                    ->map(function ($q) {
                        return [
                            'id' => $q->id,
                            'libelle' => $q->libelle,
                            'code' => $q->code ?? null,
                            'commune_id' => $q->commune_id,
                            'departement_id' => $q->commune?->departement_id,
                            'region_id' => $q->commune?->departement?->region_id,
                            'pays_id' => $q->commune?->departement?->region?->pays_id,
                        ];
                    })
                    ->toArray(),
            ];
        });
    }

    /**
     * Devises monétaires disponibles (pour devise_comptabilite_id).
     */
    protected function devisesLookup(): array
    {
        return Cache::remember('parametrage.lookups.devises', self::CACHE_TTL_LONG, function () {
            return Devises::orderBy('libelle')->get(['id', 'libelle', 'code', 'symbol'])->toArray();
        });
    }

    /**
     * Lookups standards pour Institution.
     */
    protected function institutionLookups(): array
    {
        return array_merge(
            $this->localisationLookups(),
            [
                'devises' => $this->devisesLookup(),
                'directeurs' => $this->directeursLookup(),
            ]
        );
    }

    /**
     * Lookups pour Campus.
     */
    protected function campusLookups(): array
    {
        return array_merge(
            $this->localisationLookups(),
            [
                'institutions' => Cache::remember('parametrage.lookups.institutions', self::CACHE_TTL_SHORT, function () {
                    return Institution::where('statut', 'actif')
                        ->orderBy('nom')
                        ->get(['id', 'nom', 'code'])
                        ->toArray();
                }),
                'responsables' => $this->directeursLookup(),
            ]
        );
    }

    /**
     * Lookups pour Ecole.
     */
    protected function ecoleLookups(): array
    {
        $campuses = Cache::remember('parametrage.lookups.campuses', self::CACHE_TTL_SHORT, function () {
            return Campus::where('statut', 'actif')
                ->with('institution:id,nom')
                ->orderBy('nom')
                ->get()
                ->map(function ($campus) {
                    return [
                        'id' => $campus->id,
                        'nom' => $campus->nom . ($campus->institution ? ' (' . $campus->institution->nom . ')' : ''),
                        'institution_id' => $campus->institution_id,
                    ];
                })
                ->toArray();
        });

        $types = Cache::remember('parametrage.lookups.ecole_types', self::CACHE_TTL_LONG, function () {
            return [
                'institutions' => Institution::orderBy('nom')->get(['id', 'nom', 'code'])->toArray(),
                'typeEtablissements' => TypeEtablissement::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
                'typeEnseignements' => TypeEnseignement::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
                'typeCours' => TypeCours::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
                'sections' => Section::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
            ];
        });

        return array_merge(
            $this->localisationLookups(),
            ['campuses' => $campuses],
            $types,
            [
                'directeurs' => $this->directeursLookup(),
                'devises' => $this->devisesLookup(),
            ]
        );
    }

    /**
     * Lookups pour Classe.
     */
    protected function classeLookups(): array
    {
        return Cache::remember('parametrage.lookups.classe_lists', self::CACHE_TTL_SHORT, function () {
            return [
                'ecoles' => Ecole::orderBy('nom')
                    ->get(['id', 'nom', 'code', 'campus_id'])
                    ->toArray(),
                // On expose `libelle` (= nom) en plus de `nom` car les selects
                // du formulaire Classe utilisent optionLabel="libelle".
                'campuses' => Campus::orderBy('nom')->get(['id', 'nom'])
                    ->map(fn ($c) => ['id' => $c->id, 'nom' => $c->nom, 'libelle' => $c->nom])
                    ->toArray(),
                // Utilise NiveauEtude (paramétrage) au lieu de Niveau (académique)
                // pour que la liste des niveaux paramétrés remonte bien.
                // ⚠️ La validation (Store/UpdateClasseRequest) et la clé étrangère
                // classes.niveau_id doivent donc pointer sur la table niveaux_etudes.
                'niveaux' => NiveauEtude::orderBy('libelle')
                    ->get(['id', 'libelle', 'code', 'ecole_id', 'section_id', 'cycle_id'])
                    ->toArray(),
                'sections' => Section::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
                'cycles' => CycleEnseignement::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
                'enseignants' => User::orderBy('nom')->get(['id', 'nom', 'prenoms'])->toArray(),
            ];
        });
    }

    /**
     * Liste des utilisateurs éligibles comme directeur/responsable.
     * Cache 10 min (peut changer si nouveau user créé).
     */
    protected function directeursLookup(): array
    {
        return Cache::remember('parametrage.lookups.directeurs', self::CACHE_TTL_SHORT, function () {
            return User::whereHas('roles', function ($q) {
                    $q->whereIn('name', ['administrateur', 'directeur', 'super_admin']);
                })
                ->orderBy('nom')
                ->get(['id', 'nom', 'email'])
                ->toArray();
        });
    }
}
