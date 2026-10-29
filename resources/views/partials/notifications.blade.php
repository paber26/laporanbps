{{--
    Sistem notifikasi global (pop-up) — dipakai di seluruh halaman.
    - window.notify(type, message, opts)         -> toast (success|error|info|warning)
    - window.confirmDialog(message, opts)         -> Promise<bool> (ganti confirm() native)
    - <form data-confirm="..."> otomatis di-intersep jadi modal konfirmasi.
    - Flash session('status')/session('error') dari server tampil sebagai toast.
--}}
@php
    $__statusMap = [
        'profile-updated' => 'Profil berhasil diperbarui.',
        'password-updated' => 'Kata sandi berhasil diperbarui.',
        'verification-link-sent' => 'Tautan verifikasi baru telah dikirim ke email Anda.',
    ];
    $__status = session('status');
    $__statusMsg = $__status ? ($__statusMap[$__status] ?? $__status) : null;
    $__error = session('error');
@endphp

<style>[x-cloak]{display:none !important;}</style>

<script>
    // Helper global — selalu terdefinisi lebih awal (dekuplng dari Alpine via event).
    window.notify = function (type, message, opts) {
        window.dispatchEvent(new CustomEvent('app-notify', {
            detail: Object.assign({ type: type || 'info', message: message || '' }, opts || {})
        }));
    };
    window.confirmDialog = function (message, opts) {
        return new Promise(function (resolve) {
            window.dispatchEvent(new CustomEvent('app-confirm', {
                detail: { message: message, opts: opts || {}, resolve: resolve }
            }));
        });
    };

    // Intersep form[data-confirm] -> modal pop-up (pengganti confirm() native).
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form && form.nodeName === 'FORM' && form.hasAttribute('data-confirm')) {
            e.preventDefault();
            e.stopPropagation();
            window.confirmDialog(form.getAttribute('data-confirm'), {
                title: form.getAttribute('data-confirm-title') || 'Konfirmasi',
                okText: form.getAttribute('data-confirm-ok') || 'Ya',
                variant: form.getAttribute('data-confirm-variant') || 'primary'
            }).then(function (ok) {
                if (ok) { form.removeAttribute('data-confirm'); form.submit(); }
            });
        }
    }, true);

    // Flash dari server -> toast (setelah Alpine siap agar listener aktif).
    document.addEventListener('alpine:initialized', function () {
        @if ($__statusMsg) window.notify('success', @json($__statusMsg)); @endif
        @if ($__error) window.notify('error', @json($__error)); @endif
    });

    function appNotifier() {
        return {
            toasts: [],
            confirm: { open: false, title: 'Konfirmasi', message: '', okText: 'Ya', variant: 'primary', resolver: null },

            push(d) {
                var id = Date.now() + '-' + Math.random().toString(36).slice(2);
                // show:false lalu dinyalakan via x-init (pada proxy loop item) agar
                // transisi enter berjalan & reaktivitas Alpine terpicu dengan benar.
                this.toasts.push({ id: id, type: (d && d.type) || 'info', message: (d && d.message) || '', show: false });
                var dur = (d && d.duration) || 4500;
                var self = this;
                setTimeout(function () { self.dismiss(id); }, dur);
            },
            dismiss(id) {
                var t = this.toasts.find(function (x) { return x.id === id; });
                if (t) t.show = false;
                var self = this;
                setTimeout(function () { self.toasts = self.toasts.filter(function (x) { return x.id !== id; }); }, 250);
            },
            openConfirm(d) {
                var o = (d && d.opts) || {};
                // Jika ada dialog terbuka, batalkan yang lama agar promise-nya tetap selesai.
                if (this.confirm.open && this.confirm.resolver) { this.confirm.resolver(false); }
                this.confirm = {
                    open: true,
                    title: o.title || 'Konfirmasi',
                    message: (d && d.message) || 'Lanjutkan?',
                    okText: o.okText || 'Ya',
                    variant: o.variant || 'primary',
                    resolver: (d && d.resolve) || null,
                };
            },
            resolveConfirm(val) {
                var r = this.confirm.resolver;
                this.confirm.open = false;
                this.confirm.resolver = null;
                if (r) r(val);
            },

            toastClass(type) {
                return ({
                    success: 'bg-emerald-50 dark:bg-emerald-900/50 border-emerald-300 dark:border-emerald-700 text-emerald-800 dark:text-emerald-100',
                    error:   'bg-rose-50 dark:bg-rose-900/50 border-rose-300 dark:border-rose-700 text-rose-800 dark:text-rose-100',
                    warning: 'bg-amber-50 dark:bg-amber-900/50 border-amber-300 dark:border-amber-700 text-amber-900 dark:text-amber-100',
                    info:    'bg-slate-50 dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-800 dark:text-slate-100',
                })[type] || this.toastClass('info');
            },
            iconPath(type) {
                return ({
                    success: 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                    error:   'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z',
                    warning: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
                    info:    'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
                })[type] || this.iconPath('info');
            },
        };
    }
    window.appNotifier = appNotifier;
</script>

<div x-data="appNotifier()"
     x-on:app-notify.window="push($event.detail)"
     x-on:app-confirm.window="openConfirm($event.detail)"
     x-on:keydown.escape.window="confirm.open && resolveConfirm(false)">

    {{-- ===== Toast stack ===== --}}
    <div class="fixed top-4 right-4 z-[80] flex flex-col gap-2 w-80 max-w-[calc(100vw-2rem)]" aria-live="polite">
        <template x-for="t in toasts" :key="t.id">
            <div x-show="t.show"
                 x-init="$nextTick(() => { t.show = true })"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-4"
                 :class="toastClass(t.type)"
                 class="flex items-start gap-3 rounded-lg border shadow-lg px-4 py-3 text-sm">
                <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="iconPath(t.type)" />
                </svg>
                <span class="flex-1 break-words" x-text="t.message"></span>
                <button type="button" @click="dismiss(t.id)" class="shrink-0 opacity-60 hover:opacity-100 transition" aria-label="Tutup">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>

    {{-- ===== Confirm modal ===== --}}
    <div x-show="confirm.open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div x-show="confirm.open" x-transition.opacity @click="resolveConfirm(false)" class="absolute inset-0 bg-black/50"></div>
        <div x-show="confirm.open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-xl bg-white dark:bg-gray-800 shadow-2xl ring-1 ring-black/5 dark:ring-white/10 p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100" x-text="confirm.title"></h3>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300" x-text="confirm.message"></p>
            <div class="mt-6 flex items-center justify-end gap-2">
                <button type="button" @click="resolveConfirm(false)"
                        class="px-4 py-2 text-sm rounded-md border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    Batal
                </button>
                <button type="button" @click="resolveConfirm(true)"
                        :class="confirm.variant === 'danger'
                            ? 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500'
                            : 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500'"
                        class="px-4 py-2 text-sm rounded-md text-white font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2"
                        x-text="confirm.okText"></button>
            </div>
        </div>
    </div>
</div>
