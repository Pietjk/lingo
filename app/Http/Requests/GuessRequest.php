<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuessRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('guess'))) {
            $this->merge(['guess' => strtolower(trim($this->input('guess')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'guess' => ['required', 'string', 'regex:/^[a-z]{5}$/'],
        ];
    }
}
