<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvestorPropertyIdToApprovedRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::table('approved_requests', function (Blueprint $table) {
            $table->uuid('investor_property_id')->nullable()->after('request_id');
            $table->foreign('investor_property_id')->references('id')->on('investors_properties');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('approved_requests', function (Blueprint $table) {
            $table->dropForeign(['investor_property_id']);
            $table->dropColumn('investor_property_id');

        });
    }
}
