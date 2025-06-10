<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Customer;

class StatsAppOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Customers', Customer::count())
                ->description('All registered customers')
                ->color('primary'),
            Stat::make('Total Products', \App\Models\Product::count())
                ->description('All products in inventory')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info')
                ->chart([5, 10, 8, 12, 15, 20, 18]),
            Stat::make('Total Orders', \App\Models\Order::count())
                ->description('All orders placed')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success')
                ->chart([2, 4, 6, 8, 10, 12, 14]),
            Stat::make('Total Categories', \App\Models\Category::count())
                ->description('Product categories')
                ->descriptionIcon('heroicon-m-rectangle-group')
                ->color('warning')
                ->chart([1, 2, 2, 3, 3, 4, 4]),
            Stat::make('Total Users', \App\Models\User::count())
                ->description('System users')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('gray')
                ->chart([1, 1, 2, 2, 3, 3, 4]),
            // Add more Stat::make() as needed for other statuses
        ];
    }
}
