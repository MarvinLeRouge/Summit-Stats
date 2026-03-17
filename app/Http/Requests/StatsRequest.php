<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatsRequest extends FormRequest
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
            'metric' => ['required', 'in:avg_speed_kmh,avg_speed_moving_kmh,avg_ascent_speed_mh,avg_flat_speed_kmh,avg_descent_speed_kmh,avg_descent_rate_mh,elevation_gain,distance_km'],
            'type' => ['nullable', 'in:randonnee,trail'],
            'environment' => ['nullable', 'in:urbain,campagne,montagne'],
            'activity_id' => ['nullable', 'integer', 'exists:activities,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'slope_class' => ['nullable', 'in:lt5,5_15,15_25,25_35,gt35'],
            'slope_min' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'slope_max' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
