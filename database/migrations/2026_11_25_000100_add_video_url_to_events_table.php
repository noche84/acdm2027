<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('video_url', 500)->nullable()->after('booking_url')
                ->comment('Vidéo YouTube ou Vimeo des musiciens, affichée sur la fiche du concert');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('video_url');
        });
    }
};
