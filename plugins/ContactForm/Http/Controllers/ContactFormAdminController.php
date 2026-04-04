<?php

declare(strict_types=1);

namespace Plugins\ContactForm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Plugins\ContactForm\Models\ContactForm;

class ContactFormAdminController extends Controller
{
    public function index(): JsonResponse
    {
        $forms = ContactForm::query()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'slug', 'description', 'is_active', 'created_at',
                   \Illuminate\Support\Facades\DB::raw('JSON_LENGTH(fields) as field_count')])
            ->map(function (ContactForm $form): array {
                return [
                    'id'          => $form->id,
                    'name'        => $form->name,
                    'slug'        => $form->slug,
                    'description' => $form->description,
                    'is_active'   => $form->is_active,
                    'field_count' => $form->fields ? count($form->fields) : 0,
                    'submission_count' => $form->submissions()->count(),
                    'created_at'  => $form->created_at?->toISOString(),
                ];
            });

        return response()->json(['data' => $forms]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'slug'            => ['nullable', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'fields'          => ['nullable', 'array'],
            'fields.*.id'     => ['required', 'string'],
            'fields.*.type'   => ['required', 'string', 'in:text,email,phone,number,textarea,select,radio,checkbox'],
            'fields.*.label'  => ['required', 'string', 'max:255'],
            'fields.*.name'   => ['required', 'string', 'max:100'],
            'fields.*.required' => ['nullable', 'boolean'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.width'  => ['nullable', 'string', 'in:full,half'],
            'success_message' => ['nullable', 'string', 'max:500'],
            'settings'        => ['nullable', 'array'],
            'is_active'       => ['nullable', 'boolean'],
        ]);

        $slug = $this->uniqueSlug($validated['slug'] ?? $validated['name']);

        $form = ContactForm::create([
            'name'            => $validated['name'],
            'slug'            => $slug,
            'description'     => $validated['description'] ?? null,
            'fields'          => $validated['fields'] ?? [],
            'success_message' => $validated['success_message'] ?? 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.',
            'settings'        => $validated['settings'] ?? null,
            'is_active'       => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $form, 'message' => 'Tạo form thành công.'], 201);
    }

    public function show(ContactForm $contactForm): JsonResponse
    {
        return response()->json(['data' => $contactForm]);
    }

    public function update(Request $request, ContactForm $contactForm): JsonResponse
    {
        $validated = $request->validate([
            'name'            => ['sometimes', 'required', 'string', 'max:255'],
            'slug'            => ['nullable', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'fields'          => ['nullable', 'array'],
            'fields.*.id'     => ['required', 'string'],
            'fields.*.type'   => ['required', 'string', 'in:text,email,phone,number,textarea,select,radio,checkbox'],
            'fields.*.label'  => ['required', 'string', 'max:255'],
            'fields.*.name'   => ['required', 'string', 'max:100'],
            'fields.*.required' => ['nullable', 'boolean'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.width'  => ['nullable', 'string', 'in:full,half'],
            'success_message' => ['nullable', 'string', 'max:500'],
            'settings'        => ['nullable', 'array'],
            'is_active'       => ['nullable', 'boolean'],
        ]);

        if (isset($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug($validated['slug'], $contactForm->id);
        } elseif (isset($validated['name']) && $validated['name'] !== $contactForm->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $contactForm->id);
        }

        $contactForm->update($validated);

        return response()->json(['data' => $contactForm->fresh(), 'message' => 'Cập nhật thành công.']);
    }

    public function destroy(ContactForm $contactForm): JsonResponse
    {
        $contactForm->delete();

        return response()->json(['message' => 'Đã xóa form.']);
    }

    public function submissions(Request $request, ContactForm $contactForm): JsonResponse
    {
        $submissions = $contactForm->submissions()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $submissions->map(fn ($s) => [
                'id'         => $s->id,
                'data'       => $s->data,
                'page_url'   => $s->page_url,
                'ip_address' => $s->ip_address,
                'is_read'    => $s->is_read,
                'created_at' => $s->created_at?->toISOString(),
            ]),
            'meta' => [
                'current_page' => $submissions->currentPage(),
                'last_page'    => $submissions->lastPage(),
                'total'        => $submissions->total(),
            ],
        ]);
    }

    public function markRead(int $submissionId): JsonResponse
    {
        \Plugins\ContactForm\Models\ContactSubmission::whereKey($submissionId)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Đã đánh dấu đã đọc.']);
    }

    private function uniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = Str::slug($base);
        $original = $slug;
        $i = 1;

        while (
            ContactForm::query()
                ->where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->whereKeyNot($excludeId))
                ->exists()
        ) {
            $slug = $original . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
