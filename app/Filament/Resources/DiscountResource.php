<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountResource\Pages;
use App\Models\Discount;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;
    protected static ?string $navigationIcon  = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel = 'Descuentos';
    protected static ?string $modelLabel      = 'Descuento';
    protected static ?string $pluralModelLabel = 'Descuentos';
    protected static ?string $navigationGroup = 'Pagos';
    protected static ?int    $navigationSort  = 12;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_discount');
    }

    /**
     * max_uses vacío = ilimitado; max_uses = 0 = nadie puede usar el descuento.
     * Ver Discount::isValid().
     */
    protected static function maxUsesIsBlocking(mixed $state): bool
    {
        return $state !== null && $state !== '' && (int) $state === 0;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificación')->schema([
                Forms\Components\TextInput::make('code')->label('Código')->required()->unique(ignoreRecord: true)->maxLength(50),
                Forms\Components\TextInput::make('name')->label('Nombre')->required(),
                Forms\Components\Textarea::make('description')->label('Descripción')->rows(2),
            ])->columns(2),

            Forms\Components\Section::make('Valor')->schema([
                Forms\Components\Select::make('value_type')->label('Tipo de valor')->required()
                    ->options(['percentage' => 'Porcentaje (%)', 'fixed' => 'Monto fijo ($)'])->live(),
                Forms\Components\TextInput::make('value')->label('Valor')->required()->numeric()
                    ->suffix(fn ($get) => $get('value_type') === 'percentage' ? '%' : '$'),
                Forms\Components\Select::make('applies_to_type')->label('Aplica a')
                    ->options([
                        ''            => 'Todos los conceptos',
                        'mensualidad' => 'Mensualidades',
                        'inscripcion' => 'Inscripciones',
                        'constancia'  => 'Constancias',
                        'seguro'      => 'Seguros',
                        'credencial'  => 'Credenciales',
                        'practica'    => 'Prácticas',
                    ])->nullable(),
            ])->columns(3),

            Forms\Components\Section::make('Descuento Individual')->schema([
                Forms\Components\Select::make('student_id')
                    ->label('Alumno específico (vacío = todos)')
                    ->options(Student::query()->orderBy('name')->get()->mapWithKeys(fn ($s) => [
                        $s->id => "{$s->student_number} - {$s->full_name}"
                    ]))
                    ->searchable()
                    ->nullable()
                    ->live()
                    ->helperText('Si seleccionas un alumno, este descuento SOLO se aplicará a él de forma automática'),
                Forms\Components\Toggle::make('is_single_use')
                    ->label('Uso único')
                    ->helperText('Se aplicará solo una vez y no se podrá reutilizar')
                    ->visible(fn ($get) => $get('student_id') !== null),
            ])->columns(2)
                ->description('Crea descuentos personalizados para alumnos específicos. Son automáticos y no reutilizables.'),

            Forms\Components\Section::make('Condiciones')->schema([
                Forms\Components\Select::make('condition_type')->label('Condición')->required()
                    ->options([
                        'manual'        => 'Manual',
                        'referral'      => 'Referido',
                        'scholarship'   => 'Beca',
                        'early_payment' => 'Pronto pago',
                        'promo'         => 'Promoción',
                    ]),
                Forms\Components\Toggle::make('is_automatic')->label('Automático'),
                Forms\Components\Toggle::make('is_stackable')->label('Acumulable'),
                Forms\Components\Toggle::make('is_recurring')->label('Recurrente'),
            ])->columns(4),

            Forms\Components\Section::make('Vigencia y límites')->schema([
                Forms\Components\DatePicker::make('valid_from')->label('Válido desde')->columnSpan(1),
                Forms\Components\DatePicker::make('valid_until')->label('Válido hasta')->columnSpan(1),
                Forms\Components\TextInput::make('max_uses')
                    ->label('Máx. usos')
                    ->numeric()
                    ->minValue(0)
                    ->nullable()
                    ->live(debounce: 500)
                    ->columnSpan(1)
                    ->helperText(fn ($state) => static::maxUsesIsBlocking($state)
                        ? 'Este descuento no se aplicará a nadie.'
                        : 'Déjalo vacío para usos ilimitados.'),
                Forms\Components\Toggle::make('active')->label('Activo')->default(true)->columnSpan(1),

                // Aviso, no validación: se puede guardar con 0 si así se quiere.
                Forms\Components\Placeholder::make('max_uses_warning')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->visible(fn (Forms\Get $get) => static::maxUsesIsBlocking($get('max_uses')))
                    ->content(new \Illuminate\Support\HtmlString(
                        '<div class="flex gap-3 rounded-lg border border-warning-400/40 bg-warning-50 p-3 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-300">'
                        . '<span class="text-lg leading-none">⚠️</span>'
                        . '<div><p class="font-semibold">Con 0 usos nadie podrá usar este descuento.</p>'
                        . '<p class="mt-0.5">Si lo que quieres es que no tenga límite, deja el campo <strong>vacío</strong>. '
                        . 'Puedes guardarlo así de todos modos.</p></div></div>'
                    )),
            ])->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Código')->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable()->limit(30),
                \App\Filament\Support\StudentColumn::make()
                    ->placeholder(null)
                    ->default('Todos')
                    ->formatStateUsing(fn ($state, $record) => $record->student_id ? $state : 'Todos')
                    ->badge()
                    ->color(fn ($record) => $record->student_id ? 'success' : 'gray'),
                Tables\Columns\BadgeColumn::make('condition_type')->label('Condición')
                    ->colors([
                        'success' => 'referral',
                        'primary' => 'scholarship',
                        'warning' => 'early_payment',
                        'info'    => 'promo',
                        'gray'    => 'manual',
                    ]),
                Tables\Columns\TextColumn::make('value')->label('Valor')
                    ->formatStateUsing(fn ($state, $record) =>
                        $record->value_type === 'percentage' ? "{$state}%" : "\${$state}"
                    ),
                Tables\Columns\TextColumn::make('used_count')->label('Usos'),
                Tables\Columns\TextColumn::make('max_uses')->label('Límite')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match (true) {
                        is_null($state) => 'Ilimitado',
                        (int) $state === 0 => 'Bloqueado',
                        default => (string) $state,
                    })
                    ->color(fn ($state) => match (true) {
                        is_null($state) => 'gray',
                        (int) $state === 0 => 'danger',
                        default => 'info',
                    }),
                Tables\Columns\IconColumn::make('is_single_use')->label('Único')->boolean(),
                Tables\Columns\IconColumn::make('is_automatic')->label('Auto')->boolean(),
                Tables\Columns\IconColumn::make('active')->label('Activo')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('condition_type')->label('Condición')
                    ->options(['manual'=>'Manual','referral'=>'Referido','scholarship'=>'Beca','early_payment'=>'Pronto pago','promo'=>'Promo']),
                Tables\Filters\TernaryFilter::make('active')->label('Activo'),
                Tables\Filters\TernaryFilter::make('is_automatic')->label('Automático'),
                Tables\Filters\Filter::make('individual')
                    ->label('Solo descuentos individuales')
                    ->query(fn ($query) => $query->whereNotNull('student_id')),
                Tables\Filters\TernaryFilter::make('is_single_use')->label('Uso único'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit'   => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
