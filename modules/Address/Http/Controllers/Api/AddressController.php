<?php

declare(strict_types=1);

namespace Modules\Address\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Address\Models\Commune;
use Modules\Address\Models\Province;

class AddressController extends Controller
{
    /**
     * GET /api/v1/address/provinces
     * Trả về danh sách tỉnh/thành phố sắp xếp theo tên.
     */
    public function provinces(): JsonResponse
    {
        $provinces = Province::query()
            ->select(['code', 'name', 'english_name', 'administrative_level'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $provinces,
        ]);
    }

    /**
     * GET /api/v1/address/provinces/{code}/communes
     * Trả về danh sách xã/phường của tỉnh, hỗ trợ tìm kiếm.
     */
    public function communes(Request $request, string $code): JsonResponse
    {
        $search = trim((string) ($request->query('q', '')));

        $query = Commune::query()
            ->select(['code', 'name', 'english_name', 'administrative_level', 'province_code'])
            ->where('province_code', $code)
            ->orderBy('name');

        if ($search !== '') {
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $query->where('name', 'like', $like);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }
}
