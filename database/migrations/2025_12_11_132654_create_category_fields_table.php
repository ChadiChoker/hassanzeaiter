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
        Schema::create('category_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('external_id')->unique();
            $table->string('field_key');
            $table->string('field_label');
            $table->string('field_type'); // text, number, select, radio, checkbox, date, etc.
            $table->boolean('is_required')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->text('validation_rules')->nullable();
            $table->text('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->integer('order')->default(0);
            $table->json('metadata')->nullable(); // Additional field configuration
            $table->timestamps();

            $table->index('category_id');
            $table->index('external_id');
            $table->index(['category_id', 'field_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_fields');
    }
};
