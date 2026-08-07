import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { DocxEditor } from '@docx-editor.dev/react';
import '@docx-editor.dev/core/styles/editor.css';

function KakDocxEditor({ kakId, originalUrl, saveUrl, editedUrl, pdfUrl, csrfToken }) {
    const editorRef = useRef(null);
    const [buffer, setBuffer] = useState(null);
    const [hasEdited, setHasEdited] = useState(false);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState(null);

    // Muat dokumen: selalu mulai dari original (pasti tersedia, di-generate server),
    // lalu coba ambil versi edit bila ada. Jika versi edit rusak, tetap pakai original.
    useEffect(() => {
        let cancelled = false;

        async function load() {
            try {
                const origRes = await fetch(originalUrl, { headers: { Accept: 'application/octet-stream' } });
                if (!origRes.ok) {
                    throw new Error('Gagal memuat dokumen asli (HTTP ' + origRes.status + ')');
                }

                let bytes = await origRes.arrayBuffer();
                let editedFound = false;

                try {
                    const editRes = await fetch(editedUrl, { headers: { Accept: 'application/octet-stream' } });
                    if (editRes.ok) {
                        const editedBytes = await editRes.arrayBuffer();
                        if (editedBytes.byteLength > 0) {
                            bytes = editedBytes;
                            editedFound = true;
                        }
                    }
                } catch (e) {
                    // Versi edit gagal dibaca → lanjut dengan original.
                }

                if (!cancelled) {
                    setHasEdited(editedFound);
                    setBuffer(bytes);
                    setLoading(false);
                }
            } catch (e) {
                if (!cancelled) {
                    setError(e.message || 'Gagal memuat dokumen');
                    setLoading(false);
                }
            }
        }

        load();

        return () => {
            cancelled = true;
        };
    }, [originalUrl, editedUrl]);

    const handleSave = async () => {
        const buf = await editorRef.current?.save();
        if (!buf) return;

        setSaving(true);
        setMessage(null);
        try {
            const res = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: buf,
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                throw new Error(data.error || 'Gagal menyimpan dokumen');
            }

            setHasEdited(true);
            setMessage('Dokumen berhasil disimpan.');
        } catch (e) {
            setMessage({ type: 'error', text: e.message || 'Gagal menyimpan dokumen' });
        } finally {
            setSaving(false);
        }
    };

    const handleEditorError = (err) => {
        console.error('DOCX editor error:', err);
        setError('Dokumen gagal diproses editor. Coba muat ulang halaman.');
        setLoading(false);
    };

    return (
        <div className="flex flex-col" style={{ height: 'calc(100vh - 11.5rem)' }}>
            <div className="docx-editor-toolbar bg-white dark:bg-gray-800 shadow-sm rounded-t-lg px-4 py-3 flex flex-wrap items-center gap-3 border-b border-gray-200 dark:border-gray-700">
                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Edit Dokumen:</span>
                <button
                    type="button"
                    onClick={handleSave}
                    disabled={saving || loading}
                    className="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
                >
                    {saving ? 'Menyimpan...' : 'Simpan Perubahan'}
                </button>
                <a
                    href={pdfUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="px-4 py-2 text-sm bg-rose-600 text-white rounded-md hover:bg-rose-700"
                >
                    Cetak PDF
                </a>
                <button
                    type="button"
                    onClick={() => window.open(pdfUrl, '_blank')}
                    disabled={loading}
                    className="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                >
                    Print
                </button>
                <span className="text-xs text-gray-500 dark:text-gray-400">
                    {hasEdited ? 'Menampilkan versi edit. ' : ''}Klik Simpan Perubahan untuk menyimpan sebagai file terpisah. File asli tetap utuh.
                </span>
                {message && (
                    <span className={`text-sm ${message.type === 'error' ? 'text-rose-600 dark:text-rose-400' : 'text-green-600 dark:text-green-400'}`}>
                        {message.text}
                    </span>
                )}
            </div>

            <div className="flex-1 min-h-0 bg-gray-200 dark:bg-gray-900 rounded-b-lg overflow-y-auto">
                <div className="docx-editor-canvas h-full">
                {loading && (
                    <div className="h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                        Memuat dokumen...
                    </div>
                )}
                {error && !loading && (
                    <div className="h-full flex items-center justify-center text-rose-600">
                        {error}
                    </div>
                )}
                {buffer && !loading && !error && (
                    <DocxEditor
                        ref={editorRef}
                        document={buffer}
                        mode="edit"
                        title={`KAK #${kakId}`}
                        onError={handleEditorError}
                    />
                )}
                </div>
            </div>
        </div>
    );
}

document.addEventListener('DOMContentLoaded', () => {
    const rootEl = document.getElementById('docx-editor-root');
    if (!rootEl) return;

    createRoot(rootEl).render(
        <KakDocxEditor
            kakId={rootEl.dataset.kakId}
            originalUrl={rootEl.dataset.originalUrl}
            saveUrl={rootEl.dataset.saveUrl}
            editedUrl={rootEl.dataset.editedUrl}
            pdfUrl={rootEl.dataset.pdfUrl}
            csrfToken={rootEl.dataset.csrfToken}
        />
    );
});
