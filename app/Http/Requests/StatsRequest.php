<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metric'      => ['required', 'in:avg_ascent_speed_mh,avg_speed_kmh,elevation_gain,distance_km'],
            'type'        => ['nullable', 'in:randonnee,trail'],
            'environment' => ['nullable', 'in:urbain,campagne,montagne'],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date', 'after_or_equal:date_from'],
            'slope_class' => ['nullable', 'in:lt5,5_15,15_25,25_35,gt35'],
            'slope_min'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'slope_max'   => ['nullable', 'numeric', 'min:0', 'max:100', 'gte:slope_min'],
        ];
    }
}