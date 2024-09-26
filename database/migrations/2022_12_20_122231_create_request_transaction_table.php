<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('request_transaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transaction_id');
            $table->uuid('investor_id');
            $table->uuid('approved_request_id');
            $table->integer('amount');
            $table->enum('status', ['pending','approved','failed','soled', 'removed'])->default('pending');
            $table->enum('is_reciept_received', ['yes','no'])->default('no');
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->foreign('investor_id')->references('id')->on('users');
            $table->foreign('approved_request_id')->references('id')->on('approved_requests');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_transaction');
    }
}
