<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inbox_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temp_email_id')->constrained()->onDelete('cascade');
            $table->string('message_id')->unique();
            $table->string('from_address');
            $table->string('from_name')->nullable();
            $table->text('subject')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->boolean('has_attachments')->default(false);
            $table->string('two_fa_code', 20)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('received_at');
            $table->timestamps();
            
            $table->index('temp_email_id');
            $table->index('received_at');
            $table->index('two_fa_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inbox_messages');
    }
};
