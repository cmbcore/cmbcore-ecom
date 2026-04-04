<?php

declare(strict_types=1);

namespace Modules\Notifications\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Modules\Notifications\Models\NotificationTemplate;

class NotificationTemplateService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        if (! $this->tableAvailable()) {
            return [];
        }

        $this->ensureDefaults();

        return NotificationTemplate::query()
            ->orderBy('type')
            ->get()
            ->map(fn (NotificationTemplate $template): array => [
                'id' => $template->id,
                'type' => $template->type,
                'subject' => $template->subject,
                'content' => $template->content,
                'is_active' => (bool) $template->is_active,
            ])
            ->all();
    }

    public function ensureDefaults(): void
    {
        if (! $this->tableAvailable()) {
            return;
        }

        foreach ($this->defaultTemplates() as $template) {
            NotificationTemplate::query()->updateOrCreate(
                ['type' => $template['type']],
                [
                    'subject' => $template['subject'],
                    'content' => $template['content'],
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function save(array $payload): NotificationTemplate
    {
        if (! $this->tableAvailable()) {
            throw new \RuntimeException('notification_templates table is not available.');
        }

        $this->ensureDefaults();

        /** @var NotificationTemplate $template */
        $template = NotificationTemplate::query()->updateOrCreate(
            ['type' => $payload['type']],
            [
                'subject' => trim((string) $payload['subject']),
                'content' => trim((string) $payload['content']),
                'is_active' => (bool) ($payload['is_active'] ?? true),
            ],
        );

        return $template->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function send(string $type, ?string $to, array $data): void
    {
        if (! $to || ! $this->tableAvailable()) {
            return;
        }

        /** @var NotificationTemplate|null $template */
        $template = NotificationTemplate::query()->where('type', $type)->where('is_active', true)->first();

        if (! $template instanceof NotificationTemplate) {
            return;
        }

        $subject = $this->interpolate($template->subject, $data);
        $content = nl2br(e($this->interpolate($template->content, $data)));

        try {
            Mail::html((string) $content, function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable) {
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function interpolate(string $value, array $data): string
    {
        foreach ($data as $key => $item) {
            $value = str_replace('{' . $key . '}', (string) $item, $value);
        }

        return $value;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function defaultTemplates(): array
    {
        return [
            [
                'type' => 'customer_registered',
                'subject' => 'Chao mung {customer_name} den voi shop',
                'content' => 'Tài khoản cua ban da duoc tao thanh cong.',
            ],
            [
                'type' => 'order_created_customer',
                'subject' => 'Xác nhận đơn hàng {order_number}',
                'content' => 'Đơn hàng {order_number} cua ban da duoc tiếp nhận voi tổng tiền {grand_total}.',
            ],
            [
                'type' => 'order_created_admin',
                'subject' => 'Đơn hàng moi {order_number}',
                'content' => 'Khach {customer_name} vua tao don {order_number} voi tổng tiền {grand_total}.',
            ],
            [
                'type' => 'order_confirmed_customer',
                'subject' => 'Đơn hàng {order_number} da duoc xác nhận',
                'content' => 'Đơn hàng {order_number} cua ban da duoc xác nhận.',
            ],
            [
                'type' => 'order_cancelled_customer',
                'subject' => 'Đơn hàng {order_number} da huy',
                'content' => 'Đơn hàng {order_number} cua ban da duoc huy.',
            ],
            [
                'type' => 'order_delivered_customer',
                'subject' => 'Đơn hàng {order_number} da giao thành công',
                'content' => 'Đơn hàng {order_number} cua ban da giao thành công.',
            ],
        ];
    }

    private function tableAvailable(): bool
    {
        try {
            return Schema::hasTable('notification_templates');
        } catch (\Throwable) {
            return false;
        }
    }
}
