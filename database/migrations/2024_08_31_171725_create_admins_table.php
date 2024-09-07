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
        Schema::create('admins', function (Blueprint $table) {
            $table->increments('admin_id');
            $table->string('admin_fullname');
            $table->string('admin_email')->unique();
            $table->string('admin_password');
            $table->string('admin_avatar')->nullable();
            $table->boolean('admin_is_admin')->default(0);//0 là admin, 1 là supper_admin
            $table->boolean('admin_is_delete')->default(0);//0 là active, 1 là delete
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('admin_created_at')->nullable();
            $table->timestamp('admin_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admins');
    }
};