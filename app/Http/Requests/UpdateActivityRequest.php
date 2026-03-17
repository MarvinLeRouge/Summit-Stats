<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Retourne les règles de validation applicables à la requête.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:randonnee,trail'],
            'environment' => ['sometimes', 'in:urbain,campagne,montagne'],
            'date' => ['sometimes', 'date'],
            'comment' => ['nullable', 'string'],
            'gpx_file' => ['sometimes', 'file', 'mimes:gpx,xml', 'max:20480'],
        ];
    }
}
