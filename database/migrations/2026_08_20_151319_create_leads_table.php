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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            $table->string('company_name');
            $table->string('category')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('instagram')->nullable();
            $table->boolean('has_website')->default(false);
            $table->boolean('has_instagram')->default(false);
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('status')->default('new');
            $table->string('type')->nullable();
            $table->unsignedSmallInteger('score')->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_count')->nullable();
            $table->string('source')->nullable();
            $table->text('google_maps_url')->nullable();
            $table->timestamp('source_created_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
