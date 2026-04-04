<?php

declare(strict_types=1);

namespace Modules\Page\Http\Requests;

use App\Core\Theme\ThemeManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Page\Models\Page;

class StorePageRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $contentBlocks = $this->input('content_blocks');

        if (is_string($contentBlocks) && trim($contentBlocks) !== '') {
            $decoded = json_decode($contentBlocks, true);

            if (is_array($decoded)) {
                $this->merge(['content_blocks' => $decoded]);
            }
        }

        // Decode puck_data JSON string from FormData
        $puckData = $this->input('puck_data');

        if (is_string($puckData) && trim($puckData) !== '') {
            $decoded = json_decode($puckData, true);

            if (is_array($decoded)) {
                $this->merge(['puck_data' => $decoded]);
            }
        }
    }

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
            'template' => ['required', Rule::in($this->availableTemplates())],
            'featured_image' => ['nullable', 'string', 'max:2048'],
            'featured_image_file' => ['nullable', 'file', 'image', 'max:5120'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'content_blocks' => ['nullable', 'array'],
            'content_blocks.*.type' => ['required_with:content_blocks', 'string', 'max:100'],
            'content_blocks.*.props' => ['nullable', 'array'],
            'puck_data' => ['nullable', 'array'],
            'puck_data.content' => ['nullable', 'array'],
            'puck_data.root' => ['nullable', 'array'],
            'status' => ['required', Rule::in([
                Page::STATUS_DRAFT,
                Page::STATUS_PUBLISHED,
                Page::STATUS_ARCHIVED,
            ])],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'uploads' => ['nullable', 'array'],
            'uploads.*' => ['file', 'image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => __('admin.pages.validation.title_required'),
            'template.in' => __('admin.pages.validation.template_invalid'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function availableTemplates(): array
    {
        $themeTemplates = app(ThemeManager::class)->getActive()->getTemplates()['page'] ?? [];
        $templates = collect($themeTemplates)
            ->map(static fn (mixed $template): ?string => is_array($template) ? trim((string) ($template['name'] ?? '')) : null)
            ->filter()
            ->values()
            ->all();

        // 'builder' is always available (Puck page builder)
        $templates = array_unique(array_merge(
            $templates !== [] ? $templates : [(string) config('page.default_template', 'default')],
            ['builder'],
        ));

        return $templates;
    }
}
