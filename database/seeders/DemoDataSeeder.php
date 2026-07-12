<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\PriceRange;
use App\Models\Rating;
use App\Models\Support;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * [DASH-P3] Local-only demo data so the admin panel + dashboard widgets have realistic content to
 * explore (orders across months, paid GMV, pending payouts, coupons of every validity state, ratings,
 * open support tickets, price ranges). Additive — safe to run on top of existing data.
 *
 * Run: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Clients & artists (completed profile so the user KPIs count them).
        $clients = User::factory()->count(8)->client()->create();
        $artists = User::factory()->count(6)->artist()->create();
        foreach ($clients->merge($artists) as $u) {
            $u->forceFill(['completed_profile' => 1])->save();
        }

        // Wallet: income for every artist, plus a few pending + completed payouts.
        foreach ($artists as $artist) {
            Transaction::factory()->income()->create(['user_id' => $artist->id, 'amount' => rand(400, 3000)]);
        }
        foreach ($artists->take(3) as $artist) {
            Transaction::factory()->withdraw()->create(['user_id' => $artist->id, 'amount' => rand(50, 300), 'is_completed' => 0]);
        }
        foreach ($artists->skip(3)->take(2) as $artist) {
            Transaction::factory()->withdraw()->create(['user_id' => $artist->id, 'amount' => rand(50, 200), 'is_completed' => 1]);
        }

        // Orders spread across the last 6 months, mix of direct/bidding, ~half paid.
        $statuses = [OrderStatus::NEW, OrderStatus::ACCEPTED, OrderStatus::COMPLETED, OrderStatus::IN_PAYMENT, OrderStatus::ARTIST_PENDING];
        for ($monthsAgo = 0; $monthsAgo < 6; $monthsAgo++) {
            foreach (range(1, rand(2, 6)) as $ignored) {
                $order = Order::factory()->create([
                    'client_id' => $clients->random()->id,
                    'artist_id' => $artists->random()->id,
                    'type' => rand(0, 1) ? OrderType::DIRECT->value : OrderType::BIDDING->value,
                    'cost' => rand(150, 4000),
                ]);
                $order->forceFill([
                    'is_paid' => (bool) rand(0, 1),
                    'created_at' => now()->subMonths($monthsAgo)->subDays(rand(0, 24)),
                ])->save();
                $order->setStatus($statuses[array_rand($statuses)]->value);
            }
        }

        // Ratings — some with written reviews, some stars-only.
        foreach ($artists as $artist) {
            foreach (range(1, rand(1, 3)) as $ignored) {
                Rating::create([
                    'client_id' => $clients->random()->id,
                    'artist_id' => $artist->id,
                    'stars' => rand(3, 5),
                    'notes' => rand(0, 1) ? 'Great work — professional and on time.' : null,
                ]);
            }
        }

        // Coupons covering every validity state (active / scheduled / expired).
        Coupon::create(['type' => CouponType::PERCENTAGE->value, 'amount' => 15, 'code' => 'DEMO15', 'start_date' => now()->subDays(5), 'end_date' => now()->addDays(20)]);
        Coupon::create(['type' => CouponType::FIXED->value, 'amount' => 50, 'code' => 'DEMO50', 'start_date' => now()->addWeek(), 'end_date' => now()->addMonth()]);
        Coupon::create(['type' => CouponType::PERCENTAGE->value, 'amount' => 10, 'code' => 'EXPIRED10', 'start_date' => now()->subMonths(2), 'end_date' => now()->subWeek()]);

        // Price ranges.
        foreach ([[0, 500], [500, 2000], [2000, 10000]] as [$from, $to]) {
            PriceRange::firstOrCreate(['from' => $from, 'to' => $to]);
        }

        // A few open support tickets.
        foreach ($clients->take(3) as $client) {
            $ticket = new Support([
                'user_id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'description' => 'Demo support request — need help with an order.',
                'is_complete' => 0,
            ]);
            $ticket->save();
        }
    }
}
