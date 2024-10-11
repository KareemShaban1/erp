<?php
namespace App\Traits;

trait HelperTrait {


    /**
 * Helper function to handle image uploads.
 */
private function handleImages($request, $inputName, $imageableType, $imageableId, $fileUploader)
{
    if ($request->hasFile($inputName)) {
        foreach ($request->file($inputName) as $imageFile) {
            $imageData = [
                'name'=> $request->image_name ?? null,
                'type' => $inputName,
                'imageable_type' => $imageableType,
                'imageable_id' => $imageableId,
            ];

            // Store the image
            $this->imageService->store($imageFile, $imageData, $fileUploader);
        }
    }
}
}
