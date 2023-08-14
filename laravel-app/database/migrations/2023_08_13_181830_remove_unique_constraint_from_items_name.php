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
        Schema::table('items', function (Blueprint $table) {
            $table->dropUnique(['name']); // Drop the unique constraint
        });
    }

    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->unique('name'); // Add the unique constraint back if needed
        });
    }
};
