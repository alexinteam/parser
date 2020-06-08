<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\User;
use Illuminate\Support\Facades\DB;

class MigrateIds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users')) {
            DB::table('users')
                ->where('id', 0)
                ->update([
                    'id' => DB::raw("`old_id`"),
                ]);
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
            DB::table('users')
                ->where('id', 0)
                ->update([
                    'id' => 0,
                ]);
        }
    }
}
