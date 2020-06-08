<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewCompositeIndexOnUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up():void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['parsed', 'updated_at', 'created_at']);
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
            $table->dropIndex(['parsed', 'updated_at', 'created_at']);
        });
    }
}
