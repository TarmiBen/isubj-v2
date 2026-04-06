<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgendaResource\Pages;
use App\Filament\Resources\AgendaResource\RelationManagers;
use App\Models\Agenda;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AgendaResource extends Resource
{
    protected static ?string $model = Agenda::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Agendas';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Todas las agendas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información general')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'room' => 'Sala física (con QR)',
                                'calendar' => 'Agenda/Calendario',
                            ])
                            ->required()
                            ->default('room')
                            ->live(),
                        Forms\Components\ColorPicker::make('color')
                            ->label('Color'),
                        Forms\Components\TextInput::make('icon')
                            ->label('Icono')
                            ->placeholder('heroicon-o-beaker')
                            ->helperText('Nombre de heroicon'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),

                Forms\Components\Section::make('Disponibilidad')
                    ->schema([
                        Forms\Components\CheckboxList::make('available_days')
                            ->label('Días disponibles')
                            ->options([
                                1 => 'Lun',
                                2 => 'Mar',
                                3 => 'Mié',
                                4 => 'Jue',
                                5 => 'Vie',
                                6 => 'Sáb',
                                7 => 'Dom',
                            ])
                            ->columns(7)
                            ->gridDirection('row'),
                        Forms\Components\TimePicker::make('open_time')
                            ->label('Hora de apertura')
                            ->seconds(false),
                        Forms\Components\TimePicker::make('close_time')
                            ->label('Hora de cierre')
                            ->seconds(false),
                    ])->columns(3),

                Forms\Components\Section::make('Sala física')
                    ->visible(fn(Get $get) => $get('type') === 'room')
                    ->schema([
                        Forms\Components\TextInput::make('capacity')
                            ->label('Capacidad')
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\Toggle::make('requires_qr')
                            ->label('Requiere QR')
                            ->live()
                            ->inline(false)
                            ->helperText('Activa si el aula tiene QR físico impreso'),
                        Forms\Components\Placeholder::make('qr_room_code')
                            ->label('Código QR del aula')
                            ->content(fn($record) => $record?->qr_room_code ?? 'Se generará al guardar')
                            ->visible(fn(Get $get) => $get('requires_qr')),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn($state) => $state === 'room' ? 'Sala física' : 'Calendario')
                    ->colors([
                        'primary' => 'room',
                        'warning' => 'calendar',
                    ]),
                Tables\Columns\IconColumn::make('requires_qr')
                    ->label('QR')
                    ->boolean(),
                Tables\Columns\TextColumn::make('available_days')
                    ->label('Días')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        $days = ['', 'L', 'M', 'X', 'J', 'V', 'S', 'D'];
                        return collect($state)->map(fn($d) => $days[$d] ?? '')->join(' ');
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('reservations_count')
                    ->label('Reservaciones')
                    ->counts('reservations'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'room' => 'Sala física',
                        'calendar' => 'Calendario',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\Action::make('downloadQr')
                    ->label('Descargar QR')
                    ->icon('heroicon-o-qr-code')
                    ->visible(fn($record) => $record->hasPhysicalQr())
                    ->action(function ($record) {
                        $qr = QrCode::format('png')->size(300)->generate($record->qr_room_code);
                        return response()->streamDownload(
                            fn() => print($qr),
                            "qr-{$record->slug}.png",
                            ['Content-Type' => 'image/png']
                        );
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle')
                    ->label(fn($record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn($record) => $record->update(['is_active' => !$record->is_active]))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ReservationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgendas::route('/'),
            'create' => Pages\CreateAgenda::route('/create'),
            'edit' => Pages\EditAgenda::route('/{record}/edit'),
            'calendar' => Pages\CalendarView::route('/calendar'),
        ];
    }
}
