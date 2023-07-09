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
            $table->dropColumn('invoice_id'); // Remove the invoice_id column
            $table->dropColumn('description');
            $table->dropColumn('cost');
            $table->dropColumn('hours');
            $table->uuid('organization_id'); // Add the organaization_id column back (if needed
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->uuid('invoice_id'); // Add the invoice_id column back (if needed)
            $table->text('description')->nullable();
            $table->integer('cost');
            $table->float('hours');
            $table->dropColumn('organization_id'); // Remove the organization_id column
        });
    }
};
