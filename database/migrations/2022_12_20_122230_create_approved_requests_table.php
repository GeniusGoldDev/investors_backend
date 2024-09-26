<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovedRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('approved_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('request_id')->nullable();
            $table->foreign('request_id')->references('id')->on('requests');
            $table->integer('amount');
            $table->enum('status', ['processing', 'completed'])->default('processing');
            $table->enum('contract_recieved', ['pending', 'sent', 'submitted'])->default('pending');
            
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
        Schema::dropIfExists('approved_requests');
    }
}
