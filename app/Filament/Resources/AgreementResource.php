<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgreementResource\Pages;
use App\Models\Agreement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AgreementResource extends Resource
{
    protected static ?string $model = Agreement::class;
    protected static ?string $navigationIcon  = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Convenios';
    protected static ?string $modelLabel      = 'Convenio';
    protected static ?string $pluralModelLabel = 'Convenios';
    protected static ?string $navigationGroup = 'Pagos';
    protected static ?int    $navigationSort  = 22;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_agreement');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Convenio')->schema([
                Forms\Components\TextInput::make('folio')->label('Folio')->disabled()->dehydrated(false),
                Forms\Components\Select::make('student_id')
                    ->label('Alumno')->required()
                    ->relationship('student', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name . ' (' . $record->student_number . ')')
                    ->searchable()->preload()->live(),
                Forms\Components\Select::make('type')->label('Tipo')->required()
                    ->options([
                        'credit_extension' => 'Extensión de crédito',
                        'installment_plan' => 'Plan de parcialidades',
                        'both'             => 'Ambos',
                    ])->live(),
                Forms\Components\Select::make('status')->label('Estado')
                    ->options([
                        'pending_approval' => 'Pendiente aprobación',
                        'active'           => 'Activo',
                        'completed'        => 'Completado',
                        'defaulted'        => 'En mora',
                        'cancelled'        => 'Cancelado',
                    ])->default('pending_approval'),
            ])->columns(2),

            // Adeudos a incluir en el convenio (campo virtual)
            Forms\Components\Section::make('Adeudos a incluir')
                ->description('Selecciona los adeudos que cubre este convenio. Solo aparecen adeudos pendientes del alumno.')
                ->visible(fn ($get) => !empty($get('student_id')))
                ->schema([
                    Forms\Components\CheckboxList::make('payment_order_ids')
                        ->label('')
                        ->options(fn ($get) =>
                            \App\Models\PaymentOrder::where('student_id', $get('student_id'))
                                ->whereIn('status', ['pending', 'partial', 'overdue'])
                                ->with('concept')
                                ->get()
                                ->mapWithKeys(fn ($o) => [
                                    $o->id => "{$o->folio} — {$o->concept->name} | Saldo: \$" . number_format($o->balance, 2) . " | Vence: " . ($o->due_date?->format('d/m/Y') ?? '—')
                                ])
                        )
                        ->columns(1)
                        ->dehydrated(false),
                ]),

            Forms\Components\Section::make('Extensión de fecha')
                ->visible(fn ($get) => in_array($get('type'), ['credit_extension', 'both']))
                ->schema([
                    Forms\Components\DatePicker::make('original_due_date')->label('Fecha original vencimiento'),
                    Forms\Components\DatePicker::make('new_due_date')->label('Nueva fecha vencimiento'),
                    Forms\Components\TextInput::make('extra_days')->label('Días extra')->numeric(),
                ])->columns(3),

            Forms\Components\Section::make('Parcialidades')
                ->visible(fn ($get) => in_array($get('type'), ['installment_plan', 'both']))
                ->schema([
                    Forms\Components\TextInput::make('installments_count')
                        ->label('Número de parcialidades')->numeric()->live(),
                    Forms\Components\TextInput::make('installment_amount')
                        ->label('Monto por parcialidad')->numeric()->prefix('$')->live(),
                    Forms\Components\DatePicker::make('first_installment_date')
                        ->label('Fecha primera parcialidad'),
                    Forms\Components\Placeholder::make('_parcialidades_preview')
                        ->label('Total en parcialidades')
                        ->content(fn ($get): string =>
                            '$' . number_format(
                                (float)($get('installments_count') ?? 0) * (float)($get('installment_amount') ?? 0),
                                2
                            )
                        ),
                ])->columns(4),

            Forms\Components\Section::make('Montos y términos')->schema([
                Forms\Components\TextInput::make('total_amount')->label('Monto total del convenio')->required()->numeric()->prefix('$'),
                Forms\Components\Textarea::make('terms')->label('Términos y condiciones')->rows(3),
                Forms\Components\Textarea::make('notes')->label('Notas internas')->rows(2),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('folio')->label('Folio')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('student.full_name')->label('Alumno')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'credit_extension' => 'Ext. crédito',
                        'installment_plan' => 'Parcialidades',
                        'both'             => 'Ambos',
                        default            => $state,
                    }),
                Tables\Columns\TextColumn::make('total_amount')->label('Total')->money('MXN'),
                Tables\Columns\TextColumn::make('paid_amount')->label('Pagado')->money('MXN'),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge()
                    ->color(fn ($state) => match($state) {
                        'pending_approval' => 'warning',
                        'active'           => 'success',
                        'completed'        => 'gray',
                        'defaulted'        => 'danger',
                        default            => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending_approval' => 'Pend. aprobación',
                        'active'           => 'Activo',
                        'completed'        => 'Completado',
                        'defaulted'        => 'En mora',
                        'cancelled'        => 'Cancelado',
                        default            => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->date('d/m/Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Estado')
                    ->options(['pending_approval'=>'Pend. aprobación','active'=>'Activo','completed'=>'Completado','defaulted'=>'En mora']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAgreements::route('/'),
            'create' => Pages\CreateAgreement::route('/create'),
            'edit'   => Pages\EditAgreement::route('/{record}/edit'),
            'view'   => Pages\ViewAgreement::route('/{record}'),
        ];
    }
}
