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
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('likely_whatsapp')->nullable()->after('has_instagram');
            $table->string('facebook')->nullable()->after('instagram');
            $table->string('responsible')->nullable()->after('type');
            $table->text('summary')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['likely_whatsapp', 'facebook', 'responsible', 'summary']);
        });
    }
};
