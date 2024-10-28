<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->unsignedInteger('variation_id');
            $table->foreign('variation_id')->references('id')->on('variations')->cascadeOnDelete();
            $table->decimal('amount', 10, 2); 
            $table->enum('status', ['requested', 'approved', 'rejected', 'processed'])->default('requested');
            $table->text('reason')->nullable(); 
            $table->text('admin_response')->nullable();  
            $table->dateTime('requested_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->dateTime('processed_at')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('order_refunds');
    }
}
