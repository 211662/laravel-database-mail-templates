<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('mx_record')->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'is_custom']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('domains');
    }
};
