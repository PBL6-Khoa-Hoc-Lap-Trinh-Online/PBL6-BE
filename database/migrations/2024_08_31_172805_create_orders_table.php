<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('order_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('receiver_address_id');
            $table->decimal('order_total_amount',15,2);
            $table->string('payment_method');
            $table->string('order_status');//pending, confirmed, shipped, delivered, cancelled
            $table->string('order_note')->nullable();
            $table->datetime('order_at');
            $table->datetime('confirmed_at')->nullable();
            $table->datetime('shipped_at')->nullable();
            $table->datetime('delivered_at')->nullable();
            $table->datetime('cancelled_at')->nullable();
            $table->boolean('order_is_delete')->default(0);
            $table->timestamp('order_created_at')->nullable();
            $table->timestamp('order_updated_at')->nullable();
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('receiver_address_id')->references('receiver_address_id')->on('receiver_addresses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
