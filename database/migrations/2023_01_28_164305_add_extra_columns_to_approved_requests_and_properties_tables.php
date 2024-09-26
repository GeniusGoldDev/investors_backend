<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraColumnsToApprovedRequestsAndPropertiesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('approved_requests', function (Blueprint $table) {
            $table->enum("amount_used", ["default", "custom"])->default("default");
        });

        Schema::table('investors_properties', function (Blueprint $table) {
            $table->json("square_meters_info")->nullable();
        });
        Schema::table('requests', function (Blueprint $table) {
            $table->string("square_meter_price")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('approved_requests_and_properties_tables', function (Blueprint $table) {
            //
        });
    }
}
