<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewMarksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up():void
    {
        if (!Schema::hasTable('marks')) {
            Schema::create('marks', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8';
                $table->collation = 'utf8_unicode_ci';
                $table->increments('id');
                $table->unsignedBigInteger('user_id');
                $table->string('social_network_name', '14');
                $table->string('dataset', '14');
                $table->tinyInteger('processed')->default(0);
                $table->timestamps();
                $table->index(['user_id', 'social_network_name']);
                $table->index(['user_id', 'social_network_name', 'dataset']);
                $table->index(['processed']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down():void
    {
        Schema::dropIfExists('marks');
    }
}
