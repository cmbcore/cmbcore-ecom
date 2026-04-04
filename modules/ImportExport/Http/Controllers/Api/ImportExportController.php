<?php

declare(strict_types=1);

namespace Modules\ImportExport\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\ImportExport\Services\ImportExportService;

class ImportExportController extends Controller
{
    public function __construct(
        private readonly ImportExportService $importExportService,
    ) {
    }

    public function export(): StreamedResponse
    {
        $csv = $this->importExportService->exportCsv();

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, 'products.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $count = $this->importExportService->importCsv($request->file('file'));

        return response()->json([
            'success' => true,
            'data' => ['processed' => $count],
            'message' => 'Da import sản phẩm tu CSV.',
        ]);
    }
}
