<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:randonnee,trail'],
            'environment' => ['required', 'in:urbain,campagne,montagne'],
            'date'        => ['required', 'date'],
            'comment'     => ['nullable', 'string'],
            'gpx_file'    => ['required', 'file', 'mimes:gpx,xml', 'max:20480'],
        ];
    }
}