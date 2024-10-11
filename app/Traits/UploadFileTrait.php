<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

trait UploadFileTrait
{
    /**
     * Upload any type of file (image, video, pdf, text).
     *
     * @param UploadedFile $file
     * @param string $destinationPath
     * @param array $options
     * @return string
     */
    protected function uploadFile(UploadedFile $file, string $destinationPath, array $options = []): string
    {
        // Validate the file type
        $allowedTypes = ['image', 'video', 'pdf', 'text'];
        $fileType = $this->getFileType($file);

        if (!in_array($fileType, $allowedTypes)) {
            throw new \Exception("Unsupported file type: {$fileType}");
        }

        // Handle image-specific options
        if ($fileType === 'image' && isset($options['image_type'])) {
            $this->validateImage($file, $options['image_type'], $options['width'] ?? null, $options['height'] ?? null);
        }

        $fileName = $this->generateFileName($file, $options['objectName'] ?? null);
        $file->storeAs($destinationPath, $fileName, 'public');

        return $destinationPath . '/' . $fileName;
    }

    /**
     * Delete an uploaded file.
     *
     * @param string $filePath
     * @return void
     */
    protected function deleteUploadedFile(string $filePath): void
    {
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
    }

    /**
     * Upload an image with validation and resizing options.
     *
     * @param UploadedFile $file
     * @param string $objectName
     * @param string $folderName
     * @param array $options
     * @return string
     */
    public function uploadImage(UploadedFile $file, string $folderName, array $options = [], ?string $oldImagePath = null): string
    {
        // Set default values for width and height if not provided
        $options = array_merge(['width' => null, 'height' => null], $options);

        // Remove old image if it exists
        if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }

        // Generate a unique name for the image
        $imageName = $this->generateUniqueFileName($file);
        $imagePath = 'public/' . $folderName . '/' . $imageName;

        // Ensure the directory exists
        Storage::makeDirectory('public/' . $folderName);

        // Resize and convert image to WebP if options are provided
        if (!empty($options['width']) && !empty($options['height'])) {
            $image = Image::make($file)
                ->resize($options['width'], $options['height'])
                ->encode('webp'); // Convert to WebP format

            // Save the image to the storage path
            $image->save(storage_path('app/' . $imagePath));
        } else {
            // Convert to WebP without resizing if no dimensions are provided
            $image = Image::make($file)->encode('webp');
            $image->save(storage_path('app/' . $imagePath));
        }

        return $folderName . '/' . $imageName;
    }

/**
 * Generate a unique file name for the uploaded image.
 */
private function generateUniqueFileName(UploadedFile $file): string
{
    $timestamp = time();
    $randomString = Str::random(10);
    return $timestamp . '_' . $randomString . '.webp';
}

    /**
     * Generate a unique file name.
     *
     * @param UploadedFile $file
     * @param string|null $objectName
     * @return string
     */
    private function generateFileName(UploadedFile $file, ?string $objectName = null): string
    {
        if ($objectName) {
            return Str::slug($objectName) . '.' . $file->getClientOriginalExtension();
        }

        return Str::lower($file->getClientOriginalName());
    }

    /**
     * Determine the file type based on the MIME type.
     *
     * @param UploadedFile $file
     * @return string
     */
    private function getFileType(UploadedFile $file): string
    {
        $mimeType = $file->getMimeType();

        if (str_contains($mimeType, 'image')) {
            return 'image';
        } elseif (str_contains($mimeType, 'video')) {
            return 'video';
        } elseif (str_contains($mimeType, 'pdf')) {
            return 'pdf';
        } elseif (str_contains($mimeType, 'text')) {
            return 'text';
        }

        return 'unknown';
    }

    /**
     * Validate an image based on type, width, and height.
     *
     * @param UploadedFile $file
     * @param string $imageType
     * @param int|null $width
     * @param int|null $height
     * @return void
     */
    private function validateImage(UploadedFile $file, string $imageType, ?int $width = null, ?int $height = null): void
    {
        if (!in_array($file->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
            throw new \Exception("Unsupported image type: {$file->getClientOriginalExtension()}");
        }

        $image = Image::make($file);

        if ($width && $height && ($image->width() != $width || $image->height() != $height)) {
            throw new \Exception("Image dimensions do not match the required {$width}x{$height} size.");
        }
    }
}
