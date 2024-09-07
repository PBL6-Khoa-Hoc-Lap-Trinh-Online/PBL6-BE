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
        Schema::create('diseases', function (Blueprint $table) {
            // $table->id();
            $table->increments('disease_id');
            $table->string('disease_name')->index();
            $table->string('disease_thumbnail')->nullable();
            $table->longText('general_overview')->nullable();
            $table->longText('cause')->nullable();
            $table->longText('risk_subjects')->nullable();
            $table->longText('diagnosis')->nullable();
            $table->longText('treatment_method')->nullable();
            $table->boolean('disease_is_delete')->default(0);
            $table->boolean('disease_is_show')->default(0);
            $table->timestamp('disease_created_at')->nullable();
            $table->timestamp('disease_updated_at')->nullable();
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('diseases');
    }
};
