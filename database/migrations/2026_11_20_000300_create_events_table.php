<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 20)->index();
            $table->foreignId('series_id')->nullable()->constrained('series')->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->dateTime('starts_at')->index();
            $table->string('slug')->unique();

            $table->string('title')->nullable()->comment('Titre du programme : « Love Story »');
            $table->string('ensemble')->nullable()->comment('Nom de la formation : Trio Memento');
            $table->longText('performers')->nullable()->comment('Interprètes (HTML)');
            $table->longText('program')->nullable()->comment('Programme / compositeurs (HTML)');
            $table->longText('description')->nullable()->comment('Texte libre complémentaire (HTML)');

            $table->string('price_info')->nullable()->comment('Entrée gratuite, 15 € / 10 €…');
            $table->string('booking_url', 500)->nullable();
            $table->string('partner_mention')->nullable()->comment('En collaboration avec…');
            $table->string('poster')->nullable()->comment('Affiche (image)');

            $table->string('status', 20)->default('scheduled')->index();
            $table->string('status_note')->nullable()->comment('Raison de l\'annulation / nouvelle date');
            $table->boolean('is_published')->default(true)->index();

            $table->unsignedInteger('legacy_id')->nullable()->unique()->comment('id de l\'ancienne table agenda');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
