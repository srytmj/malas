import { useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { AlertTriangle, Database, Download, Upload } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { PageProps } from '@/types';

export default function DatabaseBackup(_props: PageProps) {
    const fileRef                         = useRef<HTMLInputElement>(null);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [confirmOpen, setConfirmOpen]   = useState(false);
    const [importing, setImporting]       = useState(false);

    function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0] ?? null;
        setSelectedFile(file);
    }

    function handleImport() {
        if (!selectedFile) return;
        setImporting(true);
        const form = new FormData();
        form.append('backup_file', selectedFile);
        router.post(route('admin.settings.db.import'), form, {
            forceFormData: true,
            onFinish: () => {
                setImporting(false);
                setConfirmOpen(false);
                setSelectedFile(null);
                if (fileRef.current) fileRef.current.value = '';
            },
        });
    }

    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Backup & Import Database"
                    description="Download snapshot database atau pulihkan dari backup sebelumnya."
                    breadcrumbs={[
                        { label: 'Pengaturan' },
                        { label: 'Database' },
                    ]}
                />
            }
        >
            <Head title="Backup Database" />

            <div className="max-w-2xl space-y-6">
                {/* Download */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Download className="h-4 w-4" />
                            Download Backup
                        </CardTitle>
                        <CardDescription>
                            Download semua data (series, volume, koleksi, pinjaman, tiket, pengumuman, dll)
                            sebagai file <code className="text-xs">.sql</code>.
                            Data user tidak ikut di-backup karena dikelola via SSO.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <a
                            href={route('admin.settings.db.download')}
                            className="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90"
                        >
                            <Database className="h-4 w-4" />
                            Download Backup Sekarang
                        </a>
                        <p className="mt-2 text-xs text-muted-foreground">
                            File akan bernama <code>malas-backup-YYYY-MM-DD-HHmmss.sql</code>
                        </p>
                    </CardContent>
                </Card>

                {/* Import */}
                <Card className="border-destructive/30">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Upload className="h-4 w-4" />
                            Import dari Backup
                        </CardTitle>
                        <CardDescription>
                            Pulihkan data dari file backup yang diunduh sebelumnya.
                            <strong className="text-destructive"> Semua data yang ada akan diganti.</strong>
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-start gap-3 rounded-md border border-amber-500/40 bg-amber-500/10 p-3 text-sm text-amber-700 dark:text-amber-400">
                            <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                            <div>
                                <p className="font-medium">Perhatian sebelum import:</p>
                                <ul className="mt-1 list-disc space-y-0.5 pl-4 text-xs">
                                    <li>Semua data series, koleksi, pinjaman, tiket akan <strong>dihapus dan diganti</strong> dengan data dari file backup.</li>
                                    <li>Data user tidak terpengaruh (dikelola via SSO).</li>
                                    <li>Proses ini tidak bisa dibatalkan — pastikan sudah download backup terbaru sebelum import.</li>
                                    <li>Hanya file backup yang dihasilkan dari halaman ini yang diterima.</li>
                                </ul>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium">File Backup (.sql)</label>
                            <input
                                ref={fileRef}
                                type="file"
                                accept=".sql,.txt"
                                onChange={handleFileChange}
                                className="block w-full text-sm text-muted-foreground file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-muted/80"
                            />
                        </div>

                        <Button
                            variant="destructive"
                            disabled={!selectedFile || importing}
                            onClick={() => setConfirmOpen(true)}
                        >
                            <Upload className="mr-1.5 h-3.5 w-3.5" />
                            {importing ? 'Mengimpor...' : 'Import'}
                            {selectedFile && !importing ? ` — ${selectedFile.name}` : ''}
                        </Button>
                    </CardContent>
                </Card>
            </div>

            {/* Konfirmasi import */}
            <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Konfirmasi Import Database</DialogTitle>
                        <DialogDescription>
                            Kamu akan mengimpor <strong>{selectedFile?.name}</strong>.
                            Semua data yang ada akan dihapus dan digantikan dengan data dari file ini.
                            Tindakan ini tidak bisa dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    <p className="text-sm text-muted-foreground">
                        Sudah yakin? Data user tidak akan terpengaruh.
                    </p>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setConfirmOpen(false)}>
                            Batal
                        </Button>
                        <Button variant="destructive" disabled={importing} onClick={handleImport}>
                            {importing ? 'Mengimpor...' : 'Ya, Import Sekarang'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    );
}
