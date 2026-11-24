<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 20)->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->longText('description')->nullable()->comment('Texte de présentation affiché avec chaque concert de la série');
            $table->string('default_time', 5)->nullable()->comment('HH:MM proposé par défaut');
            $table->foreignId('default_venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->string('default_price_info')->nullable();
            $table->string('website', 500)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
