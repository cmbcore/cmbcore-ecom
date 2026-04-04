<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests\Concerns;

trait InteractsWithProductPayload
{
    protected function prepareProductPayload(): void
    {
        $this->merge([
            'category_id' => $this->nullableIntegerInput('category_id'),
            'is_featured' => filter_var($this->input('is_featured', false), FILTER_VALIDATE_BOOL),
            'skus' => $this->decodeJsonField('skus'),
            'media' => $this->decodeJsonField('media'),
        ]);
    }

    private function decodeJsonField(string $key): mixed
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true, 512);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private function nullableIntegerInput(string $key): ?int
    {
        $value = $this->input($key);

        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
