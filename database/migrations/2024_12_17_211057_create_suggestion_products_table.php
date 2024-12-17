<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuggestionProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('suggestion_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()
            ->constrained()->nullOnDelete();
            $table->unsignedInteger('business_id');
            $table->foreign('business_id')->references('id')->on('business')->cascadeOnDelete();
            $table->string('name');
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
        Schema::dropIfExists('suggestion_products');
    }
}
