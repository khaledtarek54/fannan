<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BaseController extends Controller
{
    static public function sendResponse($result, $message): JsonResponse
    {
        $data = [
            'success' => true,
            'data' => $result,
            'message' => $message,
            'errors' => null,
        ];
        return response()->json($data);
    }

    static public function sendError($errors, $code = 200, $message = "validation_failed"): \Illuminate\Foundation\Application|Response|Application|ResponseFactory
    {
        $res = [
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => $errors,
        ];
        return response($res, $code);
    }
}
