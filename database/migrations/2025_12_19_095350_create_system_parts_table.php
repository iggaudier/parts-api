<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_type');
            $table->string('manufacturer');
            $table->string('model_number');
            $table->decimal('list_price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Composite unique constraint
            $table->unique(['manufacturer', 'model_number']);

            // Indexes for performance
            $table->index('part_type');
            $table->index('manufacturer');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_parts');
    }
};
