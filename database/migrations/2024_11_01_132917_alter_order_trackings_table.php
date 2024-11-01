<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterOrderTrackingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_trackings', function (Blueprint $table) {
            // Drop the 'declined_at' column
            $table->dropColumn('declined_at');

            // Add the new 'completed_at' column
            $table->dateTime('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_trackings', function (Blueprint $table) {
            // Add the 'declined_at' column back
            $table->dateTime('declined_at')->nullable();

            // Drop the 'completed_at' column
            $table->dropColumn('completed_at');
        });
    }
}
