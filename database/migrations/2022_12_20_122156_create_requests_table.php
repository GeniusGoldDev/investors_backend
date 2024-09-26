<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('investor_id');
            $table->foreign('investor_id')->references('id')->on('users');
            $table->uuid('investor_property_id')->nullable();
            $table->foreign('investor_property_id')->references('id')->on('investors_properties');
            $table->string('name');
            $table->enum('status', ['approved', 'pending', 'processing', 'completed'])->default('pending');
            $table->enum('is_special', ['yes', 'no'])->default('no');

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
        Schema::dropIfExists('requests');
    }
}
