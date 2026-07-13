@php $pegawai = $pegawai ?? null; @endphp
<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 space-y-4">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    @if ($errors->any())
        <div class="bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 px-4 py-3 rounded-md text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div>
        <x-input-label for="nip" value="NIP" />
        <x-text-input id="nip" name="nip" type="text" class="mt-1 block w-full" :value="old('nip', $pegawai->nip ?? '')" required />
        <x-input-error :messages="$errors->get('nip')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="nama" value="Nama" />
        <x-text-input id="nama" name="nama" type="text" class="mt-1 block w-full" :value="old('nama', $pegawai->nama ?? '')" required />
        <x-input-error :messages="$errors->get('nama')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="unit_kerja" value="Unit Kerja / Asal Kantor BPS" />
        <x-text-input id="unit_kerja" name="unit_kerja" type="text" class="mt-1 block w-full" :value="old('unit_kerja', $pegawai->unit_kerja ?? '')" required />
        <x-input-error :messages="$errors->get('unit_kerja')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="tanda_tangan" value="Tanda Tangan (opsional, PNG/JPG)" />
        <input id="tanda_tangan" name="tanda_tangan" type="file" accept="image/png,image/jpeg" class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-300">
        @if ($pegawai && $pegawai->tanda_tangan_path)
            <img src="{{ \Storage::url($pegawai->tanda_tangan_path) }}" class="h-16 mt-2 border rounded" alt="ttd saat ini">
        @endif
        <x-input-error :messages="$errors->get('tanda_tangan')" class="mt-1" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('pegawai.index') }}" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300">Batal</a>
        <button class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Simpan</button>
    </div>
</form>
