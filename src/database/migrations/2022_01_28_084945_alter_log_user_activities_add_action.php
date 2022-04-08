<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterLogUserActivitiesAddAction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_user_activities', function (Blueprint $table) {
            $table->string('query', 10)->change();
            $table->renameColumn('query', 'type');
            $table->string('action', 255)->after('query');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_user_activities', function (Blueprint $table) {
            $table->dropColumn('action');
            $table->text('type')->change();
            $table->renameColumn('type', 'query');
        });
    }
}
