<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApartmentPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apartment_prices', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('apartment_id')->unsigned()->change();
            $table->bigInteger('price_type_id')->unsigned()->change();
            $table->bigInteger('rent_type_id')->unsigned()->change();
            $table->integer('price')->unsigned()->change();
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
        Schema::dropIfExists('apartment_prices');
    }
}
