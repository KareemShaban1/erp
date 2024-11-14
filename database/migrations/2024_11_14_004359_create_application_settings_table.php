<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('application_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Unique key for each setting
            $table->text('value')->nullable(); // Value of the setting
            $table->enum('type', ['string', 'boolean', 'text', 'integer', 'float', 'json'])->default('string'); // Type of the value
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
        Schema::dropIfExists('application_settings');
    }
}
