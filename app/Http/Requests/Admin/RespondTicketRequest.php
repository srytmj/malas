<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RespondTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'admin_response' => ['required', 'string', 'max:5000'],
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ];
    }
}
