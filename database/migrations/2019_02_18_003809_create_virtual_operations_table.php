<?php

use App\Currency;
use App\Order;
use App\Product;
use App\VirtualOperation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVirtualOperationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('virtual_operations', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('type', ['C', 'D']);
            $table->string('storage_type');
            $table->smallInteger('storage_id')->unsigned();
            $table->string('operable_type');
            $table->smallInteger('operable_id')->unsigned();
            $table->string('owner_type');
            $table->smallInteger('owner_id')->unsigned();
            $table->float('quantity');
            $table->smallInteger('user_id')->unsigned()->nullable();
            $table->smallInteger('is_reservation')->default(0);
            $table->text('comment');
            $table->timestamps();
        });


        Order::all()
            ->each(
                function (Order $order) {
                    /**
                     * @var \App\OrderDetail $orderDetail
                     */
                    foreach ($order->orderDetails as $orderDetail) {
                        if ($orderDetail->currentState()->need_payment) {
                            VirtualOperation::create([
                                'type' => 'D',
                                'storage_type' => Order::class,
                                'storage_id' => $order->id,
                                'operable_type' => Product::class,
                                'operable_id' => $orderDetail->product->id,
                                'owner_type' => Order::class,
                                'owner_id' => $order->id,
                                'quantity' => 1,
                                'user_id' => null,
                                'is_reservation' => 0,
                                'comment' => 'Initial correction',
                            ]);

                        }
                    }

                    if (!$order->currentState()->check_payment) {
                        $orderCurrencies = $order
                            ->orderDetails()
                            ->select('currency_id')
                            ->distinct()
                            ->get()
                            ->map(function ($currency_id) {
                                return Currency::find($currency_id)->first();
                            });

                        /**
                         * @var Currency $currency
                         */
                        foreach ($orderCurrencies as $currency) {

                            $balance = $order->getOrderBalance($currency);

                            if ($balance > 0) {
                                VirtualOperation::create([
                                    'type' => 'D',
                                    'storage_type' => Order::class,
                                    'storage_id' => $order->id,
                                    'operable_type' => Currency::class,
                                    'operable_id' => $currency->id,
                                    'owner_type' => Order::class,
                                    'owner_id' => $order->id,
                                    'quantity' => $balance,
                                    'user_id' => null,
                                    'is_reservation' => 0,
                                    'comment' => 'Initial correction',
                                ]);
                            }

                        }

                    }
                }
            );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('virtual_operations');
    }
}
