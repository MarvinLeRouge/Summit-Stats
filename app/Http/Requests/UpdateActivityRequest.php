<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
