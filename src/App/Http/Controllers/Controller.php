<?php

namespace JacobHyde\Orders\App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Format a regular response.
     *
     * @param array $data
     * @param bool $success
     * @param string $error_code
     * @return JsonResponse
     */
    public function regularResponse(array $data = [], bool $success = true, string $error_code = null, $code = 200, string $error_detail = null): JsonResponse
    {
        $data['success'] = $success;
        if ($error_code) {
            $data['error'] = $error_code;
        }
        if ($error_detail) {
            $data['error_detail'] = $error_detail;
        }

        return response()->json([
            'data' => $data,
        ], $code);
    }
}