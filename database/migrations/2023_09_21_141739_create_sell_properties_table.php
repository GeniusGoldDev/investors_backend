<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sell_properties', function (Blueprint $table) {
            $table->id();
            $table->uuid('approved_request_id');
            $table->uuid('marketer_id');
            $table->uuid('user_id');
            $table->string('amount');
            $table->enum('status', ['pending', 'sold', 'completed'])->default('pending');
            $table->enum('is_notified', ['true', 'false'])->default('false');
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
        Schema::dropIfExists('sell_properties');
    }
}
