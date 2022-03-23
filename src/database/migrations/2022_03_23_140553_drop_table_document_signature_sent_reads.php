<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropTableDocumentSignatureSentReads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('document_signature_sent_reads');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('document_signature_sent_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_signature_sent_id');
            $table->unsignedBigInteger('people_id');
            $table->boolean('read');
            $table->timestamps();
        });
    }
}
