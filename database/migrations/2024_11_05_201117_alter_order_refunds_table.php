<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterOrderRefundsTable extends Migration
{/**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_refunds', function (Blueprint $table) {
            // $table->dropForeign(['product_id']);
            // $table->dropForeign(['variation_id']);
            $table->dropColumn(['product_id', 'variation_id']);

            // Add the new order_item_id column with foreign key constraint
            $table->foreignId('order_item_id')->after('order_id')->constrained('order_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_refunds', function (Blueprint $table) {
            // Drop the order_item_id column and its foreign key
            $table->dropForeign(['order_item_id']);
            $table->dropColumn('order_item_id');

            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unsignedInteger('variation_id');
            $table->foreign('variation_id')->references('id')->on('variations')->cascadeOnDelete();
        });
    }
}
