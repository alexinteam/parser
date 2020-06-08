<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewIndexToUsersTable2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up():void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['parsed', 'photo_count', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down():void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['parsed', 'photo_count', 'updated_at']);
        });
    }
}
