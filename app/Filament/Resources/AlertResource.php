<?php
namespace App\Filament\Resources;
use App\Filament\Resources\AlertResource\Pages;
use App\Models\Alert;
use App\Models\Group;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class AlertResource extends Resource
{
    protected static ?string $model = Alert::class;
    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Alertas';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 10;
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información de la Alerta')->schema([
                Forms\Components\TextInput::make('title')->label('Título')->required()->maxLength(255)->columnSpanFull(),
                Forms\Components\Textarea::make('message')->label('Mensaje')->required()->rows(4)->columnSpanFull(),
                Forms\Components\Select::make('type')->label('Tipo')->options(['info' => 'Información','warning' => 'Advertencia','danger' => 'Peligro','success' => 'Éxito'])->required()->default('info'),
                Forms\Components\Select::make('priority')->label('Prioridad')->options(['low' => 'Baja','medium' => 'Media','high' => 'Alta'])->required()->default('medium'),
                Forms\Components\DateTimePicker::make('expires_at')->label('Fecha de vencimiento')->helperText('Opcional. Si no se establece, la alerta no vencerá automáticamente.')->native(false)->seconds(false),
                Forms\Components\Toggle::make('is_active')->label('Activa')->helperText('Solo las alertas activas se mostrarán a los usuarios')->default(true)->inline(false),
            ])->columns(2),
            Forms\Components\Section::make('Destinatarios')->description('Selecciona los grupos. Los maestros de estos grupos recibirán la alerta.')->schema([
                Forms\Components\Select::make('groups')->label('Grupos')->multiple()->options(Group::orderBy('name')->pluck('name', 'id'))->searchable()->preload()->required()->helperText('Se asignará la alerta a todos los maestros que dan clases en estos grupos')->columnSpanFull(),
                Forms\Components\Select::make('additional_users')->label('Usuarios adicionales (opcional)')->multiple()->options(User::orderBy('name')->pluck('name', 'id'))->searchable()->helperText('Puedes agregar usuarios específicos además de los maestros de los grupos')->columnSpanFull(),
            ]),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->label('Título')->searchable()->sortable()->weight('bold')->limit(50),
            Tables\Columns\BadgeColumn::make('type')->label('Tipo')->formatStateUsing(fn ($state) => match($state) {'info' => 'Información','warning' => 'Advertencia','danger' => 'Peligro','success' => 'Éxito',default => $state})->colors(['primary' => 'info','warning' => 'warning','danger' => 'danger','success' => 'success']),
            Tables\Columns\BadgeColumn::make('priority')->label('Prioridad')->formatStateUsing(fn ($state) => match($state) {'low' => 'Baja','medium' => 'Media','high' => 'Alta',default => $state})->colors(['gray' => 'low','warning' => 'medium','danger' => 'high']),
            Tables\Columns\TextColumn::make('total_recipients')->label('Destinatarios')->badge()->color('info'),
            Tables\Columns\TextColumn::make('viewed_count')->label('Vistas')->badge()->color('success')->formatStateUsing(fn ($record) => $record->viewed_count . ' (' . $record->viewed_percentage . '%)'),
            Tables\Columns\TextColumn::make('closed_count')->label('Cerradas')->badge()->color('gray')->formatStateUsing(fn ($record) => $record->closed_count . ' (' . $record->closed_percentage . '%)'),
            Tables\Columns\IconColumn::make('is_active')->label('Activa')->boolean(),
            Tables\Columns\TextColumn::make('expires_at')->label('Vence')->dateTime('d/m/Y H:i')->sortable()->placeholder('Sin vencimiento')->color(fn ($record) => $record->expires_at && $record->expires_at < now() ? 'danger' : 'gray'),
            Tables\Columns\TextColumn::make('created_at')->label('Creada')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            Tables\Filters\SelectFilter::make('type')->label('Tipo')->options(['info' => 'Información','warning' => 'Advertencia','danger' => 'Peligro','success' => 'Éxito']),
            Tables\Filters\SelectFilter::make('priority')->label('Prioridad')->options(['low' => 'Baja','medium' => 'Media','high' => 'Alta']),
            Tables\Filters\TernaryFilter::make('is_active')->label('Activa'),
            Tables\Filters\Filter::make('expired')->label('Vencidas')->query(fn ($query) => $query->whereNotNull('expires_at')->where('expires_at', '<', now())),
        ])->actions([
            Tables\Actions\Action::make('toggle')->label(fn ($record) => $record->is_active ? 'Desactivar' : 'Activar')->icon(fn ($record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')->color(fn ($record) => $record->is_active ? 'warning' : 'success')->action(fn ($record) => $record->update(['is_active' => !$record->is_active]))->requiresConfirmation(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])->defaultSort('created_at', 'desc');
    }
    public static function getRelations(): array
    {
        return [];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlerts::route('/'),
            'create' => Pages\CreateAlert::route('/create'),
            'edit' => Pages\EditAlert::route('/{record}/edit'),
        ];
    }
}
