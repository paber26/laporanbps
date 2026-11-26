<?php

namespace App\Http\Controllers;

use App\Models\MasterPembiayaan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MasterPembiayaanController extends Controller
{
    public function index(): View
    {
        $pembiayaans = MasterPembiayaan::latest()->paginate(10);

        return view('master_pembiayaan.index', compact('pembiayaans'));
    }

    public function create(): View
    {
        return view('master_pembiayaan.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->ensureUniqueCombination($data);

        MasterPembiayaan::create($data);

        return redirect()->route('master-pembiayaan.index')->with('status', 'Data pembiayaan ditambahkan.');
    }

    public function edit(MasterPembiayaan $masterPembiayaan): View
    {
        return view('master_pembiayaan.edit', ['pembiayaan' => $masterPembiayaan]);
    }

    public function update(Request $request, MasterPembiayaan $masterPembiayaan): RedirectResponse
    {
        $data = $this->validated($request);
        $this->ensureUniqueCombination($data, $masterPembiayaan->id);

        $masterPembiayaan->update($data);

        return redirect()->route('master-pembiayaan.index')->with('status', 'Data pembiayaan diperbarui.');
    }

    public function destroy(MasterPembiayaan $masterPembiayaan): RedirectResponse
    {
        $masterPembiayaan->delete();

        return redirect()->route('master-pembiayaan.index')->with('status', 'Data pembiayaan dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'program' => ['required', 'string', 'max:255'],
            'kegiatan' => ['required', 'string', 'max:255'],
            'ro' => ['required', 'string', 'max:255'],
            'komponen' => ['required', 'string', 'max:255'],
            'akun' => ['required', 'string', 'max:255'],
        ]);

        return array_map(fn ($v) => trim((string) $v), $data);
    }

    /**
     * Cegah penyimpanan kombinasi pembiayaan duplikat.
     *
     * @param  array<string, string>  $data
     */
    protected function ensureUniqueCombination(array $data, ?int $ignoreId = null): void
    {
        $exists = MasterPembiayaan::query()
            ->where('program', $data['program'])
            ->where('kegiatan', $data['kegiatan'])
            ->where('ro', $data['ro'])
            ->where('komponen', $data['komponen'])
            ->where('akun', $data['akun'])
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'program' => 'Kombinasi data pembiayaan tersebut sudah ada di Master Pembiayaan.',
            ]);
        }
    }
}
