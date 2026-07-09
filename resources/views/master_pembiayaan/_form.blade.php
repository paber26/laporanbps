<form action="{{ $action }}" method="POST" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-md text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    @php $pembiayaan = $pembiayaan ?? null; @endphp
    @foreach (['program' => 'Program', 'kegiatan' => 'Kegiatan', 'ro' => 'RO (Rincian Output)', 'komponen' => 'Komponen', 'akun' => 'Akun'] as $name => $label)
        <div>
            <x-input-label :for="$name" :value="$label" />
            <x-text-input :id="$name" :name="$name" type="text" class="mt-1 block w-full"
                :value="old($name, $pembiayaan->{$name} ?? '')" required />
            <x-input-error :messages="$errors->get($name)" class="mt-1" />
        </div>
    @endforeach

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('master-pembiayaan.index') }}" class="px-4 py-2 text-sm text-gray-600">Batal</a>
        <button class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Simpan</button>
    </div>
</form>
