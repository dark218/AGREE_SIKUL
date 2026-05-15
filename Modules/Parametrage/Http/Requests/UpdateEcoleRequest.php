<?php

namespace Modules\Parametrage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEcoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ecoleId = $this->route('ecole')?->id ?? $this->route('ecole');

        return [
            'campus_id' => 'required|exists:campuses,id',
            'institution_id' => 'nullable|exists:institutions,id',
            'code' => ['required', 'string', 'max:100', Rule::unique('ecoles', 'code')->ignore($ecoleId)],
            'nom' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:50',
            'devise_slogan' => 'nullable|string|max:255',
            'devise_comptabilite_id' => 'nullable|exists:devises,id',
            'type_etablissement_id' => 'nullable|exists:type_etablissement,id',
            'type_enseignement_id' => 'nullable|exists:type_enseignement,id',
            'type_cours_id' => 'nullable|exists:type_cours,id',
            'section_id' => 'nullable|exists:sections,id',
            'directeur_id' => 'nullable|exists:users,id',
            'date_creation' => 'nullable|date',
            'numero_agrement' => 'nullable|string|max:100',
            'ministere_tutelle' => 'nullable|string|max:255',
            'logo_id' => 'nullable|exists:fichier,id',
            'capacite_totale' => 'nullable|integer|min:0',
            'capacite_maximale' => 'nullable|integer|min:0',
            'statut' => 'nullable|in:actif,non_actif,suspendu',
            'adresse_siege' => 'nullable|string',
            'code_postal' => 'nullable|string|max:20',
            'boite_postale' => 'nullable|string|max:100',
            'ville' => 'nullable|string|max:100',
            'quartier' => 'nullable|string|max:100',
            'commune' => 'nullable|string|max:100',
            'departement' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'quartier_id' => 'nullable|exists:quartiers,id',
            'commune_id' => 'nullable|exists:communes,id',
            'departement_id' => 'nullable|exists:departements,id',
            'region_id' => 'nullable|exists:regions,id',
            'pays_id' => 'nullable|exists:pays,id',
            'telephone_principal' => 'nullable|string|max:20',
            'telephone_2' => 'nullable|string|max:20',
            'telephone_3' => 'nullable|string|max:20',
            'whatsapp_1' => 'nullable|string|max:20',
            'whatsapp_2' => 'nullable|string|max:20',
            'fax' => 'nullable|string|max:20',
            'email_principal' => 'nullable|email|max:255',
            'email_1' => 'nullable|email|max:255',
            'site_web' => 'nullable|url|max:255',
            'facebook' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'vision' => 'nullable|string',
            'mission' => 'nullable|string',
            'dirigeants' => 'nullable|array',
            'dirigeants.*.nom' => 'nullable|string|max:255',
            'dirigeants.*.prenom' => 'nullable|string|max:255',
            'dirigeants.*.nom_restituer' => 'nullable|string|max:255',
            'dirigeants.*.fonction' => 'nullable|string|max:255',
            'dirigeants.*.ordre' => 'nullable|integer|min:0',
        ];
    }
}
