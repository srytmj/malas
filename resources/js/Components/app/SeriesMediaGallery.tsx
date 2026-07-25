import { useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { ImagePlus, Loader2, Trash2 } from 'lucide-react';
import { Button } from '@/Components/ui/button';

export interface SeriesMediaItem {
    id: string;
    image_url: string | null;
    caption: string | null;
}

interface SeriesMediaGalleryProps {
    seriesId: string;
    media: SeriesMediaItem[];
}

export function SeriesMediaGallery({ seriesId, media }: SeriesMediaGalleryProps) {
    const fileRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [deletingId, setDeletingId] = useState<string | null>(null);

    function handleFilesSelected(e: React.ChangeEvent<HTMLInputElement>) {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        const form = new FormData();
        Array.from(files).forEach((f) => form.append('images[]', f));

        setUploading(true);
        router.post(route('admin.series.media.store', seriesId), form, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                setUploading(false);
                if (fileRef.current) fileRef.current.value = '';
            },
        });
    }

    function handleDelete(id: string) {
        setDeletingId(id);
        router.delete(route('admin.series.media.destroy', id), {
            preserveScroll: true,
            onFinish: () => setDeletingId(null),
        });
    }

    return (
        <div className="mt-8">
            <div className="mb-3 flex items-center justify-between">
                <h2 className="text-base font-semibold">Galeri Media ({media.length})</h2>
                <input
                    ref={fileRef}
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    className="hidden"
                    onChange={handleFilesSelected}
                />
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={uploading}
                    onClick={() => fileRef.current?.click()}
                >
                    {uploading ? (
                        <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" />
                    ) : (
                        <ImagePlus className="mr-1.5 h-3.5 w-3.5" />
                    )}
                    {uploading ? 'Mengunggah...' : 'Tambah Gambar'}
                </Button>
            </div>

            {media.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    Belum ada media tambahan. Upload artwork, banner, atau screenshot untuk memperkaya halaman katalog series ini.
                </p>
            ) : (
                <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6">
                    {media.map((m) => (
                        <div key={m.id} className="group relative aspect-video overflow-hidden rounded-lg border bg-muted">
                            {m.image_url && (
                                <img src={m.image_url} alt={m.caption ?? ''} className="h-full w-full object-cover" />
                            )}
                            <button
                                type="button"
                                disabled={deletingId === m.id}
                                onClick={() => handleDelete(m.id)}
                                className="absolute right-1 top-1 rounded-md bg-black/60 p-1 text-white opacity-0 transition-opacity group-hover:opacity-100 disabled:opacity-100"
                            >
                                {deletingId === m.id ? (
                                    <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                ) : (
                                    <Trash2 className="h-3.5 w-3.5" />
                                )}
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
