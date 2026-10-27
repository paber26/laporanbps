<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah peran pengguna: 'admin' (oversight penuh) atau 'petugas' (hanya
     * mengelola data miliknya). Akun admin@bps.go.id dijadikan admin.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('petugas')->after('password');
        });

        DB::table('users')
            ->where('email', 'admin@bps.go.id')
            ->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
