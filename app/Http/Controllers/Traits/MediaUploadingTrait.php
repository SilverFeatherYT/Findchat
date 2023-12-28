<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

trait MediaUploadingTrait
{
    public function storeMedia(Request $request)
    {
        // Validates file size
        if (request()->has('size')) {
            $this->validate(request(), [
                'file' => 'max:' . request()->input('size') * 1024,
            ]);
        }
        // If width or height is preset - we are validating it as an image
        if (request()->has('width') || request()->has('height')) {
            $this->validate(request(), [
                'file' => sprintf(
                    'image|dimensions:max_width=%s,max_height=%s',
                    request()->input('width', 100000),
                    request()->input('height', 100000)
                ),
            ]);
        }

        $path = storage_path('tmp/uploads');

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        foreach ($request->file('images') as $file) {
            if ($file->isValid()) {
                $filename = $file->getClientOriginalName();
                $name = uniqid() . '_' . trim($filename);

                // Move and store each image
                $file->move($path, $name);

                // Create image from the new location
                $newFilePath = $path . '/' . $name;
                $image = Image::make($newFilePath);

                $uploadedImages[] = [
                    'name' => $name,
                    'original_name' => $filename,
                    'url' => asset('tmp/' . $name),
                    'height' => $image->height(),
                    'width' => $image->width(),
                ];
            }
        }


        return response()->json($uploadedImages);
    }
}
