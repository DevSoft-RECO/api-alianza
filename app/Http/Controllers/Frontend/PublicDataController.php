<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\GaleriaImagen;

class PublicDataController extends Controller
{
    public function init()
    {
        // Consolidamos las 3 peticiones en una sola, aprovechando la caché ya implementada
        $data = [
            'slider' => Cache::rememberForever('public_slider', function () {
                $settings = DB::table('slider_settings')->orderBy('id', 'asc')->get();
                $images = DB::table('slider_images')->orderBy('created_at', 'desc')->get();

                $mappedImages = $images->map(function ($img) {
                    return [
                        'id' => $img->id,
                        'url' => asset($img->image_path), 
                        'category' => $img->category,
                    ];
                });

                return [
                    'settings' => $settings,
                    'images' => $mappedImages
                ];
            }),
            'galeria' => Cache::rememberForever('public_gallery', function () {
                return GaleriaImagen::orderBy('orden')->get();
            }),
            'settings' => Cache::rememberForever('public_settings', function () {
                return DB::table('configuraciones_sitio')->pluck('valor', 'clave');
            })
        ];

        return response()->json($data);
    }
}
