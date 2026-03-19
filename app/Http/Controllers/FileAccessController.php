<?php

// ARCHIVO: app/Http/Controllers/FileAccessController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FileAccessController extends Controller
{
    public function show($filename)
    {
        // 1. Define la ruta completa del archivo en el disco 'public'
        $path = 'public/' . $filename;

        // 2. Verifica si el archivo existe
        if (!Storage::exists($path)) {
            abort(404);
        }

        // 3. Obtiene el contenido y el tipo MIME del archivo
        $file = Storage::get($path);
        $type = Storage::mimeType($path);

        // 4. Devuelve el contenido del archivo con las cabeceras correctas
        return response($file, 200)->header('Content-Type', $type);
    }
}
