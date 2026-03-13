<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\GaleriaImagen;
use App\Models\Encabezado;
use App\Models\Colegio;
use App\Models\SobreNosotros;

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
            }),
            'catalogo' => Cache::rememberForever('public_catalogo', function () {
                $encabezado = Encabezado::where('seccion', 'catalogo')->first();
                if (!$encabezado) {
                    $encabezado = [
                        'titulo' => 'Nuestra Oferta Educativa',
                        'subtitulo' => 'Excelencia Académica',
                        'descripcion' => 'Explora los programas especializados de nuestras instituciones.'
                    ];
                }

                $colegios = Colegio::with('catalogoCarreras')->get();
                $institutions = $colegios->map(function ($col) {
                    return [
                        'id' => 'col_' . $col->id,
                        'name' => $col->nombre,
                        'label' => $col->label ?? 'Categoría',
                        'description' => $col->descripcion_web ?? 'Descripción del colegio',
                        'theme' => $col->theme ?? '#2563eb', 
                        'careers' => $col->catalogoCarreras->map(function ($car) {
                            return [
                                'id' => $car->id,
                                'title' => $car->nombre,
                                'subtitle' => $car->jornada,
                                'schedule' => $car->jornada,
                                'badge' => $car->badge,
                                'icon' => $car->icon ?? 'graduation-cap',
                                'features' => $car->detalles ?? [],
                            ];
                        })
                    ];
                });

                return [
                    'encabezado' => $encabezado,
                    'institutions' => $institutions
                ];
            }),
            'nosotros' => Cache::rememberForever('public_nosotros', function () {
                $encabezado = Encabezado::where('seccion', 'nosotros')->first();
                if (!$encabezado) {
                    $encabezado = [
                        'titulo' => 'Sobre Nosotros',
                        'subtitulo' => 'Nuestra Historia',
                        'descripcion' => 'Conoce más sobre nuestra trayectoria y fundadores.'
                    ];
                }

                $registros = SobreNosotros::all()->groupBy('tipo');
                $colegios = $registros->has('colegio') ? $registros['colegio'] : [];
                $fundadores = $registros->has('fundador') ? $registros['fundador'] : [];

                return [
                    'encabezado' => $encabezado,
                    'colegios' => $colegios,
                    'fundadores' => $fundadores
                ];
            })
        ];

        return response()->json($data);
    }
}
