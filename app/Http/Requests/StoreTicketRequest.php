<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'subject'   => ['required', 'string', 'max:200'],
            'type'      => ['required', 'in:catalog_request,title_revision,other'],
            'message'   => ['required', 'string', 'max:5000'],
            'series_id' => ['nullable', 'uuid', 'exists:series,id'],
        ];
    }
}
