<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSocialNetworkName extends Migration
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
                $table->string('social_network_name', '14')->after('id');
                $table->index('social_network_name');
                $table->index(['id', 'social_network_name']);
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
                $table->dropIndex('social_network_name');
                $table->dropIndex(['id', 'social_network_name']);
                $table->dropColumn('social_network_name');
            });
        }
    }
}
