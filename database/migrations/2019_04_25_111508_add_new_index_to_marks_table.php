<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewIndexToMarksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up():void
    {
        Schema::table('marks', function (Blueprint $table) {
            $table->index(['processed', 'dataset']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down():void
    {
        Schema::table('marks', function (Blueprint $table) {
            $table->dropIndex(['processed', 'dataset']);
        });
    }
}
