<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TestStorageConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'access_key_id' => ['required', 'string', 'max:255'],
            'secret_access_key' => ['required', 'string', 'max:255'],
            'bucket' => ['required', 'string', 'max:255'],
            'endpoint' => ['required', 'url', 'max:255'],
            'region' => ['nullable', 'string', 'max:100'],
            'url' => ['required', 'url', 'max:255'],
        ];
    }
}
