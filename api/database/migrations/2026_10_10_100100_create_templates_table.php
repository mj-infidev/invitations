<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // tamil-hindu-wedding
            $table->string('name');
            $table->string('category');  // wedding
            $table->string('tradition'); // tamil_hindu
            $table->unsignedInteger('price_paise'); // ₹499 = 49900; money is never a decimal
            // The form fields this template needs. Format: PLAN.md > Template form fields.
            $table->json('fields_schema');
            $table->string('thumbnail_path');
            $table->unsignedTinyInteger('max_photos');
            $table->boolean('is_active')->default(true); // false = hidden from customers
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
