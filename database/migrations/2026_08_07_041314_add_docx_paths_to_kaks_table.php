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
        Schema::table('kaks', function (Blueprint $table) {
            $table->string('docx_original_path')->nullable()->after('ttd_kanan_nip');
            $table->string('docx_edited_path')->nullable()->after('docx_original_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kaks', function (Blueprint $table) {
            $table->dropColumn(['docx_original_path', 'docx_edited_path']);
        });
    }
};
