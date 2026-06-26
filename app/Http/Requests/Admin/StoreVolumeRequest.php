<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVolumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Volume::class);
    }

    public function rules(): array
    {
        return [
            'volume_number' => ['required', 'integer', 'min:1'],
            'type'          => ['required', Rule::in(['regular', 'digital', 'bind_up'])],
            'isbn'          => ['nullable', 'string', 'max:20'],
            'published_at'  => ['nullable', 'date'],
            'cover'         => ['nullable', 'image', 'max:2048'],
        ];
    }
}
