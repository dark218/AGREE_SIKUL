<?php

namespace Modules\Parametrage\Http\Controllers\Concerns;

use App\Models\User;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Entities\Commune;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\Departement;
use Modules\Parametrage\Entities\Devises;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Institution;
use Modules\Parametrage\Entities\Niveau;
use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\Quartier;
use Modules\Parametrage\Entities\Region;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\TypeCours;
use Modules\Parametrage\Entities\TypeEnseignement;
use Modules\Parametrage\Entities\TypeEtablissement;

/**
 * Lookups centralisés pour les formulaires Parametrage.
 * Évite la duplication code dans chaque controller create/edit.
 */
trait ProvidesParametrageLookups
{
    /**
     * Listes de localisation (pays, régions, départements, communes, quartiers).
     * Chaque liste est triée et minimaliste pour rester légère.
     */
    protected function localisationLookups(): array
    {
        return [
            'paysList' => Pays::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray(),
            'regions' => Region::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray(),
            'departements' => Departement::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray(),
            'communes' => Commune::orderBy('libelle')->get(['id', 'libelle', 'code'])->toArray(),
            'quartiers' => Quartier::with(['commune.departement.region.pays'])
                ->orderBy('libelle')
                ->get(['id', 'libelle', 'code', 'commune_id'])
                ->map(function ($q) {
                    return [
                        'id' => $q->id,
                        'libelle' => $q->libelle,
                        'code' => $q->code ?? null,
                        // Hiérarchie pré-chargée pour cascade côté Vue
                        'commune_id' => $q->commune_id,
                        'departement_id' => $q->commune?->departement_id,
                        'region_id' => $q->commune?->departement?->region_id,
                        'pays_id' => $q->commune?->departement?->region?->pays_id,
                    ];
                })
                ->toArray(),
        ];
    }

    /**
     * Devises monétaires disponibles (pour devise_comptabilite_id).
     */
    protected function devisesLookup(): array
    {
        return Devises::orderBy('libelle')->get(['id', 'libelle', 'code', 'symbol'])->toArray();
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
                'institutions' => Institution::where('statut', 'actif')
                    ->orderBy('nom')
                    ->get(['id', 'nom', 'code'])
                    ->toArray(),
                'responsables' => $this->directeursLookup(),
            ]
        );
    }

    /**
     * Lookups pour Ecole.
     */
    protected function ecoleLookups(): array
    {
        $campuses = Campus::where('statut', 'actif')
            ->with('institution')
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

        return array_merge(
            $this->localisationLookups(),
            [
                'campuses' => $campuses,
                'institutions' => Institution::orderBy('nom')->get(['id', 'nom', 'code'])->toArray(),
                'typeEtablissements' => TypeEtablissement::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
                'typeEnseignements' => TypeEnseignement::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
                'typeCours' => TypeCours::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
                'sections' => Section::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
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
        return [
            'ecoles' => Ecole::where('statut', 'actif')
                ->orderBy('nom')
                ->get(['id', 'nom', 'code', 'campus_id'])
                ->toArray(),
            'campuses' => Campus::orderBy('nom')->get(['id', 'nom'])->toArray(),
            'niveaux' => Niveau::orderBy('ordre')->get(['id', 'libelle', 'code', 'ecole_id'])->toArray(),
            'sections' => Section::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
            'cycles' => CycleEnseignement::orderBy('libelle')->get(['id', 'libelle'])->toArray(),
            'enseignants' => User::orderBy('nom')->get(['id', 'nom', 'prenoms'])->toArray(),
            'anneesScolaires' => AnneeScolaire::orderBy('libelle', 'desc')->get(['id', 'libelle'])->toArray(),
        ];
    }

    /**
     * Liste des utilisateurs éligibles comme directeur/responsable.
     */
    protected function directeursLookup(): array
    {
        return User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['administrateur', 'directeur', 'super_admin']);
            })
            ->orderBy('nom')
            ->get(['id', 'nom', 'email'])
            ->toArray();
    }
}
