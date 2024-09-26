<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSomeColumnsToApprovedRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::table('approved_requests', function (Blueprint $table) {
            $table->enum('allocated', ['yes', 'no'])->default('no');
            $table->enum('allocation_type', ['online', 'physical'])->nullable();
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
            //
        });
    }
}
