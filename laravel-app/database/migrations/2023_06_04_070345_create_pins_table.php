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
        Schema::create('pins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('number');
            $table->string('coordinate_X');
            $table->string('coordinate_Y');
            $table->uuid('invoice_id');
            $table->uuid('user_id');
            $table->timestamps();
            $table->unique(['number', 'invoice_id','coordinate_X','coordinate_Y']); // Add unique constraint for number and invoice_id columns

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pins');
    }
};
