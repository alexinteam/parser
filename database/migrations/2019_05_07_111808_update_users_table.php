<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUsersTable extends Migration
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
                $table->unsignedInteger('age_predict')->after('age')->default(0);
                $table->string('sex')->after('age_predict')->nullable();
                $table->string('sex_predict')->after('sex')->nullable();
                $table->integer('ita' )->after('sex_predict')->nullable();
                $table->string('race')->after('ita')->nullable();
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
                $table->dropColumn('age_predict');
                $table->dropColumn('sex');
                $table->dropColumn('sex_predict');
                $table->dropColumn('ita' );
                $table->dropColumn('race');
            });
        }
    }
}
