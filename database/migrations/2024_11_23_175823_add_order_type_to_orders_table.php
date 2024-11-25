<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderTypeToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_type')
            ->default('order')
            ->after('order_status');

            $table->dropForeign(['client_id']);
            $table->dropForeign(['business_location_id']);
            $table->dropColumn(['client_id', 'business_location_id']);


            // $table->foreignId('client_id')
            // ->nullable()
            // ->after('order_uuid')
            // ->constrained()
            // ->nullOnDelete();
            // $table->unsignedInteger('business_location_id')
            // ->nullable()
            // ->after('client_id');
            $table->unsignedInteger('from_business_location_id')
            ->nullable()
            ->after('business_location_id');
            $table->unsignedInteger('to_business_location_id')
            ->nullable()
            ->after('from_business_location_id');


            // Add foreign key constraints
            // $table->foreign('business_location_id')
            //     ->references('id')
            //     ->on('business_locations')
            //     ->nullOnDelete();

            $table->foreign('from_business_location_id')
                ->references('id')
                ->on('business_locations')
                ->nullOnDelete();

            $table->foreign('to_business_location_id')
                ->references('id')
                ->on('business_locations')
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
            // Drop the new columns and foreign keys
            $table->dropColumn('order_type');
            $table->dropForeign(['business_location_id']);
            $table->dropForeign(['from_business_location_id']);
            $table->dropForeign(['to_business_location_id']);
            $table->dropColumn(['business_location_id', 'from_business_location_id', 'to_business_location_id']);

            // Re-add client_id as non-nullable
            $table->foreignId('client_id')
                ->after('order_uuid')
                ->constrained()
                ->cascadeOnDelete();
        });
    }
}
