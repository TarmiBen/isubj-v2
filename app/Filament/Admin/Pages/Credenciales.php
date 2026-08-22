<?php

namespace App\Filament\Admin\Pages;

use App\Models\Career;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class Credenciales extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Credenciales';
    protected static string $view = 'filament.admin.pages.credenciales';
    protected static ?string $title = 'Generador de Credenciales';
    protected static ?string $navigationGroup = 'Gestión de Estudiantes';
    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public function mount(): void
    {
        $vigencia = strtoupper(now()->locale('es')->isoFormat('MMMM YYYY'));

        $this->form->fill([
            'vigencia' => $vigencia,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuración de Credenciales')
                    ->schema([
                        Forms\Components\Select::make('students')
                            ->label('Alumnos')
                            ->multiple()
                            ->searchable()
                            ->searchDebounce(400)
                            ->getSearchResultsUsing(fn (string $search) =>
                                Student::where(function ($q) use ($search) {
                                    $q->where('name', 'like', "%{$search}%")
                                      ->orWhere('last_name1', 'like', "%{$search}%")
                                      ->orWhere('last_name2', 'like', "%{$search}%")
                                      ->orWhere('student_number', 'like', "%{$search}%");
                                })
                                ->limit(5)
                                ->get()
                                ->mapWithKeys(fn ($s) => [
                                    $s->id => $s->full_name . ' (' . $s->student_number . ')',
                                ])
                            )
                            ->getOptionLabelsUsing(fn (array $values) =>
                                Student::whereIn('id', $values)
                                    ->get()
                                    ->mapWithKeys(fn ($s) => [
                                        $s->id => $s->full_name . ' (' . $s->student_number . ')',
                                    ])
                            )
                            ->required()
                            ->helperText('Escribe al menos 1 letra para buscar alumnos (máx. 5 resultados)')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('vigencia')
                            ->label('Vigencia')
                            ->required()
                            ->helperText('Ej: ABRIL 2026'),

                        Forms\Components\Select::make('career_id')
                            ->label('Carrera')
                            ->options(fn () => Career::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $data = $this->form->getState();

        $studentIds = $data['students'] ?? [];
        $vigencia   = strtoupper(trim($data['vigencia']));
        $career     = Career::find($data['career_id']);
        $careerName = $career ? strtoupper($career->name) : '';

        $students = Student::whereIn('id', $studentIds)->get();

        if ($students->isEmpty()) {
            Notification::make()->title('Sin alumnos seleccionados')->warning()->send();
            return;
        }

        $tempDir = storage_path('app/temp/credenciales');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $generatedFiles = [];

        foreach ($students as $student) {
            $slug       = Str::slug($student->full_name);
            $outputPath = $tempDir . '/' . $slug . '_credencial.jpg';
            $this->generateCredencial($student, $vigencia, $careerName, $outputPath);
            $generatedFiles[] = ['path' => $outputPath, 'name' => $slug . '_credencial.jpg'];
        }

        $uuid = Str::uuid()->toString();

        if (count($generatedFiles) === 1) {
            Cache::put("credencial_{$uuid}", [
                'type' => 'single',
                'path' => $generatedFiles[0]['path'],
                'name' => $generatedFiles[0]['name'],
            ], 300);
        } else {
            $zipPath = $tempDir . '/' . $uuid . '.zip';
            $zip     = new ZipArchive();
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            foreach ($generatedFiles as $file) {
                $zip->addFile($file['path'], $file['name']);
            }
            $zip->close();
            foreach ($generatedFiles as $file) {
                @unlink($file['path']);
            }
            Cache::put("credencial_{$uuid}", [
                'type' => 'zip',
                'path' => $zipPath,
                'name' => 'credenciales.zip',
            ], 300);
        }

        $this->dispatch('open-download', uuid: $uuid);
    }

    // ── Image generation ──────────────────────────────────────────────────────

    private function generateCredencial(
        Student $student,
        string  $vigencia,
        string  $careerName,
        string  $outputPath
    ): void {
        $templatePath = public_path('credencial.jpg');
        $img          = imagecreatefromjpeg($templatePath);

        $font  = public_path('fonts/LiberationSans-Bold.ttf');
        $white = imagecolorallocate($img, 255, 255, 255);

        // ── Foto del alumno ──────────────────────────────
        // Slot: x:737, y:277, máx 234×260 — alineada al fondo del slot
        if ($student->photo && Storage::disk('public')->exists($student->photo)) {
            $photoPath = Storage::disk('public')->path($student->photo);
            $info      = @getimagesize($photoPath);
            $isPng     = $info && ($info[2] ?? 0) === IMAGETYPE_PNG;
            $photoImg  = $this->loadImage($photoPath);
            if ($photoImg) {
                $slotX = 737; $slotY = 277; $maxW = 234; $maxH = 260;
                $sw    = imagesx($photoImg);
                $sh    = imagesy($photoImg);
                $ratio = min($maxW / $sw, $maxH / $sh);
                $nw    = (int) ($sw * $ratio);
                $nh    = (int) ($sh * $ratio);
                // Alinear al borde inferior del slot
                $destY = $slotY + ($maxH - $nh);

                $resized = imagecreatetruecolor($nw, $nh);

                if ($isPng) {
                    // Inicializar con transparencia total para respetar el canal alfa del PNG
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                    imagefill($resized, 0, 0, $transparent);
                } else {
                    $whiteBg = imagecolorallocate($resized, 255, 255, 255);
                    imagefill($resized, 0, 0, $whiteBg);
                }

                imagealphablending($resized, true);
                imagecopyresampled($resized, $photoImg, 0, 0, 0, 0, $nw, $nh, $sw, $sh);

                // Alpha blending activado en la plantilla para que los píxeles
                // transparentes del PNG dejen ver el fondo de la credencial
                imagealphablending($img, true);
                imagecopy($img, $resized, $slotX, $destY, 0, 0, $nw, $nh);
                imagedestroy($photoImg);
                imagedestroy($resized);
            }
        }

        // ── Nombre: área x:519, y:240, w:470, h:36 — alineado a la izquierda ──
        // Si no cabe en una línea, el último apellido baja a un segundo renglón.
        $this->drawNameBlock($img, $student, $font, 519, 240, 470, 36, $white);

        // ── Carrera: área x:512, y:360, w:219, h:88 ─────
        $this->drawWrappedText($img, strtoupper($this->removeAccents($careerName)), $font, 512, 360, 219, 88, $white);

        // ── Vigencia: área x:477, y:494, w:252, h:59 ────
        $this->drawFittedText($img, strtoupper($this->removeAccents($vigencia)), $font, 477, 494, 252, 59, $white);

        imagejpeg($img, $outputPath, 95);
        imagedestroy($img);
    }

    private function loadImage(string $path): \GdImage|false
    {
        $info = @getimagesize($path);
        if (!$info) {
            return false;
        }
        return match ($info[2] ?? 0) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default        => false,
        };
    }

    /**
     * Dibuja texto a 15px centrado horizontal y verticalmente en el área.
     */
    private function drawFittedText(
        \GdImage $img,
        string   $text,
        string   $font,
        int      $x,
        int      $y,
        int      $maxW,
        int      $maxH,
        int      $color
    ): void {
        $fontSize = 18;

        $bbox  = imagettfbbox($fontSize, 0, $font, $text);
        $tw    = $bbox[2] - $bbox[0];
        $th    = abs($bbox[1] - $bbox[7]);

        $textX = $x + (int)(($maxW - $tw) / 2);
        $textY = $y + (int)(($maxH + $th) / 2);

        imagettftext($img, $fontSize, 0, $textX, $textY, $color, $font, $text);
    }

    /**
     * Dibuja el nombre del alumno alineado a la izquierda.
     *
     * Si el nombre completo no cabe en una sola línea, baja el último apellido
     * al renglón inferior. Si aun así no cabe, baja ambos apellidos y, como
     * último recurso, reduce el tamaño de fuente hasta que quepa.
     */
    private function drawNameBlock(
        \GdImage $img,
        Student  $student,
        string   $font,
        int      $x,
        int      $y,
        int      $maxW,
        int      $maxH,
        int      $color
    ): void {
        $clean = fn (?string $t) => trim(preg_replace('/\s+/', ' ', strtoupper($this->removeAccents((string) $t))));

        $names = $clean($student->name);
        $last1 = $clean($student->last_name1);
        $last2 = $clean($student->last_name2);
        $full  = $clean("{$names} {$last1} {$last2}");

        if ($full === '') {
            return;
        }

        $singleSize = 18;
        $multiSize  = 16;

        // ¿Cabe completo en una línea?
        if ($this->textWidth($full, $font, $singleSize) <= $maxW) {
            $this->drawTextLines($img, [$full], $font, $singleSize, $x, $y, $maxH, $color);
            return;
        }

        // Opción 1: el último apellido pasa abajo.
        $lines = null;
        $first = $clean("{$names} {$last1}");

        if ($last2 !== '' && $this->textWidth($first, $font, $multiSize) <= $maxW) {
            $lines = [$first, $last2];
        } elseif ($names !== '' && $this->textWidth($names, $font, $multiSize) <= $maxW) {
            // Opción 2: ambos apellidos abajo.
            $lines = [$names, $clean("{$last1} {$last2}")];
        }

        // Opción 3: corte por palabras.
        if ($lines === null) {
            $lines = $this->wrapWords($full, $font, $multiSize, $maxW);
        }

        $lines = array_values(array_filter($lines, fn ($l) => $l !== ''));

        // Último recurso: reducir la fuente hasta que la línea más ancha quepa.
        $fontSize = $multiSize;
        while ($fontSize > 10) {
            $widest = max(array_map(fn ($l) => $this->textWidth($l, $font, $fontSize), $lines));
            if ($widest <= $maxW) {
                break;
            }
            $fontSize--;
        }

        $this->drawTextLines($img, $lines, $font, $fontSize, $x, $y, $maxH, $color);
    }

    /**
     * Dibuja un bloque de líneas alineadas a la izquierda.
     * Una sola línea se centra verticalmente en el área; con varias líneas el
     * bloque se ancla al borde superior y se recorre hacia arriba lo necesario
     * para que el último renglón no invada el espacio de la foto.
     */
    private function drawTextLines(
        \GdImage $img,
        array    $lines,
        string   $font,
        int      $fontSize,
        int      $x,
        int      $y,
        int      $maxH,
        int      $color
    ): void {
        $bbox   = imagettfbbox($fontSize, 0, $font, 'AQ');
        $ascent = abs($bbox[1] - $bbox[7]);
        $lineH  = $ascent + 5;

        if (count($lines) === 1) {
            $baseY = $y + (int) (($maxH + $ascent) / 2);
        } else {
            $lastBaseline = $y + $maxH - (count($lines) - 1) * $lineH;
            // Sin bajar de la etiqueta "NOMBRE:" aunque haya 3+ renglones.
            $baseY = max($y + $ascent - 8, min($y + $ascent, $lastBaseline));
        }

        foreach ($lines as $i => $line) {
            imagettftext($img, $fontSize, 0, $x, $baseY + $i * $lineH, $color, $font, $line);
        }
    }

    private function textWidth(string $text, string $font, int $fontSize): int
    {
        $bbox = imagettfbbox($fontSize, 0, $font, $text);

        return (int) ($bbox[2] - $bbox[0]);
    }

    /**
     * Corte por palabras cuando ni el nombre solo cabe en el ancho disponible.
     *
     * @return array<int, string>
     */
    private function wrapWords(string $text, string $font, int $fontSize, int $maxW): array
    {
        $lines   = [];
        $current = '';

        foreach (explode(' ', $text) as $word) {
            $test = $current !== '' ? $current . ' ' . $word : $word;
            if ($current !== '' && $this->textWidth($test, $font, $fontSize) > $maxW) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $test;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    /**
     * Dibuja texto con salto de línea automático a 15px.
     * Centra cada línea horizontalmente y el bloque verticalmente.
     */
    private function drawWrappedText(
        \GdImage $img,
        string   $text,
        string   $font,
        int      $x,
        int      $y,
        int      $maxW,
        int      $maxH,
        int      $color
    ): void {
        $fontSize = 18;
        $words    = explode(' ', $text);
        $lines    = [];
        $current  = '';

        foreach ($words as $word) {
            $test = $current !== '' ? $current . ' ' . $word : $word;
            $bbox = imagettfbbox($fontSize, 0, $font, $test);
            $tw   = $bbox[2] - $bbox[0];
            if ($tw > $maxW && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $test;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        $bbox   = imagettfbbox($fontSize, 0, $font, 'A');
        $lineH  = abs($bbox[1] - $bbox[7]) + 4;
        $totalH = count($lines) * $lineH;
        $startY = $y + (int)(($maxH - $totalH) / 2) + $lineH;

        foreach ($lines as $i => $line) {
            $lbbox = imagettfbbox($fontSize, 0, $font, $line);
            $lw    = $lbbox[2] - $lbbox[0];
            $lineX = $x + (int)(($maxW - $lw) / 2);
            imagettftext($img, $fontSize, 0, $lineX, $startY + $i * $lineH, $color, $font, $line);
        }
    }

    /**
     * Elimina acentos y caracteres especiales del español.
     */
    private function removeAccents(string $text): string
    {
        return strtr($text, [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
        ]);
    }
}