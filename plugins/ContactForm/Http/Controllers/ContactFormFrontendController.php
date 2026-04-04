<?php

declare(strict_types=1);

namespace Plugins\ContactForm\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Plugins\ContactForm\Models\ContactForm;
use Plugins\ContactForm\Models\ContactSubmission;

class ContactFormFrontendController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $formId = (int) $request->input('_form_id');
        $form   = ContactForm::find($formId);

        if (! $form || ! $form->is_active) {
            return redirect()->back()->withErrors(['form' => 'Form không tồn tại hoặc đã bị vô hiệu hóa.']);
        }

        // Build validation rules dynamically from form field config
        $rules    = [];
        $messages = [];

        foreach ($form->resolvedFields() as $field) {
            $fieldName = (string) ($field['name'] ?? '');

            if ($fieldName === '') {
                continue;
            }

            $fieldRules = ['nullable'];

            if (! empty($field['required'])) {
                $fieldRules = ['required'];
                $messages["{$fieldName}.required"] = ($field['label'] ?? $fieldName) . ' là bắt buộc.';
            }

            switch ($field['type'] ?? 'text') {
                case 'email':
                    $fieldRules[] = 'email';
                    $fieldRules[] = 'max:255';
                    break;
                case 'phone':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:50';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:5000';
                    break;
                case 'select':
                case 'radio':
                    $options = array_column((array) ($field['options'] ?? []), 'value');
                    if ($options !== []) {
                        $fieldRules[] = 'in:' . implode(',', $options);
                    }
                    break;
                default:
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:500';
            }

            $rules[$fieldName] = $fieldRules;
        }

        try {
            $validated = $request->validate($rules, $messages);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        // Remove internal fields from data
        $data = collect($validated)
            ->except(['_form_id', '_token'])
            ->all();

        ContactSubmission::create([
            'form_id'    => $form->id,
            'data'       => $data,
            'page_url'   => $request->headers->get('referer'),
            'ip_address' => $request->ip(),
        ]);

        // Optional: Email notification from form settings
        $settings = $form->settings ?? [];
        if (! empty($settings['notify_email'])) {
            $this->sendNotification($form, $data, $settings['notify_email']);
        }

        $successKey = 'contact_success_' . $form->id;

        return redirect()->back()->with($successKey, $form->success_message);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function sendNotification(ContactForm $form, array $data, string $email): void
    {
        try {
            $rows = collect($data)
                ->map(fn ($value, $key) => "<tr><td><strong>{$key}</strong></td><td>{$value}</td></tr>")
                ->implode('');

            \Illuminate\Support\Facades\Mail::html(
                "<p>Có submission mới từ form <strong>{$form->name}</strong>:</p><table border='1' cellpadding='8'>{$rows}</table>",
                function ($message) use ($email, $form): void {
                    $message->to($email)->subject("[Form] Submission mới: {$form->name}");
                },
            );
        } catch (\Throwable) {
            // Silent — don't block user experience on mail failure
        }
    }
}
