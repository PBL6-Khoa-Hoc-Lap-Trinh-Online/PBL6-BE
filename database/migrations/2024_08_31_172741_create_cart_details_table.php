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
        Schema::create('cart_details', function (Blueprint $table) {
            $table->increments('cart_detail_id');
            $table->unsignedInteger('cart_id');
            $table->unsignedInteger('product_id');  
            $table->integer('cart_quantity')->default(0);//quantity of product in cart
            $table->decimal('cart_price',15,2)->default(0);//price of product in cart
            $table->foreign('cart_id')->references('cart_id')->on('carts');
            $table->foreign('product_id')->references('product_id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cart_details');
    }
};
