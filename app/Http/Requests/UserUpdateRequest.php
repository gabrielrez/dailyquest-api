<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->username == $this->user()->username) {
            return [
                'full_name' => 'sometimes|string|max:45'
            ];
        }

        return [
            'full_name' => 'sometimes|string|max:45',
            'username'  => 'sometimes|string|unique:users,username|max:45',
        ];
    }
}
