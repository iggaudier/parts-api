<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('system_part_id')->constrained()->onDelete('cascade');
            $table->decimal('multiplier', 8, 4)->nullable();
            $table->decimal('static_price', 10, 2)->nullable();
            $table->decimal('team_price', 10, 2);
            $table->timestamps();

            // A part can only be associated once per team
            $table->unique(['team_id', 'system_part_id']);

            // Indexes
            $table->index('team_id');
            $table->index('system_part_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_parts');
    }
};
