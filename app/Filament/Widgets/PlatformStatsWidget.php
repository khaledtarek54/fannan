<?php

namespace App\Filament\Widgets;

use App\Enums\TransactionType;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\SupportResource;
use App\Filament\Resources\WithdrawTransactionResource;
use App\Models\Order;
use App\Models\Support;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Business KPIs for the dashboard: order volume, paid GMV, and the two operational queues
 * (pending withdrawals, open support) so admins see the backlog on login. All counts/sums run
 * in SQL.
 */
class PlatformStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalOrders = Order::query()->count();
        $paidOrders = Order::query()->where('is_paid', 1)->count();
        $gmv = (float) Order::query()->where('is_paid', 1)->sum('cost');

        $pendingWithdrawals = Transaction::query()
            ->where('type', TransactionType::WITHDRAW->value)
            ->where('is_completed', 0)
            ->count();

        $openSupport = Support::query()->where('is_complete', 0)->distinct('user_id')->count('user_id');

        // [DASH-P3] each card links through to the list it summarizes, so the dashboard is a jumping-off
        // point for the backlog rather than a dead end.
        return [
            Stat::make(__('app.orders'), $totalOrders)
                ->description($paidOrders . ' ' . __('app.paid'))
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->url(InvoiceResource::getUrl('index')),
            Stat::make(__('app.total') . ' (' . __('app.paid') . ')', money($gmv))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->url(InvoiceResource::getUrl('index')),
            Stat::make(__('app.withdraw_talents'), $pendingWithdrawals)
                ->icon('heroicon-o-banknotes')
                ->color($pendingWithdrawals > 0 ? 'warning' : 'gray')
                ->url(WithdrawTransactionResource::getUrl('index')),
            Stat::make(__('app.supports'), $openSupport)
                ->icon('heroicon-o-lifebuoy')
                ->color($openSupport > 0 ? 'warning' : 'gray')
                ->url(SupportResource::getUrl('index')),
        ];
    }
}
