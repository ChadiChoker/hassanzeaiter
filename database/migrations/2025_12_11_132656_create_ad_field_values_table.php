<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ad_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('ads')->onDelete('cascade');
            $table->foreignId('category_field_id')->constrained('category_fields')->onDelete('cascade');
            $table->text('value')->nullable(); // Flexible storage for any type of value
            $table->timestamps();

            $table->index('ad_id');
            $table->index('category_field_id');
            $table->unique(['ad_id', 'category_field_id']); // One value per field per ad
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_field_values');
    }
};
