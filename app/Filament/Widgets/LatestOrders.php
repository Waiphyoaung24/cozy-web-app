<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Order;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrders extends BaseWidget
{
    protected static ?int $sort = 2; // To control widget order on dashboard
   
    public  function getColumnSpan(): int|string|array
    {
        return 3; // Or 'full', ['default' => 2, 'md' => 3], etc.
    }
    
    protected function getTableQuery() :\Illuminate\Database\Eloquent\Builder | \Illuminate\Database\Eloquent\Relations\Relation | null
    {
    return Order::with('customer')->withCount('items')->latest()->limit(5);
    }
    

    protected function getTableColumns(): array
    {
        return [
            
            Tables\Columns\TextColumn::make('id')
                ->label('Number')->alignCenter(),
            Tables\Columns\TextColumn::make('customer.name')
                ->label('Customer')->alignCenter(),
            Tables\Columns\BadgeColumn::make('status')
                ->colors([
                    'success' => 'shipped',
                    'danger' => 'cancelled',
                    'warning' => 'processing',
                    'info' => 'new',
                ])->alignCenter(),
            Tables\Columns\TextColumn::make('total_price')
                ->label('Total price')->money('thb')->alignCenter(),

            Tables\Columns\TextColumn::make('items_count')
                ->label('Items')->alignCenter()->formatStateUsing(fn ($state, $record) => $record->items()->count()),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Order Date')
                ->date('M d, Y')
                ->sortable()->alignCenter(),
        ];
    }
    
   
   
}