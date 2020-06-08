<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewProcessedColumnAndIndex extends Migration
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
                $table->unsignedTinyInteger('processed', false)->after('active')->default(0);
                $table->index('processed');
                $table->index(['processed', 'created_at']);
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
                if(Schema::hasColumn('users', 'processed')) {
                    $table->dropIndex(['processed']);
                    $table->dropIndex(['processed', 'created_at']);
                    $table->dropColumn('processed');
                }
            });
        }
    }
}
