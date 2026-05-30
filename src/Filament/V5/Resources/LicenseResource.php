<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Filament\V5\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Kurt\Modules\Licensing\Enums\LicenseStatus;
use Kurt\Modules\Licensing\Enums\PolicyType;
use Kurt\Modules\Licensing\Filament\V5\Resources\LicenseResource\Pages;
use Kurt\Modules\Licensing\Server\Models\License;

class LicenseResource extends Resource
{
    /** @var array<string, string> */
    public const STATUS_OPTIONS = [
        'active' => 'Active',
        'suspended' => 'Suspended',
        'expired' => 'Expired',
        'revoked' => 'Revoked',
    ];

    /** @var array<string, string> */
    public const POLICY_OPTIONS = [
        'perpetual' => 'Perpetual',
        'subscription' => 'Subscription',
        'updates_window' => 'Updates window',
    ];

    protected static ?string $model = License::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|\UnitEnum|null $navigationGroup = 'Licensing';

    protected static ?string $recordTitleAttribute = 'key_prefix';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Licensee')
                    ->schema([
                        TextInput::make('licensee_email')
                            ->email()
                            ->required(),
                        Select::make('product_id')
                            ->relationship('product', 'slug')
                            ->searchable()
                            ->required(),
                        TextInput::make('licensee_name'),
                        TextInput::make('licensee_company'),
                    ])
                    ->columns(2),

                Section::make('Policy & status')
                    ->schema([
                        Select::make('status')
                            ->options(self::STATUS_OPTIONS)
                            ->required(),
                        Select::make('policy_type')
                            ->options(self::POLICY_OPTIONS)
                            ->required(),
                        TextInput::make('max_activations')
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                        TextInput::make('key_prefix')
                            ->disabled()
                            ->dehydrated(false),
                        DateTimePicker::make('expires_at')
                            ->seconds(false),
                        DateTimePicker::make('updates_until')
                            ->seconds(false),
                    ])
                    ->columns(2),

                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key_prefix')
                    ->label('Key')
                    ->badge()
                    ->copyable(),
                TextColumn::make('licensee_email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.slug')
                    ->label('Product')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (LicenseStatus $state): string => ucfirst($state->value))
                    ->color(fn (LicenseStatus $state): string => match ($state) {
                        LicenseStatus::Active => 'success',
                        LicenseStatus::Suspended => 'warning',
                        LicenseStatus::Expired => 'gray',
                        LicenseStatus::Revoked => 'danger',
                    }),
                TextColumn::make('policy_type')
                    ->badge()
                    ->formatStateUsing(fn (PolicyType $state): string => ucfirst(str_replace('_', ' ', $state->value))),
                TextColumn::make('max_activations')
                    ->label('Seats')
                    ->numeric(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(self::STATUS_OPTIONS),
                SelectFilter::make('policy_type')
                    ->options(self::POLICY_OPTIONS),
            ])
            ->actions([
                EditAction::make(),
                Action::make('revoke')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->requiresConfirmation()
                    ->visible(fn (License $record): bool => $record->status !== LicenseStatus::Revoked)
                    ->action(fn (License $record) => $record->update([
                        'status' => LicenseStatus::Revoked->value,
                        'revoked_at' => now(),
                    ])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * @return array<class-string, mixed>
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLicenses::route('/'),
            'edit' => Pages\EditLicense::route('/{record}/edit'),
        ];
    }
}
