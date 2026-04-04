<?php

declare(strict_types=1);

namespace Modules\SeoTools\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\SeoTools\Services\SeoToolsService;

class SitemapController extends Controller
{
    public function __construct(
        private readonly SeoToolsService $seoToolsService,
    ) {
    }

    public function __invoke(): Response
    {
        $urls = $this->seoToolsService->sitemapUrls();
        $xml = view('seo-tools::sitemap', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
