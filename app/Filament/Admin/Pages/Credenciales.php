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

        $font  = '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf';
        $white = imagecolorallocate($img, 255, 255, 255);

        // ── Foto del alumno ──────────────────────────────
        // Slot: x:737, y:277, máx 234×260 — alineada al fondo del slot
        if ($student->photo && Storage::disk('public')->exists($student->photo)) {
            $photoPath = Storage::disk('public')->path($student->photo);
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
                imagecopyresampled($resized, $photoImg, 0, 0, 0, 0, $nw, $nh, $sw, $sh);
                imagecopy($img, $resized, $slotX, $destY, 0, 0, $nw, $nh);
                imagedestroy($photoImg);
                imagedestroy($resized);
            }
        }

        // ── Nombre: área x:519, y:240, w:549, h:36 — alineado a la izquierda ──
        $fullName = strtoupper($this->removeAccents($student->full_name));
        $this->drawLeftText($img, $fullName, $font, 519, 240, 549, 36, $white);

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
        $fontSize = 15;

        $bbox  = imagettfbbox($fontSize, 0, $font, $text);
        $tw    = $bbox[2] - $bbox[0];
        $th    = abs($bbox[1] - $bbox[7]);

        $textX = $x + (int)(($maxW - $tw) / 2);
        $textY = $y + (int)(($maxH + $th) / 2);

        imagettftext($img, $fontSize, 0, $textX, $textY, $color, $font, $text);
    }

    /**
     * Dibuja texto a 15px alineado a la izquierda, centrado verticalmente.
     */
    private function drawLeftText(
        \GdImage $img,
        string   $text,
        string   $font,
        int      $x,
        int      $y,
        int      $maxW,
        int      $maxH,
        int      $color
    ): void {
        $fontSize = 15;

        $bbox  = imagettfbbox($fontSize, 0, $font, $text);
        $th    = abs($bbox[1] - $bbox[7]);
        $textY = $y + (int)(($maxH + $th) / 2);

        imagettftext($img, $fontSize, 0, $x, $textY, $color, $font, $text);
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
        $fontSize = 15;
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