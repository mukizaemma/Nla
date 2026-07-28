<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminImageUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadEditorImageController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:'.AdminImageUploader::ABSOLUTE_UPLOAD_MAX_KB],
        ]);

        try {
            $result = AdminImageUploader::store(
                $request->file('file'),
                'editor-images',
                false,
                'editor'
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'url' => asset($result['path']),
            'bytes' => $result['bytes'],
            'formatted_bytes' => AdminImageUploader::formatBytes($result['bytes']),
            'was_resized' => $result['was_resized'],
        ]);
    }
}
