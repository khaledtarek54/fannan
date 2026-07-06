<?php

namespace App\Providers;

use App\Models\Ad;
use App\Models\BiddingOrderArtist;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use App\Observers\UpdateBiddingOrderStatus;
use App\Services\Concerns\AddressRepository;
use App\Services\Concerns\AdRepository;
use App\Services\Concerns\ArtistGalleryRepository;
use App\Services\Concerns\ArtistRepository;
use App\Services\Concerns\BaseRepository;
use App\Services\Concerns\BiddingOrderArtistRepository;
use App\Services\Concerns\BiddingOrderRepository;
use App\Services\Concerns\ChatRepository;
use App\Services\Concerns\CityRepository;
use App\Services\Concerns\CouponRepository;
use App\Services\Concerns\CouponUserRepository;
use App\Services\Concerns\NotificationRepository;
use App\Services\Concerns\OrderCategoryRepository;
use App\Services\Concerns\OrderDateRepository;
use App\Services\Concerns\OrderOfferRepository;
use App\Services\Concerns\OrderPaymentTransactionRepository;
use App\Services\Concerns\OrderRepository;
use App\Services\Concerns\RatingRepository;
use App\Services\Concerns\SupportRepository;
use App\Services\Concerns\TransactionRepository;
use App\Services\Concerns\UserCategoryRepository;
use App\Services\Concerns\UserRepository;
use App\Services\Contracts\AddressRepositoryInterface;
use App\Services\Contracts\AdRepositoryInterface;
use App\Services\Contracts\ArtistGalleryRepositoryInterface;
use App\Services\Contracts\ArtistRepositoryInterface;
use App\Services\Contracts\BaseRepositoryInterface;
use App\Services\Contracts\BiddingOrderArtistRepositoryInterface;
use App\Services\Contracts\BiddingOrderRepositoryInterface;
use App\Services\Contracts\ChatRepositoryInterface;
use App\Services\Contracts\CityRepositoryInterface;
use App\Services\Contracts\CouponRepositoryInterface;
use App\Services\Contracts\CouponUserRepositoryInterface;
use App\Services\Contracts\NotificationRepositoryInterface;
use App\Services\Contracts\OrderCategoryRepositoryInterface;
use App\Services\Contracts\OrderDateRepositoryInterface;
use App\Services\Contracts\OrderOfferRepositoryInterface;
use App\Services\Contracts\OrderPaymentTransactionRepositoryInterface;
use App\Services\Contracts\OrderRepositoryInterface;
use App\Services\Contracts\RatingRepositoryInterface;
use App\Services\Contracts\SupportRepositoryInterface;
use App\Services\Contracts\TransactionRepositoryInterface;
use App\Services\Contracts\UserCategoryRepositoryInterface;
use App\Services\Contracts\UserRepositoryInterface;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider as TelescopeProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // [SECURITY][R2-C4] Model::unguard() was REMOVED. It disabled mass-assignment protection
        // for EVERY model app-wide, making each model's $fillable inert (Order.is_paid,
        // User.is_admin/role/wallet, UserTransaction.is_paid/status, Setting.value, …). Each model
        // now enforces its own $fillable again. See docs/SECURITY_ISSUES_ROUND2.md R2-C4.
        BiddingOrderArtist::observe(UpdateBiddingOrderStatus::class);


//        Model::shouldBeStrict($this->app->environment('local'));

        Filament::registerNavigationGroups([
            'Users' => NavigationGroup::make(fn() => __('app.users')),
            'Configurations' => NavigationGroup::make(fn() => __('app.configurations')),
            'Promotions' => NavigationGroup::make(fn() => __('app.promotions')),
            'Orders' => NavigationGroup::make(fn() => __('app.orders')),
            'Transactions' => NavigationGroup::make(fn() => __('app.transactions')),
            'Supports' => NavigationGroup::make(fn() => __('app.supports')),
        ]);

        FilamentAsset::register([
            Js::make('custom-script', __DIR__ . '/../../resources/js/support.js'),
        ]);

        if ($this->app->environment('local')) {
            $this->app->register(TelescopeProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        $this->app->bind(BaseRepositoryInterface::class, BaseRepository::class);
        $this->app->bind(ArtistRepositoryInterface::class, ArtistRepository::class);
        $this->app->bind(AdRepositoryInterface::class, AdRepository::class);
        $this->app->bind(ArtistGalleryRepositoryInterface::class, ArtistGalleryRepository::class);
        $this->app->bind(CouponRepositoryInterface::class, CouponRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(BiddingOrderRepositoryInterface::class, BiddingOrderRepository::class);
        $this->app->bind(OrderDateRepositoryInterface::class, OrderDateRepository::class);
        $this->app->bind(OrderOfferRepositoryInterface::class, OrderOfferRepository::class);
        $this->app->bind(RatingRepositoryInterface::class, RatingRepository::class);
        $this->app->bind(ChatRepositoryInterface::class, ChatRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
        $this->app->bind(SupportRepositoryInterface::class, SupportRepository::class);
        $this->app->bind(AddressRepositoryInterface::class, AddressRepository::class);
        $this->app->bind(UserCategoryRepositoryInterface::class, UserCategoryRepository::class);
        $this->app->bind(BiddingOrderArtistRepositoryInterface::class, BiddingOrderArtistRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(OrderPaymentTransactionRepositoryInterface::class, OrderPaymentTransactionRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(CityRepositoryInterface::class, CityRepository::class);
        $this->app->bind(CouponUserRepositoryInterface::class, CouponUserRepository::class);
        $this->app->bind(OrderCategoryRepositoryInterface::class, OrderCategoryRepository::class);


        Relation::morphMap([
            'ad' => Ad::class,
            'order' => Order::class,
            'bidding_order_artist' => BiddingOrderArtist::class,
            'user' => User::class,
            'category' => Category::class,
        ]);
    }
}
