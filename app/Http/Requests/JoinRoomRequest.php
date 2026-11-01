<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JoinRoomRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper(trim($this->input('code')))]);
        }

        if (is_string($this->input('nickname'))) {
            $this->merge(['nickname' => trim($this->input('nickname'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/^[A-Z]{4}$/', Rule::exists('rooms', 'code')],
            'nickname' => ['required', 'string', 'min:1', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.exists' => 'No room found with that code.',
            'code.regex' => 'A room code is 4 letters.',
        ];
    }
}
