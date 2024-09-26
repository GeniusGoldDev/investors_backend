<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestConversationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('request_conversation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('request_id');
            $table->uuid('convo_1_id');
            $table->uuid('convo_2_id');
            $table->string('convo_1')->nullable();
            $table->string('convo_2')->nullable();
            $table->integer('record_order')->nullable();
           
            $table->enum('status', ['active', 'inactive', 'closed'])->default('active');
            $table->foreign('convo_1_id')->references('id')->on('users');
            $table->foreign('convo_2_id')->references('id')->on('users');
            $table->foreign('request_id')->references('id')->on('requests');
            
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
        Schema::dropIfExists('request_conversation');
    }
}
