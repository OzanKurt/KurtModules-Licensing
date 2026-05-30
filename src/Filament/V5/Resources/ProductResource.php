<?php

declare(strict_types=1);

namespace Kurt\Modules\Licensing\Filament\V5\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Kurt\Modules\Licensing\Filament\V5\Resources\ProductResource\Pages;
use Kurt\Modules\Licensing\Server\Models\Product;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|\UnitEnum|null $navigationGroup = 'Licensing';

    protected static ?string $recordTitleAttribute = 'slug';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        TagsInput::make('composer_packages')
                            ->placeholder('vendor/package')
                            ->helperText('Composer packages this product unlocks.')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Default policy')
                    ->schema([
                        Select::make('default_policy.type')
                            ->label('Policy')
                            ->options([
                                'perpetual' => 'Perpetual',
                                'subscription' => 'Subscription',
                                'updates_window' => 'Updates window',
                            ])
                            ->default('perpetual'),
                        TextInput::make('default_policy.max_activations')
                            ->label('Seats')
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('licenses_count')
                    ->counts('licenses')
                    ->label('Licenses')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
