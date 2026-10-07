<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ImageService
{
    /**
     * Store on the public disk and return the relative path.
     *
     * Admin uploads go under "uploads/" — separate from "products/" and
     * "testimonials/", which hold the committed seed images (see
     * storage/app/public/.gitignore). Keeps future uploads out of git.
     */
    public function store(UploadedFile $file, string $directory = 'uploads'): string
    {
        return $file->store($directory, 'public');
    }
}
