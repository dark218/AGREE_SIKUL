<?php

namespace Modules\Parametrage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'nullable|string|max:100',
            'libelle' => 'required|string|max:255',
            'libelle_affichage' => 'nullable|string|max:255',
            'batiment' => 'nullable|string|max:100',
            // Structure académique
            'ecole_id' => 'nullable|exists:ecoles,id',
            'campus_id' => 'nullable|exists:campuses,id',
            'section_id' => 'nullable|exists:sections,id',
            // Le formulaire liste les NiveauEtude (table niveaux_etudes), pas Niveau.
            'niveau_id' => 'nullable|exists:niveaux_etudes,id',
            'cycle_id' => 'nullable|exists:cycles_enseignement,id',
            'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
            // Enseignant et capacité
            'enseignant_titulaire_id' => 'nullable|exists:users,id',
            'capacite_max' => 'nullable|integer|min:1',
            'capacite_actuelle' => 'nullable|integer|min:0',
            'statut' => 'nullable|in:actif,non_actif,suspendu',
            // Legacy
            'nom' => 'nullable|string|max:255',
            'code_salle' => 'nullable|string|max:100',
            'salle' => 'nullable|string|max:100',
        ];
    }
}
