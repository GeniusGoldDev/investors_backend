<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InvestorsProperties extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('investors_properties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->json('image')->nullable();
            $table->json('filename')->nullable();
            $table->string('property_link')->nullable();
            $table->integer('amount');
            $table->string('location');
            $table->string('description');
            $table->enum('status', ['active', 'inactive']);
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
        Schema::dropIfExists('investors_properties');

    }
}
