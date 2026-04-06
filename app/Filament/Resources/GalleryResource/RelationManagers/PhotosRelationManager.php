<?php

namespace App\Filament\Resources\GalleryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Fotos de la Galería';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('caption')
                    ->label('Descripción/Pie de foto')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('filename')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Foto')
                    ->square()
                    ->size(100),

                Tables\Columns\TextColumn::make('original_filename')
                    ->label('Nombre')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('size')
                    ->label('Tamaño')
                    ->formatStateUsing(fn ($state) => number_format($state / 1024 / 1024, 2) . ' MB')
                    ->badge()
                    ->color(fn ($state) => $state > 2097152 ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('width')
                    ->label('Dimensiones')
                    ->formatStateUsing(fn ($record) => $record->width . ' x ' . $record->height . ' px')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('caption')
                    ->label('Descripción')
                    ->limit(40)
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('order')
                    ->label('Orden')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('upload')
                    ->label('Subir Fotos')
                    ->icon('heroicon-o-photo')
                    ->color('primary')
                    ->form([
                        Forms\Components\FileUpload::make('photos')
                            ->label('Seleccionar Fotos')
                            ->image()
                            ->multiple()
                            ->maxSize(10240) // 10MB antes de comprimir
                            ->directory('galleries/' . ($this->getOwnerRecord()?->id ?? 'temp'))
                            ->visibility('public')
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('1920')
                            ->imageResizeTargetHeight('1080')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->helperText('Puedes seleccionar múltiples fotos. Las que pesen más de 2MB se comprimirán automáticamente.')
                            ->columnSpanFull()
                            ->required(),

                        Forms\Components\Textarea::make('caption')
                            ->label('Descripción (se aplicará a todas las fotos)')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data) {
                        $gallery = $this->getOwnerRecord();
                        $photos = $data['photos'] ?? [];
                        $caption = $data['caption'] ?? null;

                        $uploaded = 0;
                        $failed = 0;

                        foreach ($photos as $photoPath) {
                            try {
                                // Verificar que el archivo existe
                                if (!Storage::exists($photoPath)) {
                                    $failed++;
                                    continue;
                                }

                                $fullPath = Storage::path($photoPath);
                                $originalSize = Storage::size($photoPath);
                                $mimeType = Storage::mimeType($photoPath);

                                // Obtener dimensiones y comprimir si es necesario
                                $imageInfo = $this->compressImage($fullPath, $originalSize);

                                // Crear thumbnail
                                $thumbnailDir = 'galleries/' . $gallery->id . '/thumbnails';
                                if (!Storage::exists($thumbnailDir)) {
                                    Storage::makeDirectory($thumbnailDir);
                                }
                                $thumbnailPath = $thumbnailDir . '/' . basename($photoPath);
                                $this->createThumbnail($fullPath, Storage::path($thumbnailPath));

                                // Guardar en base de datos
                                $gallery->photos()->create([
                                    'filename' => basename($photoPath),
                                    'path' => $photoPath,
                                    'thumbnail_path' => $thumbnailPath,
                                    'original_filename' => basename($photoPath),
                                    'size' => Storage::size($photoPath), // Tamaño después de compresión
                                    'original_size' => $originalSize,
                                    'mime_type' => $mimeType,
                                    'width' => $imageInfo['width'] ?? 0,
                                    'height' => $imageInfo['height'] ?? 0,
                                    'caption' => $caption,
                                    'order' => $gallery->photos()->max('order') + 1,
                                ]);

                                $uploaded++;
                            } catch (\Exception $e) {
                                $failed++;
                                \Log::error('Error al procesar imagen: ' . $e->getMessage(), [
                                    'file' => $photoPath,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }

                        // Notificaciones según resultado
                        if ($uploaded > 0) {
                            Notification::make()
                                ->title('Fotos subidas exitosamente')
                                ->body("Se subieron {$uploaded} foto(s) correctamente" . ($failed > 0 ? ". {$failed} fallaron." : ''))
                                ->success()
                                ->send();
                        }

                        if ($failed > 0 && $uploaded === 0) {
                            Notification::make()
                                ->title('Error al subir fotos')
                                ->body("No se pudo procesar ninguna foto. Revisa el formato y tamaño.")
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalWidth('2xl')
                    ->slideOver(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => Storage::url($record->path))
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order', 'asc')
            ->reorderable('order')
            ->emptyStateHeading('No hay fotos en esta galería')
            ->emptyStateDescription('Haz clic en "Subir Fotos" para agregar imágenes')
            ->emptyStateIcon('heroicon-o-photo');
    }

    private function compressImage(string $sourcePath, int $originalSize): array
    {
        $imageInfo = getimagesize($sourcePath);

        if (!$imageInfo) {
            return ['width' => 0, 'height' => 0];
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Solo comprimir si es mayor a 2MB
        if ($originalSize > 2097152) {
            $image = null;

            switch ($mimeType) {
                case 'image/jpeg':
                    $image = @imagecreatefromjpeg($sourcePath);
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($sourcePath);
                    break;
                case 'image/webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $image = @imagecreatefromwebp($sourcePath);
                    }
                    break;
            }

            if ($image) {
                $quality = 85;
                do {
                    imagejpeg($image, $sourcePath, $quality);
                    clearstatcache(true, $sourcePath);
                    $currentSize = filesize($sourcePath);
                    $quality -= 5;
                } while ($currentSize > 2097152 && $quality > 50);

                imagedestroy($image);
            }
        }
        // Si es menor a 2MB, no hacer nada, dejar la imagen como está

        return ['width' => $width, 'height' => $height];
    }

    private function createThumbnail(string $sourcePath, string $destPath): void
    {
        if (!file_exists($sourcePath)) {
            return;
        }

        $imageInfo = @getimagesize($sourcePath);

        if (!$imageInfo) {
            return;
        }

        $mimeType = $imageInfo['mime'];

        $image = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = @imagecreatefromwebp($sourcePath);
                }
                break;
        }

        if (!$image) {
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $maxSize = 300;

        if ($width > $height) {
            $newWidth = $maxSize;
            $newHeight = ($height / $width) * $maxSize;
        } else {
            $newHeight = $maxSize;
            $newWidth = ($width / $height) * $maxSize;
        }

        $thumb = imagecreatetruecolor($newWidth, $newHeight);

        // Preservar transparencia para PNG
        if ($mimeType === 'image/png') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $directory = dirname($destPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        imagejpeg($thumb, $destPath, 80);

        imagedestroy($image);
        imagedestroy($thumb);
    }
}
