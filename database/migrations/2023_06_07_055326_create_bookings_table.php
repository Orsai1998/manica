<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('apartment_id');
            $table->bigInteger('user_id');
            $table->integer('number_of_adult')->nullable();
            $table->integer('number_of_children');
            $table->enum('status',['PENDING', 'PAID', 'NOT_PAID'])->default('PENDING');
            $table->dateTime('enter_date')->nullable();
            $table->dateTime('departure_date')->nullable();
            $table->tinyInteger('is_late_departure')->nullable();
            $table->float('total_sum')->nullable();
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
        Schema::dropIfExists('bookings');
    }
}
