<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSurveyPlanToApprovedRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */


    public function up()
    {
        Schema::table('approved_requests', function (Blueprint $table) {
            $table->enum('deed_of_assignment_type', ['soft_copy', 'hard_copy'])->nullable();
            $table->enum('survey_plan', ['soft_copy', 'hard_copy'])->nullable();

            
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
