<?php

namespace Modules\Parametrage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'institution_id' => 'required|exists:institutions,id',
            'code' => 'required|string|max:100|unique:campuses,code',
            'nom' => 'required|string|max:255',
            'adresse' => 'nullable|string',
            // Legacy strings
            'ville' => 'nullable|string|max:100',
            'code_postal' => 'nullable|string|max:20',
            'boite_postale' => 'nullable|string|max:100',
            'quartier' => 'nullable|string|max:100',
            'commune' => 'nullable|string|max:100',
            'departement' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            // FK
            'quartier_id' => 'nullable|exists:quartiers,id',
            'commune_id' => 'nullable|exists:communes,id',
            'departement_id' => 'nullable|exists:departements,id',
            'region_id' => 'nullable|exists:regions,id',
            'pays_id' => 'nullable|exists:pays,id',
            // Géo
            'longitude' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            // Contact
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'responsable_id' => 'nullable|exists:users,id',
            'statut' => 'nullable|in:actif,non_actif,suspendu',
            'statut_disponibilite' => 'nullable|string|max:50',
        ];
    }
}
