<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStorageSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        $requiredIfS3 = Rule::requiredIf($this->input('driver') === 's3');

        return [
            'driver' => ['required', 'in:local,s3'],
            'access_key_id' => [$requiredIfS3, 'nullable', 'string', 'max:255'],
            'secret_access_key' => [$requiredIfS3, 'nullable', 'string', 'max:255'],
            'bucket' => [$requiredIfS3, 'nullable', 'string', 'max:255'],
            'endpoint' => [$requiredIfS3, 'nullable', 'url', 'max:255'],
            'region' => ['nullable', 'string', 'max:100'],
            'url' => [$requiredIfS3, 'nullable', 'url', 'max:255'],
        ];
    }
}
