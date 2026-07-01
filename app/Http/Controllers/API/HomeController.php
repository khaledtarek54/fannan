<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Ad\AdResource;
use App\Http\Resources\Artist\HomeArtistResource;
use App\Http\Resources\ArtistOrderHomeResource;
use App\Http\Resources\Order\BiddingOrderResource;
use App\Models\User;
use App\Services\AdService;
use App\Services\ArtistService;
use App\Services\BiddingOrderService;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        protected readonly AdService           $adService,
        protected readonly ArtistService       $artistService,
        protected readonly OrderService        $orderService,
        protected readonly BiddingOrderService $biddingOrderService,
        protected readonly NotificationService $notificationService,
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $adsRequest = $request->input('ads', []);
        $topRequest = $request->input('top_artists', []);
        $latestRequest = $request->input('latest_artists', []);
        $ads = AdResource::collection($this->adService->index($adsRequest));
        $top = HomeArtistResource::collection($this->artistService->index($topRequest));
        $latest = HomeArtistResource::collection($this->artistService->index($latestRequest));
        $unreadNotifications = $this->notificationService->unreadNotificationsCount();
        return response()->json([
            'unread_notifications' => $unreadNotifications,
            'ads' => $ads,
            'top' => $top,
            'latest' => $latest,
        ]);
    }

    public function artistHome(Request $request): JsonResponse
    {
        $adsRequest = $request->input('ads', []);
        $acceptedOrders = $request->input('accepted_orders', []);
        $ads = AdResource::collection($this->adService->index($adsRequest));
        $orders = ArtistOrderHomeResource::collection($this->orderService->index($acceptedOrders));
        $biddings = BiddingOrderResource::collection($this->biddingOrderService->artistHomeBiddingOrders($request->all()));
        $unreadNotifications = $this->notificationService->unreadNotificationsCount();
        return response()->json([
            'unread_notifications' => $unreadNotifications,
            'ads' => $ads,
            'orders' => $orders,
            'biddings' => $biddings,
        ]);
    }
}
