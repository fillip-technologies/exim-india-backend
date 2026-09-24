<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ImageService
{
    /** Store on the public disk and return the relative path. */
    public function store(UploadedFile $file, string $directory = 'products'): string
    {
        return $file->store($directory, 'public');
    }
}
