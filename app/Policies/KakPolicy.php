<?php

namespace App\Policies;

use App\Models\Kak;
use App\Models\User;

class KakPolicy
{
    /**
     * Admin punya akses penuh ke semua KAK (oversight).
     * Mengembalikan null agar ability spesifik lanjut dievaluasi untuk non-admin.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Daftar KAK boleh dilihat semua pengguna (mode "lihat semua").
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Detail KAK boleh dilihat semua pengguna (mode "lihat semua").
     */
    public function view(User $user, Kak $kak): bool
    {
        return true;
    }

    /**
     * Semua pengguna boleh membuat KAK.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Hanya pemilik (atau admin via before) yang boleh mengubah.
     */
    public function update(User $user, Kak $kak): bool
    {
        return $kak->user_id === $user->id;
    }

    /**
     * Hanya pemilik (atau admin via before) yang boleh menghapus.
     */
    public function delete(User $user, Kak $kak): bool
    {
        return $kak->user_id === $user->id;
    }
}
