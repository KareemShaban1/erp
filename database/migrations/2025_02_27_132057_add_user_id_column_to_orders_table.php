<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdColumnToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Ensure the users table exists before adding a foreign key
            if (!Schema::hasColumn('orders', 'user_id')) {
                // Add user_id column first
                $table->unsignedBigInteger('user_id')->nullable()->after('number');

                // Then define the foreign key
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('SET NULL'); // Fix syntax issue
            }
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
            if (Schema::hasColumn('orders', 'user_id')) {
                // Drop the foreign key first
                $table->dropForeign(['user_id']);

                // Then drop the column
                $table->dropColumn('user_id');
            }
        });
    }
}
