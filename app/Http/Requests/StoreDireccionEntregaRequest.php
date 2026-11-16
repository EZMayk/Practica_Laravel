<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDireccionEntregaRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $jsonNumber = static function (string $attribute, mixed $value, Closure $fail): void {
            if (is_numeric($value) && ! is_int($value) && ! is_float($value)) {
                $fail("El campo {$attribute} debe ser un número JSON.");
            }
        };

        return [
            'etiqueta' => ['required', 'string', 'max:40'],
            'direccion' => ['required', 'string', 'max:200'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'observaciones' => ['nullable', 'string', 'max:200'],
            'referencia' => ['nullable', 'string', 'max:200'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', $jsonNumber],
            'lng' => ['nullable', 'numeric', 'between:-180,180', $jsonNumber],
        ];
    }
}
