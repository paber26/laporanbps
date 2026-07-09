<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PegawaiController extends Controller
{
    public function index(): View
    {
        $pegawais = Pegawai::withCount('laporans')->latest()->paginate(10);

        return view('pegawai.index', compact('pegawais'));
    }

    public function create(): View
    {
        return view('pegawai.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['tanda_tangan_path'] = $this->storeTtd($request);

        Pegawai::create($data);

        return redirect()->route('pegawai.index')->with('status', 'Pegawai ditambahkan.');
    }

    public function edit(Pegawai $pegawai): View
    {
        return view('pegawai.edit', compact('pegawai'));
    }

    public function update(Request $request, Pegawai $pegawai): RedirectResponse
    {
        $data = $this->validated($request, $pegawai);

        if ($request->hasFile('tanda_tangan')) {
            if ($pegawai->tanda_tangan_path) {
                Storage::disk('public')->delete($pegawai->tanda_tangan_path);
            }
            $data['tanda_tangan_path'] = $this->storeTtd($request);
        }

        $pegawai->update($data);

        return redirect()->route('pegawai.index')->with('status', 'Pegawai diperbarui.');
    }

    public function destroy(Pegawai $pegawai): RedirectResponse
    {
        if ($pegawai->tanda_tangan_path) {
            Storage::disk('public')->delete($pegawai->tanda_tangan_path);
        }
        $pegawai->delete();

        return redirect()->route('pegawai.index')->with('status', 'Pegawai dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Pegawai $pegawai = null): array
    {
        return $request->validate([
            'nip' => ['required', 'string', 'max:30', Rule::unique('pegawais', 'nip')->ignore($pegawai?->id)],
            'nama' => ['required', 'string', 'max:255'],
            'unit_kerja' => ['required', 'string', 'max:255'],
            'tanda_tangan' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);
    }

    protected function storeTtd(Request $request): ?string
    {
        if (! $request->hasFile('tanda_tangan')) {
            return null;
        }

        return $request->file('tanda_tangan')->store('tanda_tangan', 'public');
    }
}
