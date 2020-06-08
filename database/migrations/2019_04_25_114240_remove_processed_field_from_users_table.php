<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveProcessedFieldFromUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('processed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('processed', false)->after('active')->default(0);
            $table->index('processed');
            $table->index(['processed', 'created_at']);
            $table->index(['processed', 'parsed', 'photo_count']);
            $table->index(['processed', 'parsed', 'photo_count', 'created_at']);
        });
    }
}
