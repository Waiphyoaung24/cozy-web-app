<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Columns;
use Filament\Forms\Components;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'id';
    protected static ?string $navigationGroup = 'POS Management';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Components\Section::make('Order Details')
                ->description('Fill in the customer and order information.')
                ->schema([
                    Components\Grid::make(2)
                        ->schema([
                            Components\Select::make('customer_id')
                                ->label('Customer')
                                ->relationship('customer', 'name')
                                ->searchable()
                                ->required()
                                ->helperText('Select the customer placing this order.'),
                            Components\Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'cash' => 'Cash',
                                    'card' => 'Card',
                                    'online' => 'Online'
                                ])
                                ->required()
                                ->helperText('How will the customer pay?'),
                            Components\Select::make('status')
                                ->label('Order Status')
                                ->options([
                                    'new' => 'New',
                                    'processing' => 'Processing',
                                    'shipped' => 'Shipped',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->required()
                                ->helperText('Track the current status of the order.'),
                            Components\TextInput::make('total_price')
                                ->label('Total Price')
                                ->numeric()
                                ->step(0.01)
                                ->inputMode('decimal')
                                ->disabled()
                                ->dehydrated(true)
                                ->default(0)
                                ->reactive()
                                ->afterStateHydrated(function ($component, $state, $record) {
                                    if ($record) {
                                        $component->state(number_format($record->total_price, 2, '.', ''));
                                    }
                                })
                                ->helperText('Automatically calculated from products below.'),
                        ]),
                ]),
            Components\Section::make('Products in Order')
                ->description('Add one or more products to this order.')
                ->schema([
                    Components\Repeater::make('items')
                        ->label('Order Items')
                        ->relationship()
                        ->schema([
                            Components\Grid::make(3)
                                ->schema([
                                    Components\Select::make('product_id')
                                        ->label('Product')
                                        ->relationship('product', 'name')
                                        ->searchable()
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $product = \App\Models\Product::find($state);
                                            if ($product) {
                                                $set('price', number_format($product->price, 2, '.', ''));
                                            }
                                        })
                                        ->placeholder('Select product'),
                                    Components\TextInput::make('quantity')
                                        ->label('Quantity')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $price = $get('price') ?? 0;
                                            $set('price', number_format($price, 2, '.', ''));
                                        })
                                        ->placeholder('e.g. 2'),
                                    Components\TextInput::make('price')
                                        ->label('Unit Price')
                                        ->numeric()
                                        ->step(0.01)
                                        ->inputMode('decimal')
                                        ->required()
                                        ->disabled()
                                        ->dehydrated(true)
                                        ->placeholder('Auto'),
                                ]),
                        ])
                        ->createItemButtonLabel('Add Product')
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $total = 0;
                            foreach ($state as $item) {
                                $total += (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1);
                            }
                            $set('total_price', number_format($total, 2, '.', ''));
                        })
                        ->helperText('Add all products and their quantities for this order.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('id')
                    ->label('Order')
                    ->alignCenter()
                    ->searchable(),
                Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                Columns\TextColumn::make('total_price')
                    ->label('Total Price')
                    ->money('USD', true)
                    ->alignRight(),
                Columns\TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'cash' => 'success',
                        'card' => 'info',
                        'online' => 'primary',
                        default => 'secondary',
                    })
                    ->alignCenter(),
                Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'new',
                        'warning' => 'processing',
                        'success' => 'shipped',
                        'danger' => 'cancelled',
                    ])
                    ->alignCenter(),
                Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date('M d, Y H:i')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Order Status')
                    ->options([
                        'new' => 'New',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Components\DatePicker::make('created_from')->label('From'),
                        Components\DatePicker::make('created_until')->label('To'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q) => $q->whereDate('created_at', '>=', $data['created_from']))
                            ->when($data['created_until'], fn ($q) => $q->whereDate('created_at', '<=', $data['created_until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }


 
 public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'user.name']; // Or any other searchable fields
    }

 // ✅ Corrected: Return meaningful info for global search results
 public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'User' => optional($record->user)->name,
            'Total Price' => $record->total_price,
            'Payment Method' => $record->payment_method,
        ];
    }
}
