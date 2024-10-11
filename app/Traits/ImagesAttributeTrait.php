<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait ImagesAttributeTrait
{
    public function getImageSource($image)
    {
        if (!$image) {
            return "https://scotturb.com/wp-content/uploads/2016/11/product-placeholder-300x300.jpg";
        }
        if (Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }
        return asset('storage/' . $image);
    }

    public function getImagesSource($images): array
    {
        $data = [];
        if (empty($images)) {
            return ['https://scotturb.com/wp-content/uploads/2016/11/product-placeholder-300x300.jpg'];
        }

        foreach ($images as $image) {
            if (Str::startsWith($image, ['http://', 'https://'])) {
                $data[] = $image;
            } else {
                $data[] = asset('storage/' . $image);
            }
        }

        return $data;
    }
}
