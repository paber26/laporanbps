import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { DocxEditor } from '@docx-editor.dev/react';
import '@docx-editor.dev/core/styles/editor.css';

function KakDocxEditor({ kakId, originalUrl, saveUrl, editedUrl, csrfToken }) {
    const editorRef = useRef(null);
    const [buffer, setBuffer] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState(null);

    useEffect(() => {
        let cancelled = false;

        // Lebih dulu pakai versi edit bila sudah ada; kalau tidak, original.
        fetch(editedUrl, { headers: { Accept: 'application/octet-stream' } })
            .then((r) => {
                if (r.ok) return r.arrayBuffer();
                return fetch(originalUrl).then((r2) => {
                    if (!r2.ok) throw new Error('Gagal memuat dokumen');
                    return r2.arrayBuffer();
                });
            })
            .then((buf) => {
                if (!cancelled) {
                    setBuffer(buf);
                    setLoading(false);
                }
            })
            .catch((e) => {
                if (!cancelled) {
                    setError(e.message || 'Gagal memuat dokumen');
                    setLoading(false);
                }
            });

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

            setMessage('Dokumen berhasil disimpan.');
        } catch (e) {
            setMessage({ type: 'error', text: e.message || 'Gagal menyimpan dokumen' });
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="flex flex-col" style={{ height: 'calc(100vh - 8rem)' }}>
            <div className="bg-white dark:bg-gray-800 shadow-sm rounded-t-lg px-4 py-3 flex flex-wrap items-center gap-3 border-b border-gray-200 dark:border-gray-700">
                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Edit Dokumen:</span>
                <button
                    type="button"
                    onClick={handleSave}
                    disabled={saving || loading}
                    className="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
                >
                    {saving ? 'Menyimpan...' : 'Simpan Perubahan'}
                </button>
                <span className="text-xs text-gray-500 dark:text-gray-400">Klik Simpan Perubahan untuk menyimpan sebagai file terpisah. File asli tetap utuh.</span>
                {message && (
                    <span className={`text-sm ${message.type === 'error' ? 'text-rose-600 dark:text-rose-400' : 'text-green-600 dark:text-green-400'}`}>
                        {message.text}
                    </span>
                )}
            </div>

            <div className="flex-1 min-h-0 bg-gray-200 dark:bg-gray-900 rounded-b-lg overflow-hidden">
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
                {buffer && !loading && (
                    <DocxEditor
                        ref={editorRef}
                        document={buffer}
                        mode="edit"
                        title={`KAK #${kakId}`}
                    />
                )}
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
            csrfToken={rootEl.dataset.csrfToken}
        />
    );
});
