<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ActivityLog\Models\AdminActivityLog;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogService
{
    private ?bool $tableAvailable = null;

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, AdminActivityLog>
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $action = trim((string) ($filters['action'] ?? ''));
        $perPage = max(1, (int) ($filters['per_page'] ?? config('activity_log.per_page', 20)));

        return AdminActivityLog::query()
            ->with('actor')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%' . $search . '%';
                $query->where(function (Builder $innerQuery) use ($like): void {
                    $innerQuery
                        ->where('description', 'like', $like)
                        ->orWhere('request_path', 'like', $like)
                        ->orWhere('action', 'like', $like)
                        ->orWhere('subject_id', 'like', $like)
                        ->orWhereHas('actor', fn (Builder $actorQuery) => $actorQuery
                            ->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like));
                });
            })
            ->when($action !== '', fn (Builder $query) => $query->where('action', $action))
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @return array<int, string>
     */
    public function actions(): array
    {
        if (! $this->isReady()) {
            return [];
        }

        return AdminActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->map(static fn (string $action): string => $action)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function log(User $actor, array $context = []): ?AdminActivityLog
    {
        if (! $this->isReady() || ! $actor->canAccessAdminPanel()) {
            return null;
        }

        return AdminActivityLog::query()->create([
            'admin_user_id' => $actor->id,
            'action' => (string) ($context['action'] ?? 'admin.action'),
            'description' => $this->nullableString($context['description'] ?? null),
            'subject_type' => $this->nullableString($context['subject_type'] ?? null),
            'subject_id' => $this->nullableString($context['subject_id'] ?? null),
            'request_method' => strtoupper((string) ($context['request_method'] ?? 'CLI')),
            'request_path' => (string) ($context['request_path'] ?? 'manual'),
            'route_uri' => $this->nullableString($context['route_uri'] ?? null),
            'ip_address' => $this->nullableString($context['ip_address'] ?? null),
            'user_agent' => $this->nullableString($context['user_agent'] ?? null),
            'payload' => $this->sanitizePayload($context['payload'] ?? null),
            'meta' => $this->sanitizePayload($context['meta'] ?? null),
        ]);
    }

    public function remember(Request $request, array $context = []): void
    {
        $current = $request->attributes->get('admin_activity_context', []);
        $request->attributes->set('admin_activity_context', array_replace(
            is_array($current) ? $current : [],
            $context,
        ));
    }

    public function logRequest(Request $request, Response $response): ?AdminActivityLog
    {
        if (! $this->isReady()) {
            return null;
        }

        $context = $request->attributes->get('admin_activity_context', []);
        $actor = $context['actor'] ?? $request->user();

        if (! $actor instanceof User || ! $actor->canAccessAdminPanel()) {
            return null;
        }

        $route = $request->route();
        $action = (string) ($context['action'] ?? $this->inferAction($request));
        $subjectType = $this->nullableString($context['subject_type'] ?? $this->inferSubjectType($request));
        $subjectId = $this->nullableString($context['subject_id'] ?? $this->inferSubjectId($request));

        return $this->log($actor, [
            'action' => $action,
            'description' => $context['description'] ?? $this->describe($request, $action, $subjectType, $subjectId),
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'request_method' => $request->method(),
            'request_path' => $request->path(),
            'route_uri' => $route?->uri(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $context['payload'] ?? $request->all(),
            'meta' => array_filter([
                'status_code' => $response->getStatusCode(),
                'message' => $this->responseMessage($response),
            ], static fn (mixed $value): bool => $value !== null),
        ]);
    }

    private function isReady(): bool
    {
        if ($this->tableAvailable !== null) {
            return $this->tableAvailable;
        }

        try {
            return $this->tableAvailable = Schema::hasTable('admin_activity_logs');
        } catch (\Throwable) {
            return $this->tableAvailable = false;
        }
    }

    private function inferAction(Request $request): string
    {
        $path = trim($request->path(), '/');

        if (str_ends_with($path, '/auth/login')) {
            return 'auth.login';
        }

        if (str_ends_with($path, '/auth/logout')) {
            return 'auth.logout';
        }

        $subject = $this->inferSubjectType($request) ?? 'resource';
        $verb = match (strtoupper($request->method())) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => strtolower($request->method()),
        };

        return $subject . '.' . $verb;
    }

    private function inferSubjectType(Request $request): ?string
    {
        $segments = array_values(array_filter(explode('/', trim($request->path(), '/'))));
        $adminIndex = array_search('admin', $segments, true);

        if ($adminIndex === false || ! isset($segments[$adminIndex + 1])) {
            return null;
        }

        $resourceSegments = array_values(array_filter(
            array_slice($segments, $adminIndex + 1),
            static fn (string $segment): bool => ! preg_match('/^\{.+\}$/', $segment) && ! is_numeric($segment),
        ));

        if ($resourceSegments === []) {
            return null;
        }

        if (($resourceSegments[0] ?? null) === 'auth' && isset($resourceSegments[1])) {
            return 'auth';
        }

        $primary = Str::singular($resourceSegments[0]);
        $secondary = $resourceSegments[1] ?? null;

        if ($secondary !== null && ! in_array($secondary, ['search', 'analytics'], true)) {
            return Str::snake($primary . '_' . Str::singular($secondary));
        }

        return Str::snake($primary);
    }

    private function inferSubjectId(Request $request): ?string
    {
        $route = $request->route();

        foreach (['id', 'alias', 'orderNumber'] as $parameter) {
            $value = $route?->parameter($parameter) ?? $request->input($parameter);

            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        if (in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH'], true) && $request->filled('id')) {
            return (string) $request->input('id');
        }

        return null;
    }

    private function describe(Request $request, string $action, ?string $subjectType, ?string $subjectId): string
    {
        $label = $subjectType !== null ? Str::headline(str_replace('_', ' ', $subjectType)) : 'Admin';
        $verb = match (strtoupper($request->method())) {
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            default => strtolower($request->method()),
        };

        if ($action === 'auth.login') {
            return 'Admin signed in.';
        }

        if ($action === 'auth.logout') {
            return 'Admin signed out.';
        }

        return $subjectId !== null
            ? sprintf('%s %s #%s.', $label, $verb, $subjectId)
            : sprintf('%s %s.', $label, $verb);
    }

    private function responseMessage(Response $response): ?string
    {
        if (! $response instanceof JsonResponse) {
            return null;
        }

        $payload = $response->getData(true);

        return isset($payload['message']) && is_string($payload['message'])
            ? Str::limit($payload['message'], 255)
            : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function sanitizePayload(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $key => $item) {
                $normalizedKey = is_string($key) ? strtolower($key) : $key;

                if (is_string($normalizedKey) && in_array($normalizedKey, [
                    'password',
                    'password_confirmation',
                    'current_password',
                    'token',
                    '_token',
                ], true)) {
                    $sanitized[$key] = '[redacted]';

                    continue;
                }

                $sanitized[$key] = $this->sanitizePayload($item);
            }

            return $sanitized;
        }

        if (is_string($value)) {
            return Str::limit($value, 2000);
        }

        if ($value instanceof UploadedFile) {
            return [
                'name' => $value->getClientOriginalName(),
                'size' => $value->getSize(),
                'mime' => $value->getClientMimeType(),
            ];
        }

        if (is_object($value)) {
            return method_exists($value, '__toString')
                ? Str::limit((string) $value, 2000)
                : ['object' => $value::class];
        }

        return $value;
    }
}
