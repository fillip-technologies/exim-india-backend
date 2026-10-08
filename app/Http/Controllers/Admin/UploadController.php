<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadImageRequest;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function __construct(private ImageService $images) {}

    public function store(UploadImageRequest $request)
    {
        $path = $this->images->store($request->file('image'));

        return response()->json([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ], 201);
    }
}
