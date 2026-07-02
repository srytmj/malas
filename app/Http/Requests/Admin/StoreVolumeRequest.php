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

    public function messages(): array
    {
        return [
            'volume_number.unique' => 'Nomor volume ini sudah ada di series ini.',
        ];
    }

    public function rules(): array
    {
        $seriesId = $this->route('series')?->id;

        return [
            'volume_number' => [
                'required', 'integer', 'min:1',
                Rule::unique('volumes', 'volume_number')->where('series_id', $seriesId),
            ],
            'type'          => ['required', Rule::in(['regular', 'digital', 'bind_up'])],
            'isbn'          => ['nullable', 'string', 'max:20'],
            'published_at'  => ['nullable', 'date'],
            'cover'         => ['nullable', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }
}
