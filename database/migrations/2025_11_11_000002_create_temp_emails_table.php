<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('temp_emails', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('username', 100);
            $table->foreignId('domain_id')->constrained()->onDelete('cascade');
            $table->timestamp('expires_at');
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            
            $table->index('email');
            $table->index('expires_at');
            $table->index(['is_active', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('temp_emails');
    }
};
