<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeNewIdColumn extends Migration
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
                $table->unsignedBigInteger('id', false)->first();
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
                $table->dropColumn(['id']);
            });
        }
    }
}
