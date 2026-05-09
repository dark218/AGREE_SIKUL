<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\DB;

class PhoneLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'login' => 'required|string',
            'pays_id' => 'required|integer|exists:pays,id',
        ];

        // Ajouter la validation de la longueur du téléphone selon le pays
        if ($this->pays_id) {
            $pays = DB::table('pays')->find($this->pays_id);
            if ($pays && $pays->phone_length) {
                $rules['login'] .= '|size:' . $pays->phone_length;
            }
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            'login.required' => 'Le numéro de téléphone est obligatoire',
            'login.string' => 'Le numéro de téléphone doit être une chaîne de caractères',
            'pays_id.required' => 'Le pays est obligatoire',
            'pays_id.integer' => 'L\'identifiant du pays doit être un entier',
            'pays_id.exists' => 'Le pays sélectionné n\'existe pas',
        ];

        // Ajouter le message pour la taille dynamique
        if ($this->pays_id) {
            $pays = DB::table('pays')->find($this->pays_id);
            if ($pays && $pays->phone_length) {
                $messages['login.size'] = "Le numéro de téléphone doit contenir exactement {$pays->phone_length} caractères pour ce pays";
            }
        }

        return $messages;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->pays_id) {
                $pays = DB::table('pays')->find($this->pays_id);
                if ($pays && $pays->phone_length) {
                    if (strlen($this->login) != $pays->phone_length) {
                        $validator->errors()->add('phone', "Le numéro de téléphone doit contenir exactement {$pays->phone_length} caractères pour ce pays");
                    }
                }
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
