<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Core\Module\ModuleManager;
use App\Core\Plugin\HookManager;
use App\Core\Plugin\PluginManager;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Modules\Blog\Models\BlogPost;
use Modules\Category\Models\Category;
use Modules\Page\Models\Page;
use Modules\Product\Models\Product;

class DashboardController extends Controller
{
    public function __invoke(
        ModuleManager $moduleManager,
        PluginManager $pluginManager,
        HookManager $hookManager,
    ): JsonResponse {
        $payload = [
            'overview' => [
                'title' => __('admin.dashboard.title'),
                'description' => __('admin.dashboard.description'),
                'updated_at' => now()->toIso8601String(),
            ],
            'cards' => [
                $this->buildMetricCard(
                    key: 'blog_posts',
                    label: __('admin.dashboard.cards.blog_posts.label'),
                    value: $this->countModel($moduleManager, 'blog', BlogPost::class),
                    icon: 'blog',
                    tone: 'amber',
                    route: '/admin/blog/posts',
                    meta: __('admin.dashboard.cards.blog_posts.meta', [
                        'count' => $this->countQuery($moduleManager, 'blog', BlogPost::class, static fn (Builder $query): Builder => $query->published()),
                    ]),
                ),
                $this->buildMetricCard(
                    key: 'pages',
                    label: __('admin.dashboard.cards.pages.label'),
                    value: $this->countModel($moduleManager, 'page', Page::class),
                    icon: 'page',
                    tone: 'cyan',
                    route: '/admin/pages',
                    meta: __('admin.dashboard.cards.pages.meta', [
                        'count' => $this->countQuery($moduleManager, 'page', Page::class, static fn (Builder $query): Builder => $query->published()),
                    ]),
                ),
                $this->buildMetricCard(
                    key: 'products',
                    label: __('admin.dashboard.cards.products.label'),
                    value: $this->countModel($moduleManager, 'product', Product::class),
                    icon: 'product',
                    tone: 'emerald',
                    route: '/admin/products',
                    meta: __('admin.dashboard.cards.products.meta', [
                        'count' => $this->countQuery($moduleManager, 'product', Product::class, static fn (Builder $query): Builder => $query->active()),
                    ]),
                ),
                $this->buildMetricCard(
                    key: 'categories',
                    label: __('admin.dashboard.cards.categories.label'),
                    value: $this->countModel($moduleManager, 'category', Category::class),
                    icon: 'category',
                    tone: 'violet',
                    route: '/admin/categories',
                    meta: __('admin.dashboard.cards.categories.meta', [
                        'count' => $this->countQuery($moduleManager, 'category', Category::class, static fn (Builder $query): Builder => $query->active()),
                    ]),
                ),
            ],
            'widgets' => [
                [
                    'key' => 'content_health',
                    'zone' => 'primary',
                    'type' => 'highlights',
                    'title' => __('admin.dashboard.widgets.content_health.title'),
                    'description' => __('admin.dashboard.widgets.content_health.description'),
                    'items' => [
                        [
                            'label' => __('admin.dashboard.widgets.content_health.items.posts'),
                            'value' => $this->countQuery($moduleManager, 'blog', BlogPost::class, static fn (Builder $query): Builder => $query->published()),
                            'icon' => 'blog',
                            'tone' => 'amber',
                        ],
                        [
                            'label' => __('admin.dashboard.widgets.content_health.items.pages'),
                            'value' => $this->countQuery($moduleManager, 'page', Page::class, static fn (Builder $query): Builder => $query->published()),
                            'icon' => 'page',
                            'tone' => 'cyan',
                        ],
                        [
                            'label' => __('admin.dashboard.widgets.content_health.items.products'),
                            'value' => $this->countQuery($moduleManager, 'product', Product::class, static fn (Builder $query): Builder => $query->active()),
                            'icon' => 'product',
                            'tone' => 'emerald',
                        ],
                        [
                            'label' => __('admin.dashboard.widgets.content_health.items.categories'),
                            'value' => $this->countQuery($moduleManager, 'category', Category::class, static fn (Builder $query): Builder => $query->active()),
                            'icon' => 'category',
                            'tone' => 'violet',
                        ],
                    ],
                ],
                [
                    'key' => 'runtime_overview',
                    'zone' => 'secondary',
                    'type' => 'list',
                    'title' => __('admin.dashboard.widgets.runtime_overview.title'),
                    'description' => __('admin.dashboard.widgets.runtime_overview.description'),
                    'items' => [
                        [
                            'label' => __('admin.dashboard.widgets.runtime_overview.items.modules'),
                            'value' => $moduleManager->getEnabled()->count(),
                            'meta' => __('admin.dashboard.widgets.runtime_overview.items.modules_meta', [
                                'count' => $moduleManager->getAll()->count(),
                            ]),
                        ],
                        [
                            'label' => __('admin.dashboard.widgets.runtime_overview.items.plugins'),
                            'value' => $pluginManager->getActive()->count(),
                            'meta' => __('admin.dashboard.widgets.runtime_overview.items.plugins_meta', [
                                'count' => $pluginManager->getAll()->count(),
                            ]),
                        ],
                        [
                            'label' => __('admin.dashboard.widgets.runtime_overview.items.admin_route'),
                            'value' => '/admin',
                            'meta' => __('admin.dashboard.widgets.runtime_overview.items.admin_route_meta'),
                        ],
                        [
                            'label' => __('admin.dashboard.widgets.runtime_overview.items.api_base'),
                            'value' => '/api/admin',
                            'meta' => __('admin.dashboard.widgets.runtime_overview.items.api_base_meta'),
                        ],
                    ],
                ],
                [
                    'key' => 'plugin_mock_zone',
                    'zone' => 'extensions',
                    'type' => 'placeholder',
                    'title' => __('admin.dashboard.widgets.plugin_mock_zone.title'),
                    'description' => __('admin.dashboard.widgets.plugin_mock_zone.description'),
                    'message' => __('admin.dashboard.widgets.plugin_mock_zone.message'),
                    'badges' => [
                        __('admin.dashboard.widgets.plugin_mock_zone.badges.schema'),
                        __('admin.dashboard.widgets.plugin_mock_zone.badges.hooks'),
                        __('admin.dashboard.widgets.plugin_mock_zone.badges.future_ready'),
                    ],
                ],
            ],
        ];

        $payload = $hookManager->applyFilter('admin.dashboard', $payload);

        return response()->json([
            'success' => true,
            'data' => $payload,
            'message' => __('admin.dashboard.messages.loaded'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMetricCard(
        string $key,
        string $label,
        int $value,
        string $icon,
        string $tone,
        string $route,
        string $meta,
    ): array {
        return compact('key', 'label', 'value', 'icon', 'tone', 'route', 'meta');
    }

    private function countModel(ModuleManager $moduleManager, string $moduleAlias, string $modelClass): int
    {
        return $this->countQuery($moduleManager, $moduleAlias, $modelClass, null);
    }

    private function countQuery(ModuleManager $moduleManager, string $moduleAlias, string $modelClass, ?callable $scope): int
    {
        if (! $moduleManager->isEnabled($moduleAlias) || ! class_exists($modelClass)) {
            return 0;
        }

        /** @var Model $model */
        $model = new $modelClass();

        if (! Schema::hasTable($model->getTable())) {
            return 0;
        }

        /** @var Builder<Model> $query */
        $query = $modelClass::query();

        if (is_callable($scope)) {
            $query = $scope($query);
        }

        return (int) $query->count();
    }
}
