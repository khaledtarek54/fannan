<?php

namespace Database\Seeders;

use App\Enums\OrderType;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\City;
use App\Models\Order;
use App\Models\OrderDate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates fully-populated demo orders so the invoice can be tested end-to-end locally:
 *
 *   php artisan db:seed --class=Database\\Seeders\\InvoiceDemoSeeder
 *
 * Two orders are created (both idempotent — safe to re-run):
 *   1. "Test Event"        — cost 0, so every total shows 0 EGP (mirrors the invoice mock-up).
 *   2. "Grand Opening Gala" — cost 1500 with a 100 discount, so Tax / VAT / Total actually populate.
 *
 * It prints each order id and a ready-to-use preview URL.
 */
class InvoiceDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Tax / VAT rates the invoice totals are computed from — seeded only if the SettingSeeder
        // hasn't already set them, so a fresh DB still produces sensible numbers.
        $this->ensureSetting('tax', 10);
        $this->ensureSetting('vat', 14);

        $client = User::firstOrCreate(
            ['email' => 'khaled-hossam@outlook.com'],
            [
                'name' => 'Khaled',
                'phone' => '1020700343',
                'role' => UserRole::CLIENT->value,
                'is_verified' => true,
                'completed_profile' => true,
                'email_verified_at' => now(),
                'password' => 'password', // hashed by the User model mutator
            ],
        );

        $artist = User::firstOrCreate(
            ['email' => 'alberto@fannan.ai'],
            [
                'name' => 'Alberto',
                'phone' => '1112223334',
                'role' => UserRole::ARTIST->value,
                'is_verified' => true,
                'completed_profile' => true,
                'email_verified_at' => now(),
                'password' => 'password',
            ],
        );

        $city = City::firstOrCreate(['name' => '6th Of October']);

        $address = Address::firstOrCreate(
            ['user_id' => $client->id, 'name' => 'Giza Al Hosary Mosque, Block 21, 6th Of October'],
            [
                'city_id' => $city->id,
                'description' => 'Giza Al Hosary Mosque, Block 21, 6th Of October',
                'latitude' => 29.9765,
                'longitude' => 30.9385,
            ],
        );

        // 1. Zero-cost order — matches the original invoice mock-up (all totals 0 EGP).
        $zero = $this->demoOrder($client, $artist, $address, [
            'name' => 'Test Event',
            'description' => 'Test Description',
            'cost' => 0,
            'coupon_amount' => 0,
            'start_date' => '2026-09-01', 'end_date' => '2026-09-16',
        ]);

        // 2. Priced order — cost 1500, 100 discount, so Tax (10%) / VAT (14%) / Total populate.
        $priced = $this->demoOrder($client, $artist, $address, [
            'name' => 'Grand Opening Gala',
            'description' => 'Live oud performance — 2 sets',
            'cost' => 1500,
            'coupon_amount' => 100,
            'start_date' => '2026-10-05', 'end_date' => '2026-10-05',
            'start_time' => '20:00:00', 'end_time' => '23:00:00',
        ]);

        $this->command?->info('✅ Demo invoice orders ready (client Khaled #' . $client->id . ', artist Alberto #' . $artist->id . ').');
        foreach ([['Zero-cost', $zero], ['Priced', $priced]] as [$label, $order]) {
            $this->command?->info("   {$label}  order #{$order->id}  ->  " . url("/invoice/preview/{$order->id}"));
        }
        $this->command?->info('   Add ?pdf=1 to any preview URL to see the real PDF.');
    }

    /**
     * Create (idempotently) one demo order plus its single date row.
     */
    private function demoOrder(User $client, User $artist, Address $address, array $attrs): Order
    {
        $order = Order::firstOrCreate(
            ['client_id' => $client->id, 'artist_id' => $artist->id, 'name' => $attrs['name']],
            [
                'type' => OrderType::DIRECT->value,
                'address_id' => $address->id,
                'description' => $attrs['description'],
                'cost' => $attrs['cost'],
                'coupon_amount' => $attrs['coupon_amount'],
                'is_paid' => true, // -> Payment Status: PAID
            ],
        );

        OrderDate::firstOrCreate(
            ['order_id' => $order->id],
            [
                'start_date' => $attrs['start_date'],
                'end_date' => $attrs['end_date'],
                'start_time' => $attrs['start_time'] ?? '15:00:00',
                'end_time' => $attrs['end_time'] ?? '15:00:00',
                'is_completed' => false,
            ],
        );

        return $order;
    }

    private function ensureSetting(string $type, int $value): void
    {
        if (!Setting::query()->where('type', $type)->exists()) {
            Setting::create(['type' => $type, 'value' => ['en' => $value, 'ar' => $value]]);
        }
    }
}
