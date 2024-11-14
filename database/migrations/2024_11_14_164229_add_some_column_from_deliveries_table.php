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
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn('delivery_method');
            $table->unsignedInteger('order_id')->after('delivery_id');
            $table->unsignedInteger('delivery_method_id')->after('delivery_id');
            $table->enum('delivery_status', ['pending', 'shipping', 'delivered'])->default('pending')->after('delivery_fee');
            $table->string('delivery_description')->nullable()->after('delivery_status');
            $table->string('delivery_tracking_number')->nullable()->after('delivery_status');
            $table->timestamp('delivery_shipped_at')->nullable()->after('delivery_status');
            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
            $table->foreign('delivery_method_id')->references('delivery_method_id')->on('delivery_methods')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('delivery_method');
            $table->dropForeign(['delivery_method_id']);
            $table->dropColumn('delivery_method_id');
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
            $table->dropColumn('delivery_status');
            $table->dropColumn('delivery_description');
            $table->dropColumn('delivery_tracking_number');
            $table->dropColumn('delivery_shipped_at');
        });
    }
};
