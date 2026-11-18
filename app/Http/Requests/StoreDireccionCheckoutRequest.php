<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDireccionCheckoutRequest extends FormRequest
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
            if (! is_int($value) && ! is_float($value)) {
                $fail("El campo {$attribute} debe ser un número JSON.");
            }
        };

        return [
            'direccion' => ['required', 'array:etiqueta,direccion,telefono,referencia'],
            'direccion.etiqueta' => ['required', 'string', 'max:40'],
            'direccion.direccion' => ['required', 'string', 'max:200'],
            'direccion.telefono' => ['nullable', 'string', 'max:20'],
            'direccion.referencia' => ['nullable', 'string', 'max:200'],
            'ubicacion' => ['required', 'array:latitude,longitude'],
            'ubicacion.latitude' => ['required', 'numeric', 'between:-90,90', $jsonNumber],
            'ubicacion.longitude' => ['required', 'numeric', 'between:-180,180', $jsonNumber],
        ];
    }
}
