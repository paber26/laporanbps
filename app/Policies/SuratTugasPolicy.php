<?php

namespace App\Policies;

use App\Models\SuratTugas;
use App\Models\User;

class SuratTugasPolicy
{
    /**
     * Admin punya akses penuh ke semua Surat Tugas.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SuratTugas $suratTugas): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Hanya pembuat (atau admin via before) yang boleh mengubah.
     */
    public function update(User $user, SuratTugas $suratTugas): bool
    {
        return $suratTugas->user_id === $user->id;
    }

    /**
     * Hanya pembuat (atau admin via before) yang boleh menghapus.
     */
    public function delete(User $user, SuratTugas $suratTugas): bool
    {
        return $suratTugas->user_id === $user->id;
    }
}
