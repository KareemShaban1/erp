<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->references('id')->on('clients')->nullOnDelete();
            $table->unsignedInteger('product_id')->nullable(); // Matches the type of 'id' in 'products'
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->unsignedInteger('variation_id')->nullable(); // Matches the type of 'id' in 'variations'
            $table->foreign('variation_id')->references('id')->on('variations')->nullOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('discount')->nullable();
            // $table->string('status')->default('active'); // Could be active, checked_out, abandoned

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
        Schema::dropIfExists('carts');
    }
}
