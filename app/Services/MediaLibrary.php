<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MediaLibrary
{
    public function store(UploadedFile $upload): string
    {
        $image = @imagecreatefromstring($upload->getContent());
        if (! $image) {
            throw ValidationException::withMessages(['image' => 'This image could not be decoded.']);
        }

        $directory = public_path('images/uploads');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            imagedestroy($image);
            throw new RuntimeException('The image upload directory could not be created.');
        }

        $path = 'images/uploads/'.bin2hex(random_bytes(16)).'.webp';
        $written = @imagewebp($image, public_path($path), 85);
        imagedestroy($image);
        if (! $written) {
            if (file_exists(public_path($path))) {
                unlink(public_path($path));
            }
            throw new RuntimeException('The uploaded image could not be saved.');
        }

        return $path;
    }
}
