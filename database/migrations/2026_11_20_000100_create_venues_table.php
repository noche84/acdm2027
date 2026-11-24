<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('room')->nullable()->comment('Salle précise : auditorium, chapelle d\'hiver…');
            $table->string('street')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city')->default('Liège');
            $table->string('map_url', 500)->nullable();
            $table->string('website', 500)->nullable();
            $table->text('access_info')->nullable()->comment('Accès, parking, chauffage…');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
