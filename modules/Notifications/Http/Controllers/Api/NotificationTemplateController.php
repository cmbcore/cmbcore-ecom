<?php

declare(strict_types=1);

namespace Modules\Notifications\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notifications\Services\NotificationTemplateService;

class NotificationTemplateController extends Controller
{
    public function __construct(
        private readonly NotificationTemplateService $notificationTemplateService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->notificationTemplateService->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'type' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string'],
            'content' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->notificationTemplateService->save($payload),
            'message' => 'Da luu email template.',
        ]);
    }
}
