<?php

namespace App\Http\Requests;

use App\Http\Enums\CollectionStatusEnum;
use App\Models\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CollectionCreateRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'max:45',
                Rule::unique('collections')->where(function ($query) {
                    return $query->where('owner_id', $this->user()->id);
                }),
            ],
            'description'      => 'nullable|string|max:100',
            'cyclic'           => 'sometimes|boolean',
            'deadline'         => 'sometimes|date|after:today',
            'is_collaborative' => 'sometimes|boolean',
            'status' => ['sometimes', new Enum(CollectionStatusEnum::class)],
        ];
    }

    public function messages(): array 
    {
        return [
            'name.unique' => 'You already have a collection with that name.',
        ];
    }
}
