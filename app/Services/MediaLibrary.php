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
            throw ValidationException::withMessages([
                'image' => 'This image could not be decoded.',
            ]);
        }

        $directory = public_path('images/uploads');

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            imagedestroy($image);
            throw new RuntimeException('The image upload directory could not be created.');
        }

        $path = 'images/uploads/'.bin2hex(random_bytes(16)).'.webp';
        $destination = public_path($path);

        $written = @imagewebp($image, $destination, 85);
        imagedestroy($image);

        if (! $written) {
            if (file_exists($destination)) {
                unlink($destination);
            }

            throw new RuntimeException('The uploaded image could not be saved.');
        }

        return $path;
    }
}
