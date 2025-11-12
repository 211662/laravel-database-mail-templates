<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('api_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('endpoint');
            $table->integer('requests_count')->default(1);
            $table->timestamp('window_start');
            $table->timestamps();
            
            $table->index(['ip_address', 'window_start']);
            $table->index('window_start');
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_requests');
    }
};
