<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\StorefrontSlugResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Blog\Http\Controllers\Frontend\BlogController;
use Modules\Page\Http\Controllers\Frontend\PageController;

class StorefrontContentFallbackController extends Controller
{
    public function __construct(
        private readonly StorefrontSlugResolver $slugResolver,
        private readonly BlogController $blogController,
        private readonly PageController $pageController,
    ) {
    }

    public function __invoke(Request $request): View
    {
        $resolved = $this->slugResolver->resolve($request->path());

        if ($resolved === null) {
            abort(404);
        }

        return match ($resolved['type']) {
            'page' => $this->pageController->show($resolved['slug']),
            'blog' => $this->blogController->show($resolved['slug']),
            default => abort(404),
        };
    }
}
