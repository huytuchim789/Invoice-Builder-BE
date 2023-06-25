<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->string('status')->default('pending');
            $table->string("email_subject")->default("");
            $table->text("email_message")->nullable();
            $table->text('error_message')->nullable();
            $table->string('method')->default('mail'); // Updated migration
            $table->unique(['invoice_id', 'method']);
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
        Schema::dropIfExists('email_transactions');
    }
};
