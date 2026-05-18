<?php

namespace Modules\Parametrage\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $institutionId = $this->route('institution')?->id ?? $this->route('institution');

        return [
            'code' => ['nullable', 'string', 'max:100', Rule::unique('institutions', 'code')->ignore($institutionId)],
            'nom' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:50',
            'type' => 'nullable|in:primaire,secondaire,superieur,formation,autre',
            'statut_juridique' => 'nullable|string|max:100',
            'date_creation' => 'nullable|date',
            'numero_autorisation' => 'nullable|string|max:100',
            'numero_agrement_2' => 'nullable|string|max:100',
            'numero_agrement_3' => 'nullable|string|max:100',
            'numero_agrement_4' => 'nullable|string|max:100',
            'ministere_tutelle_1' => 'nullable|string|max:255',
            'ministere_tutelle_2' => 'nullable|string|max:255',
            'ministere_tutelle_3' => 'nullable|string|max:255',
            'ministere_tutelle_4' => 'nullable|string|max:255',
            'promoteur' => 'nullable|string|max:255',
            'gerant' => 'nullable|string|max:255',
            'directeur_general_id' => 'nullable|exists:users,id',
            'adresse_siege' => 'nullable|string',
            'code_postal' => 'nullable|string|max:20',
            'boite_postale' => 'nullable|string|max:100',
            'quartier' => 'nullable|string|max:100',
            'commune' => 'nullable|string|max:100',
            'ville' => 'nullable|string|max:100',
            'departement' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'quartier_id' => 'nullable|exists:quartiers,id',
            'commune_id' => 'nullable|exists:communes,id',
            'departement_id' => 'nullable|exists:departements,id',
            'region_id' => 'nullable|exists:regions,id',
            'pays_id' => 'nullable|exists:pays,id',
            'devise_principale' => 'nullable|string|max:3',
            'devise_slogan' => 'nullable|string|max:255',
            'devise_comptabilite_id' => 'nullable|exists:devises,id',
            'logo_id' => 'nullable|exists:fichier,id',
            'email_principal' => 'nullable|email|max:255',
            'telephone_principal' => 'nullable|string|max:20',
            'site_web' => 'nullable|url|max:255',
            'telephone_1' => 'nullable|string|max:20',
            'telephone_2' => 'nullable|string|max:20',
            'telephone_3' => 'nullable|string|max:20',
            'whatsapp_1' => 'nullable|string|max:20',
            'whatsapp_2' => 'nullable|string|max:20',
            'fax' => 'nullable|string|max:20',
            'email_1' => 'nullable|email|max:255',
            'email_2' => 'nullable|email|max:255',
            'facebook' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
            'fuseau_horaire' => 'nullable|string|max:50',
            'langue_principale' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'vision' => 'nullable|string',
            'mission' => 'nullable|string',
            'statut' => 'nullable|in:actif,non_actif,suspendu,bloque',
        ];
    }
}
