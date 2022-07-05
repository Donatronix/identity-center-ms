<?php

namespace App\Traits;

/**
 *
 */
trait ResponseTrait
{
    public function infoResponse($title, $message, $code = 200)
    {
        return response()->json([
            'type' => 'info',
            'title' => $title,
            'message' => $message
        ], $code);
    }

    public function dangerResponse($title, $message, $code = 500)
    {
        return response()->json([
            'type' => 'danger',
            'title' => $title,
            'message' => $message
        ], $code);
    }

    public function successResponse($title, $message, $data)
    {
        return response()->json([
            'type' => 'success',
            'title' => $title,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    public function warningResponse($title, $message, $code = 400)
    {
        return response()->json([
            'type' => 'warning',
            'title' => $title,
            'message' => $message
        ], $code);
    }
}

