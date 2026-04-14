<?php
namespace App\Filament\Resources;
use App\Filament\Resources\GalleryResource\Pages;
use App\Filament\Resources\GalleryResource\RelationManagers;
use App\Models\Gallery;
use App\Models\Reservation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class GalleryResource extends Resource
{
    protected static ?string $model = Gallery::class;
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'Galerías de Fotos';
    protected static ?string $navigationGroup = 'Multimedia';
    protected static ?int $navigationSort = 1;
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información de la Galería')->schema([
                Forms\Components\TextInput::make('title')->label('Título')->required()->maxLength(255)->columnSpanFull(),
                Forms\Components\Textarea::make('description')->label('Descripción')->rows(3)->columnSpanFull(),
                Forms\Components\Select::make('galleryable_type')->label('Tipo')->options(['App\\Models\\Reservation' => 'Reservación'])->live()->afterStateUpdated(fn ($set) => $set('galleryable_id', null)),
                Forms\Components\Select::make('galleryable_id')->label('Relacionado a')->options(function (Get $get) {
                    $type = $get('galleryable_type');
                    if ($type === 'App\\Models\\Reservation') {
                        return Reservation::with(['agenda', 'user'])->get()->mapWithKeys(function ($reservation) {
                            $label = $reservation->agenda->name;
                            if ($reservation->user) $label .= ' - ' . $reservation->user->name;
                            $label .= ' (' . $reservation->date->format('d/m/Y') . ')';
                            return [$reservation->id => $label];
                        });
                    }
                    return [];
                })->searchable()->visible(fn (Get $get) => filled($get('galleryable_type'))),
                Forms\Components\Toggle::make('is_public')->label('Galería pública')->helperText('Si está activo, la galería será visible para todos')->default(true)->inline(false),
            ])->columns(2),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('cover_photo.path')->label('Portada')->circular(),
            Tables\Columns\TextColumn::make('title')->label('Título')->searchable()->sortable()->weight('bold'),
            Tables\Columns\TextColumn::make('galleryable_type')->label('Tipo')->formatStateUsing(fn ($state) => class_basename($state))->badge()->color('info'),
            Tables\Columns\TextColumn::make('galleryable.agenda.name')->label('Relacionado a')->searchable()->description(fn ($record) => $record->galleryable?->date?->format('d/m/Y')),
            Tables\Columns\TextColumn::make('photos_count')->label('Fotos')->counts('photos')->badge()->color('success'),
            Tables\Columns\IconColumn::make('is_public')->label('Público')->boolean(),
            Tables\Columns\TextColumn::make('creator.name')->label('Creado por')->sortable()->toggleable(),
            Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            Tables\Filters\SelectFilter::make('galleryable_type')->label('Tipo')->options(['App\\Models\\Reservation' => 'Reservación']),
            Tables\Filters\TernaryFilter::make('is_public')->label('Público'),
        ])->actions([
            Tables\Actions\Action::make('view_photos')->label('Ver Fotos')->icon('heroicon-o-photo')->color('info')->url(fn ($record) => GalleryResource::getUrl('edit', ['record' => $record->id])),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
        ])->defaultSort('created_at', 'desc');
    }
    public static function getRelations(): array
    {
        return [
            RelationManagers\PhotosRelationManager::class,
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGalleries::route('/'),
            'create' => Pages\CreateGallery::route('/create'),
            'edit' => Pages\EditGallery::route('/{record}/edit'),
        ];
    }
}
