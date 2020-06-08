<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewActiveField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedTinyInteger('active', false)->after('parsed')->default(1);
                $table->index('active');
                $table->index(['active', 'id']);
                $table->index(['active', 'parsed']);
                $table->index(['active', 'parsed', 'created_at']);
                $table->index(['active', 'parsed', 'updated_at']);
                $table->index(['active', 'created_at']);
                $table->index(['active', 'updated_at']);
                $table->index(['active', 'created_at', 'updated_at']);
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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if(Schema::hasColumn('users', 'active')) {
                    $table->dropIndex('active');
                    $table->dropIndex(['active', 'id']);
                    $table->dropIndex(['active', 'parsed']);
                    $table->dropIndex(['active', 'parsed', 'created_at']);
                    $table->dropIndex(['active', 'parsed', 'updated_at']);
                    $table->dropIndex(['active', 'created_at']);
                    $table->dropIndex(['active', 'updated_at']);
                    $table->dropIndex(['active', 'created_at', 'updated_at']);
                    $table->dropColumn('active');
                }
            });
        }
    }
}
