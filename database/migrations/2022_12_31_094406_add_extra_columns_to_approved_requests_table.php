<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraColumnsToApprovedRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::table('approved_requests', function (Blueprint $table) {
            $table->enum('key_allocated', ['no', 'yes'])->default('no');
            $table->enum('deed_of_assignment', ['assigned', 'not_assigned'])->default('not_assigned');
            $table->enum('type', ['house', 'land'])->nullable();
            
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
