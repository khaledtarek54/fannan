<?php

namespace App\Filament\Resources\DirectOrderResource\Pages;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Filament\Resources\DirectOrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderDate;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateDirectOrder extends CreateRecord
{
    protected static string $resource = DirectOrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Assemble a complete, listable direct order the way the app's OrderRepository does:
     * generate the "D" number, persist the chosen subcategory + date window as their own rows,
     * and set the initial ARTIST_PENDING status. (category_id/start_date/end_date/subcategory_id
     * are form-only — they are not Order columns.)
     */
    public function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            /** @var Order $order */
            $order = Order::create([
                'type'        => OrderType::DIRECT->value,
                'number'      => 'D' . (Order::count() + 1),
                'client_id'   => $data['client_id'],
                'artist_id'   => $data['artist_id'],
                'address_id'  => $data['address_id'],
                'description' => $data['description'],
                'cost'        => $data['cost'] ?? null,
            ]);

            // NB: orders has no city_id column (dropped in 2024_08_19_add_address_id_to_orders_table);
            // the order's city is derived through address -> city.

            if (!empty($data['subcategory_id'])) {
                OrderCategory::create([
                    'order_id'       => $order->id,
                    'subcategory_id' => $data['subcategory_id'],
                ]);
            }

            if (!empty($data['start_date']) && !empty($data['end_date'])) {
                $start = Carbon::parse($data['start_date']);
                $end   = Carbon::parse($data['end_date']);
                OrderDate::create([
                    'order_id'     => $order->id,
                    'start_date'   => $start->toDateString(),
                    'end_date'     => $end->toDateString(),
                    'start_time'   => $start->toTimeString(),
                    'end_time'     => $end->toTimeString(),
                    'is_completed' => false,
                ]);
            }

            $order->setStatus(OrderStatus::ARTIST_PENDING->value);

            return $order;
        });
    }
}
