<?php

namespace App\Filament\Teacher\Resources\AssignmentResource\Pages;

use App\Filament\Support\CommonActions;
use App\Filament\Teacher\Resources\AssignmentResource;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class ViewAssignmentDetails extends Page implements HasTable
{
    use InteractsWithRecord, InteractsWithTable;

    protected static string $resource = AssignmentResource::class;

    protected static string $view = 'filament.teacher.resources.assignment-resource.pages.view-assignment-details';


    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.teacher.resources.assignments.index') => 'Asignaturas',
            route('filament.teacher.resources.assignments.view', $this->record)  => $this->record->subject->name,
            $this->getTitle(),
        ];
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return "Documentos :  {$this->record->subject->name} / {$this->record->group->code}";
    }

    protected function getHeaderActions(): array
    {
        return [
            CommonActions::uploadDocumentModel($this->record),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Document::query()->where('documentable_type', get_class($this->record))->where('documentable_id', $this->record->id))
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('src')
                    ->label('Archivo')
                    ->formatStateUsing(fn (string $state): string => basename($state))
                    ->url(fn (Document $record): string => Storage::url($record->src))
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')
                    ->label('Fecha de Subida')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        TextInput::make('name')
                            ->label('Nombre del Documento')
                            ->required()
                            ->maxLength(255),
                        FileUpload::make('src')
                            ->label('Archivo')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'application/zip',
                                'application/x-rar-compressed'
                            ])
                            ->maxSize(20480) // 20MB
                            ->directory('assignment-documents')
                            ->preserveFilenames()
                    ])
                    ->using(function (Document $record, array $data): Document {
                        // Si se subió un nuevo archivo, eliminar el anterior
                        if (isset($data['src']) && $data['src'] !== $record->src) {
                            if (Storage::exists($record->src)) {
                                Storage::delete($record->src);
                            }

                            // Obtener información del nuevo archivo de forma segura
                            $fileSize = null;
                            $mimeType = null;

                            try {
                                if (Storage::exists($data['src'])) {
                                    $fileSize = Storage::size($data['src']);
                                    $mimeType = Storage::mimeType($data['src']);
                                }
                            } catch (\Exception $e) {
                                $fileSize = 0;
                                $mimeType = 'unknown';
                            }

                            $data['meta'] = [
                                'size' => $fileSize,
                                'mime_type' => $mimeType
                            ];
                        }

                        $record->update($data);

                        Notification::make()
                            ->title('Documento actualizado exitosamente')
                            ->success()
                            ->send();

                        return $record;
                    }),
                TableAction::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Document $record): string => Storage::url($record->src))
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->before(function (Document $record) {
                        // Eliminar el archivo del storage
                        if (Storage::exists($record->src)) {
                            Storage::delete($record->src);
                        }
                    })
                    ->after(function () {
                        Notification::make()
                            ->title('Documento eliminado exitosamente')
                            ->success()
                            ->send();
                    })
            ])
            ->emptyStateHeading('No hay documentos cargados')
            ->emptyStateDescription('Usa el botón "Cargar Nuevo Documento" para subir archivos.')
            ->emptyStateIcon('heroicon-o-document');
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        if (!$bytes) return '0 B';

        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function authorizeAccess(): void
    {
        abort_unless(
            static::getResource()::canView($this->getRecord()),
            401
        );
    }

}
