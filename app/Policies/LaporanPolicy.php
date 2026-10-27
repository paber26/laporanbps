<?php

namespace App\Policies;

use App\Models\Laporan;
use App\Models\User;

class LaporanPolicy
{
    /**
     * Admin punya akses penuh ke semua laporan (oversight).
     * Mengembalikan null agar ability spesifik lanjut dievaluasi untuk non-admin.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Daftar laporan boleh dilihat semua pengguna (mode "lihat semua").
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Detail laporan boleh dilihat semua pengguna (mode "lihat semua").
     */
    public function view(User $user, Laporan $laporan): bool
    {
        return true;
    }

    /**
     * Semua pengguna boleh membuat laporan.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Hanya pemilik (atau admin via before) yang boleh mengubah.
     */
    public function update(User $user, Laporan $laporan): bool
    {
        return $laporan->user_id === $user->id;
    }

    /**
     * Hanya pemilik (atau admin via before) yang boleh menghapus.
     */
    public function delete(User $user, Laporan $laporan): bool
    {
        return $laporan->user_id === $user->id;
    }
}
