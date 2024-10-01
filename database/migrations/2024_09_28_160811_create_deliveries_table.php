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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->increments('delivery_id');
            $table->enum('delivery_method',['GHN','GHTK', 'VIETTEL','AT_PHARMACITY','SHIPPER'])->default('GHN');
            $table->decimal('delivery_fee', 15, 2)->nullable();
            $table->string('delivery_tracking_number')->nullable();
            $table->string('delivery_description')->nullable();
            $table->timestamp('delivery_shipped_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('deliveries');
    }
};
