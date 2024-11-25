<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientIdAndBusinessLocationIdToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            //
            $table->foreignId('client_id')->nullable()->after('number')
            ->constrained()->nullOnDelete();

            $table->unsignedInteger('business_location_id')->nullable()->after('number');
            $table->foreign('business_location_id')->references('id')
            ->on('business_locations')->nullOnDelete();

            $table->foreignId('parent_order_id')
            ->nullable()
            ->after('number')
            ->constrained('orders')
            ->nullOnDelete();
      


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['parent_order_id']);
            // Drop the column
            $table->dropColumn('parent_order_id');
        });
    }
}
