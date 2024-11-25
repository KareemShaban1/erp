<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterBannersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->string('module_type')->nullable()
            ->comment('Type of module: category, product, etc.')->after('image');
            $table->unsignedBigInteger('module_id')->nullable()
            ->comment('ID of the associated module (e.g., product_id, category_id)')->after('module_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('module_type');
            $table->dropColumn('module_id');
        });
    }
}
