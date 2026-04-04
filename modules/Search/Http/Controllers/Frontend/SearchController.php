<?php

declare(strict_types=1);

namespace Modules\Search\Http\Controllers\Frontend;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Product\Http\Controllers\Frontend\ProductCatalogController;

class SearchController extends ProductCatalogController
{
    protected function listingView(): string
    {
        return 'search.index';
    }

    public function index(Request $request): View
    {
        return theme_manager()->view($this->listingView(), $this->listingPayload($request));
    }
}
