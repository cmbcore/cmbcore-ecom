<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Blog\Models\BlogPost;

class StoreBlogPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'featured_image' => ['nullable', 'string', 'max:2048'],
            'featured_image_file' => ['nullable', 'file', 'image', 'max:5120'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::in([
                BlogPost::STATUS_DRAFT,
                BlogPost::STATUS_PUBLISHED,
                BlogPost::STATUS_ARCHIVED,
            ])],
            'published_at' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => __('admin.blogs.validation.title_required'),
        ];
    }
}
