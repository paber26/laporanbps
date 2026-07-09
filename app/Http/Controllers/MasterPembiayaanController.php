<?php

namespace App\Http\Controllers;

use App\Models\MasterPembiayaan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        MasterPembiayaan::create($this->validated($request));

        return redirect()->route('master-pembiayaan.index')->with('status', 'Data pembiayaan ditambahkan.');
    }

    public function edit(MasterPembiayaan $masterPembiayaan): View
    {
        return view('master_pembiayaan.edit', ['pembiayaan' => $masterPembiayaan]);
    }

    public function update(Request $request, MasterPembiayaan $masterPembiayaan): RedirectResponse
    {
        $masterPembiayaan->update($this->validated($request));

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
        return $request->validate([
            'program' => ['required', 'string', 'max:255'],
            'kegiatan' => ['required', 'string', 'max:255'],
            'ro' => ['required', 'string', 'max:255'],
            'komponen' => ['required', 'string', 'max:255'],
            'akun' => ['required', 'string', 'max:255'],
        ]);
    }
}
