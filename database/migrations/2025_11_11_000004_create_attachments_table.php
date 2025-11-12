<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbox_message_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->string('content_type', 100);
            $table->integer('size');
            $table->text('storage_path');
            $table->timestamps();
            
            $table->index('inbox_message_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attachments');
    }
};
