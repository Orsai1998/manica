<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ApartmentFeedbacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apartment_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('apartment_id');
            $table->bigInteger('user_id');
            $table->float('rate')->nullable();
            $table->longText('feedback');
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
        //
    }
}
