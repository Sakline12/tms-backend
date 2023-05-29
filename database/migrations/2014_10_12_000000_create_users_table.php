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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('address');
            $table->string('phone');
            // $table->bigInteger('designation_id')->unsigned();
            // $table->foreign('designation_id')->references('id')->on('designations')->onDelete('cascade');
            // $table->bigInteger('department_id')->unsigned();
            // $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->enum('type', ['Admin', 'Employee']);
            $table->boolean('isActive')->default(false);
            // $table->string('image')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
