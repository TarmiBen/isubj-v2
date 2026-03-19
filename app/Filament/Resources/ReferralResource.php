<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralResource\Pages;
use App\Models\Discount;
use App\Models\Referral;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;
    protected static ?string $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Referidos';
    protected static ?string $modelLabel      = 'Referido';
    protected static ?string $pluralModelLabel = 'Referidos';
    protected static ?string $navigationGroup = 'Pagos';
    protected static ?int    $navigationSort  = 23;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_referral');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Referido')->schema([
                Forms\Components\Select::make('referrer_student_id')
                    ->label('Alumno referidor')->required()
                    ->relationship('referrer', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name . ' — Código: ' . $record->code)
                    ->searchable()->preload(),
                Forms\Components\Select::make('referred_student_id')
                    ->label('Alumno referido')->required()
                    ->relationship('referred', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable()->preload(),
                Forms\Components\TextInput::make('referral_code')
                    ->label('Código de referido')
                    ->maxLength(20)
                    ->nullable()
                    ->helperText('Se genera automáticamente si se deja vacío'),
                Forms\Components\Select::make('discount_id')
                    ->label('Descuento a aplicar')->required()
                    ->options(Discount::where('condition_type', 'referral')->active()->pluck('name', 'id'))
                    ->searchable(),
            ])->columns(2),

            Forms\Components\Section::make('Configuración')->schema([
                Forms\Components\Select::make('status')->label('Estado')
                    ->options([
                        'pending'   => 'Pendiente',
                        'active'    => 'Activo',
                        'paused'    => 'Pausado',
                        'expired'   => 'Expirado',
                        'cancelled' => 'Cancelado',
                    ])->default('pending'),
                Forms\Components\Toggle::make('requires_referred_enrolled')
                    ->label('Requiere referido inscrito')->default(true),
                Forms\Components\DateTimePicker::make('activated_at')->label('Activado el')->nullable(),
                Forms\Components\DateTimePicker::make('expires_at')->label('Expira el')->nullable(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referrer.full_name')->label('Referidor')->searchable(),
                Tables\Columns\TextColumn::make('referred.full_name')->label('Referido')->searchable(),
                Tables\Columns\TextColumn::make('referral_code')->label('Código'),
                Tables\Columns\TextColumn::make('discount.name')->label('Descuento'),
                Tables\Columns\BadgeColumn::make('status')->label('Estado')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'gray'    => 'paused',
                        'danger'  => fn ($state) => in_array($state, ['expired', 'cancelled']),
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending'   => 'Pendiente',
                        'active'    => 'Activo',
                        'paused'    => 'Pausado',
                        'expired'   => 'Expirado',
                        'cancelled' => 'Cancelado',
                        default     => $s,
                    }),
                Tables\Columns\IconColumn::make('requires_referred_enrolled')->label('Req. inscrito')->boolean(),
                Tables\Columns\TextColumn::make('activated_at')->label('Activado')->date('d/m/Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')
                    ->options(['pending'=>'Pendiente','active'=>'Activo','paused'=>'Pausado','expired'=>'Expirado']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Acción rápida: activar referral
                Tables\Actions\Action::make('activate')
                    ->label('Activar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(fn ($record) => $record->update(['status' => 'active', 'activated_at' => now()])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReferrals::route('/'),
            'create' => Pages\CreateReferral::route('/create'),
            'edit'   => Pages\EditReferral::route('/{record}/edit'),
        ];
    }
}
