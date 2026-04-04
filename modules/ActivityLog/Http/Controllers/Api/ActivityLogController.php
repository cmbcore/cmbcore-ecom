<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ActivityLog\Models\AdminActivityLog;
use Modules\ActivityLog\Services\ActivityLogService;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $logs = $this->activityLogService->paginate($request->all());

        return response()->json([
            'success' => true,
            'data' => collect($logs->items())->map(fn (AdminActivityLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'request_method' => $log->request_method,
                'request_path' => $log->request_path,
                'ip_address' => $log->ip_address,
                'payload' => $log->payload,
                'meta' => $log->meta,
                'created_at' => $log->created_at?->toISOString(),
                'actor' => $log->actor ? [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                    'email' => $log->actor->email,
                ] : null,
            ])->all(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'actions' => $this->activityLogService->actions(),
            ],
        ]);
    }
}
