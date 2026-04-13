<?php

namespace App\Http\Requests\Settings\Articles;

use App\Models\Settings\ArticleModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $article = $this->route('article');
        $id = $article instanceof ArticleModel ? $article->getKey() : $article;

        return [
            'reference' => ['sometimes', 'string', 'max:100', Rule::unique('articles', 'reference')->ignore($id)],
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'vat_id' => ['sometimes', 'integer', Rule::exists('vats', 'id')],
            'photo' => ['nullable', 'image'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
