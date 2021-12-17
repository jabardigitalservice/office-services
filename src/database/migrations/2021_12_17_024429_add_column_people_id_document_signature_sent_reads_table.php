<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnPeopleIdDocumentSignatureSentReadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('document_signature_sent_reads', function (Blueprint $table) {
            $table->unsignedBigInteger('people_id')->after('document_signature_sent_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('document_signature_sent_reads', function (Blueprint $table) {
            $table->dropColumn('people_id');
        });
    }
}
