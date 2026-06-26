<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('series'));
    }

    public function rules(): array
    {
        return [
            'title_romaji'   => ['required', 'string', 'max:255'],
            'title_english'  => ['nullable', 'string', 'max:255'],
            'title_japanese' => ['nullable', 'string', 'max:500'],
            'synopsis'       => ['nullable', 'string'],
            'status'         => ['required', Rule::in(['publishing', 'finished', 'on_hiatus', 'discontinued', 'not_yet_published'])],
            'type'           => ['required', Rule::in(['manga', 'manhwa', 'manhua', 'novel', 'one_shot', 'doujinshi'])],
            'cover'          => ['nullable', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'published_from' => ['nullable', 'date'],
            'published_to'   => ['nullable', 'date', 'after_or_equal:published_from'],
            'total_volumes'  => ['nullable', 'integer', 'min:1'],
            'score'          => ['nullable', 'numeric', 'min:0', 'max:10'],
            'rank'           => ['nullable', 'integer', 'min:1'],
        ];
    }
}
