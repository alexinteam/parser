<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPhotoCountColumn extends Migration
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
                $table->unsignedSmallInteger('photo_count', false)->after('processed')->default(0);
                $table->index('photo_count');
                $table->index(['photo_count', 'created_at']);
                $table->index(['photo_count', 'updated_at']);
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
                if(Schema::hasColumn('users', 'photo_count')) {
                    $table->dropIndex(['photo_count']);
                    $table->dropIndex(['photo_count', 'created_at']);
                    $table->dropIndex(['photo_count', 'updated_at']);
                    $table->dropColumn('photo_count');
                }
            });
        }
    }
}
