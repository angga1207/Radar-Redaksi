<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadArticleBodyImageRequest;
use App\Services\ImageOptimizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ArticleBodyImageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UploadArticleBodyImageRequest $request, ImageOptimizer $imageOptimizer): JsonResponse
    {
        $storedImage = $imageOptimizer->store($request->file('image'), 'article-body');
        $url = Storage::disk('public')->url($storedImage['path']);

        return response()->json([
            'url' => $url,
            'href' => $url,
            'contentType' => $storedImage['mime_type'],
        ], 201);
    }
}
