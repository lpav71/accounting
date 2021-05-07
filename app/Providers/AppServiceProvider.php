<?php

namespace App\Providers;

use App\CourierTask;
use App\Observers\CourierTaskObserver;
use App\Observers\OperationObserver;
use App\Observers\OrderDetailObserver;
use App\Observers\OrderObserver;
use App\Observers\ProductExchangeObserver;
use App\Observers\ProductReturnObserver;
use App\Observers\RoutePointObserver;
use App\Observers\TaskCommentObserver;
use App\Observers\TaskObserver;
use App\Observers\UserWorkTableObserver;
use App\Observers\UtmCampaignObserver;
use App\Observers\UtmObserver;
use App\Observers\UtmSourceObserver;
use App\Observers\VirtualOperationObserver;
use App\Operation;
use App\Order;
use App\OrderDetail;
use App\ProductExchange;
use App\ProductReturn;
use App\RouteList;
use App\RoutePoint;
use App\Task;
use App\TaskComment;
use App\UserWorkTable;
use App\Utm;
use App\UtmCampaign;
use App\UtmSource;
use App\VirtualOperation;
use Config;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        App::setLocale('ru');

        Config::set('exceptions.doing.throw', true);
        Config::set('exceptions.doing.messages', []);

        Operation::observe(OperationObserver::class);
        Order::observe(OrderObserver::class);
        OrderDetail::observe(OrderDetailObserver::class);
        ProductExchange::observe(ProductExchangeObserver::class);
        ProductReturn::observe(ProductReturnObserver::class);
        RoutePoint::observe(RoutePointObserver::class);
        Task::observe(TaskObserver::class);
        TaskComment::observe(TaskCommentObserver::class);
        VirtualOperation::observe(VirtualOperationObserver::class);
        UserWorkTable::observe(UserWorkTableObserver::class);
        Utm::observe(UtmObserver::class);
        UtmCampaign::observe(UtmCampaignObserver::class);
        UtmSource::observe(UtmSourceObserver::class);
        CourierTask::observe(CourierTaskObserver::class);

        /**
         * Paginate a standard Laravel Collection.
         *
         * @param int $perPage
         * @param int $total
         * @param int $page
         * @param string $pageName
         * @return array
         */

        if (!Collection::hasMacro('paginate')) {

            Collection::macro('paginate',
                function ($perPage = 15, $page = null, $options = []) {
                    $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
                    return (new LengthAwarePaginator(
                        $this->forPage($page, $perPage)->values()->all(), $this->count(), $perPage, $page, $options))
                        ->withPath('');
                });
        }
    }
}
