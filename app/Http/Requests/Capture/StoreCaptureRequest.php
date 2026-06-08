<?php

namespace App\Http\Requests\Capture;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaptureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'input' => ['required', 'string', 'min:1', 'max:4000'],
        ];
    }
}
