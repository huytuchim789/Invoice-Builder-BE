<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('company');
            $table->string('email');
            $table->string('country');
            $table->string('address');
            $table->string('contact_number', 20);
            $table->string('contact_number_country', 10);
        });
    }

    /**
     * Reverse the migrations.x
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
};
