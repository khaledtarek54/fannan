<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

class CheckOrderCompleteCommand extends Command
{

    public function __construct(protected readonly OrderService $orderService)
    {
        parent::__construct();
    }


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-order-complete-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is task schedule for check today at 12 AM if complete or not, with notify client or artist';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->orderService->notifyCompletedOrders();
    }
}
