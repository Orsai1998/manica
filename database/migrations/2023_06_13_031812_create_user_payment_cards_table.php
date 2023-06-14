<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPaymentCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_payment_cards', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('subscription_token');
            $table->string('account');
            $table->string('bank');
            $table->string('brand');
            $table->tinyInteger('is_main');
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
        Schema::dropIfExists('user_payment_cards');
    }
}
