<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewCreatedAtUpdatedAtIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('friends')) {
            Schema::table('friends', function (Blueprint $table) {
                $table->index(['created_at', 'updated_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('friends')) {
            Schema::table('friends', function (Blueprint $table) {
                $table->dropIndex(['created_at', 'updated_at']);
            });
        }
    }
}
