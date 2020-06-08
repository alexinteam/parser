<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCreatedAtIndex extends Migration
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
                $table->index('created_at');
                $table->index('updated_at');
                $table->index(['created_at', 'parsed']);
                $table->index(['updated_at', 'parsed']);
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
                $table->dropIndex('created_at');
                $table->dropIndex('updated_at');
                $table->dropIndex(['created_at', 'parsed']);
                $table->dropIndex(['updated_at', 'parsed']);
            });
        }
    }
}
