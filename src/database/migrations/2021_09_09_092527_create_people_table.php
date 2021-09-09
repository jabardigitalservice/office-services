<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('people', function (Blueprint $table) {
            $table->string('PeopleKey');
            $table->unsignedInteger('PeopleId');
            $table->string('PeopleName');
            $table->string('PeoplePosition')->nullable();
            $table->string('PeopleUsername');
            $table->string('PeoplePassword');
            $table->date('PeopleActiveStartDate')->nullable();
            $table->date('PeopleActiveEndDate')->nullable();
            $table->unsignedTinyInteger('PeopleIsActive')->default(1);
            $table->string('PrimaryRoleId')->nullable();
            $table->unsignedInteger('GroupId')->nullable();
            $table->string('RoleAtasan');
            $table->string('NIP')->nullable();
            $table->string('ApprovelName');
            $table->string('Email')->nullable();
            $table->string('NIK')->nullable();
            $table->string('Pangkat')->nullable();
            $table->string('Eselon')->nullable();
            $table->string('Golongan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('people');
    }
}
