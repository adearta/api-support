<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    public function img($folder, $img)
    {
        $path = storage_path('app/public/' . $folder . '/' . $img);

        if (!Storage::disk('public')->exists($folder . '/' . $img)) {
            abort(404);
        }

        $path_info = pathinfo($path);
        return response()->file($path, ['Content-Type' => 'image/' . $path_info['extension']]);
    }
}
