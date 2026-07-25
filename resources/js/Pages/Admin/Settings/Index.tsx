import { useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import {
    AlertTriangle, CheckCircle2, Database, Download, Eye, HardDrive, Loader2, Save, Shield, Upload, XCircle, Zap,
} from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Switch } from '@/Components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { PageProps } from '@/types';

interface StorageSettingData {
    driver: 'local' | 's3';
    access_key_id: string | null;
    bucket: string | null;
    endpoint: string | null;
    region: string | null;
    url: string | null;
    has_secret: boolean;
    migration_status: 'idle' | 'running' | 'completed' | 'failed';
    migration_message: string | null;
}

interface Props extends PageProps {
    setting: StorageSettingData;
}

const storageSchema = z.object({
    driver:             z.enum(['local', 's3']),
    access_key_id:      z.string().optional(),
    secret_access_key:  z.string().optional(),
    bucket:             z.string().optional(),
    endpoint:           z.string().optional(),
    region:             z.string().optional(),
    url:                z.string().optional(),
});
type StorageFormValues = z.infer<typeof storageSchema>;

function FieldError({ message }: { message?: string }) {
    if (!message) return null;
    return <p className="text-xs text-destructive">{message}</p>;
}

function StorageTab({ setting }: { setting: StorageSettingData }) {
    const [submitting, setSubmitting] = useState(false);
    const [testing, setTesting]       = useState(false);
    const [testResult, setTestResult] = useState<{ success: boolean; message: string } | null>(null);

    const {
        register, control, handleSubmit, setError, watch,
        formState: { errors },
    } = useForm<StorageFormValues>({
        resolver: zodResolver(storageSchema),
        defaultValues: {
            driver:            setting.driver,
            access_key_id:     setting.access_key_id ?? '',
            secret_access_key: '',
            bucket:            setting.bucket ?? '',
            endpoint:          setting.endpoint ?? '',
            region:            setting.region ?? '',
            url:               setting.url ?? '',
        },
    });

    const driver = watch('driver');

    function onSubmit(values: StorageFormValues) {
        setSubmitting(true);
        router.put(route('admin.settings.storage.update'), values, {
            onError: (errs) => {
                Object.entries(errs).forEach(([k, msg]) => {
                    setError(k as keyof StorageFormValues, { message: msg as string });
                });
            },
            onFinish: () => setSubmitting(false),
        });
    }

    async function testConnection() {
        setTesting(true);
        setTestResult(null);
        const values = watch();
        try {
            const res = await window.axios.post<{ success: boolean; message: string }>(
                route('admin.settings.storage.test'),
                {
                    access_key_id:     values.access_key_id,
                    secret_access_key: values.secret_access_key,
                    bucket:            values.bucket,
                    endpoint:          values.endpoint,
                    region:            values.region,
                    url:               values.url,
                },
            );
            setTestResult(res.data);
        } catch {
            setTestResult({ success: false, message: 'Gagal menghubungi server.' });
        } finally {
            setTesting(false);
        }
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                    <HardDrive className="h-4 w-4" />
                    Konfigurasi Storage
                </CardTitle>
                <CardDescription>
                    Atur lokasi penyimpanan file cover — lokal atau S3-compatible (misal Cloudflare R2).
                    Saat driver diganti, file yang sudah ada otomatis dipindahkan ke lokasi baru di latar belakang.
                </CardDescription>
            </CardHeader>
            <CardContent>
                {setting.migration_status !== 'idle' && (
                    <div
                        className={`mb-4 flex items-start gap-2 rounded-md p-3 text-sm ${
                            setting.migration_status === 'running'
                                ? 'bg-muted text-muted-foreground'
                                : setting.migration_status === 'completed'
                                    ? 'bg-green-500/10 text-green-700 dark:text-green-400'
                                    : 'bg-destructive/10 text-destructive'
                        }`}
                    >
                        {setting.migration_status === 'running' && <Loader2 className="mt-0.5 h-4 w-4 shrink-0 animate-spin" />}
                        {setting.migration_status === 'completed' && <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />}
                        {setting.migration_status === 'failed' && <XCircle className="mt-0.5 h-4 w-4 shrink-0" />}
                        <span>
                            {setting.migration_status === 'running'
                                ? 'Sedang memindahkan file ke lokasi storage baru...'
                                : setting.migration_message}
                        </span>
                    </div>
                )}
                <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                    <div className="space-y-1.5">
                        <Label>Driver</Label>
                        <Controller<StorageFormValues, 'driver'>
                            control={control}
                            name="driver"
                            render={({ field }) => (
                                <Select value={field.value} onValueChange={field.onChange}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="local">Local (disk server)</SelectItem>
                                        <SelectItem value="s3">S3-compatible (R2, AWS S3, dll)</SelectItem>
                                    </SelectContent>
                                </Select>
                            )}
                        />
                    </div>

                    {driver === 's3' && (
                        <div className="space-y-4 rounded-lg border p-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="access_key_id">Access Key ID <span className="text-destructive">*</span></Label>
                                <Input id="access_key_id" {...register('access_key_id')} />
                                <FieldError message={errors.access_key_id?.message} />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="secret_access_key">
                                    Secret Access Key {!setting.has_secret && <span className="text-destructive">*</span>}
                                </Label>
                                <Input
                                    id="secret_access_key"
                                    type="password"
                                    placeholder={setting.has_secret ? 'Biarkan kosong untuk tetap pakai yang lama' : ''}
                                    {...register('secret_access_key')}
                                />
                                <FieldError message={errors.secret_access_key?.message} />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="bucket">Bucket <span className="text-destructive">*</span></Label>
                                    <Input id="bucket" {...register('bucket')} />
                                    <FieldError message={errors.bucket?.message} />
                                </div>
                                <div className="space-y-1.5">
                                    <Label htmlFor="region">Region</Label>
                                    <Input id="region" placeholder="auto" {...register('region')} />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="endpoint">Endpoint <span className="text-destructive">*</span></Label>
                                <Input id="endpoint" placeholder="https://xxxx.r2.cloudflarestorage.com" {...register('endpoint')} />
                                <FieldError message={errors.endpoint?.message} />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="url">URL Publik <span className="text-destructive">*</span></Label>
                                <Input id="url" placeholder="https://pub-xxxx.r2.dev" {...register('url')} />
                                <FieldError message={errors.url?.message} />
                            </div>

                            {testResult && (
                                <div className={`flex items-start gap-2 rounded-md p-3 text-sm ${testResult.success ? 'bg-green-500/10 text-green-700 dark:text-green-400' : 'bg-destructive/10 text-destructive'}`}>
                                    {testResult.success ? <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" /> : <XCircle className="mt-0.5 h-4 w-4 shrink-0" />}
                                    <span>{testResult.message}</span>
                                </div>
                            )}

                            <Button type="button" variant="outline" size="sm" disabled={testing} onClick={testConnection}>
                                <Zap className="mr-1.5 h-3.5 w-3.5" />
                                {testing ? 'Menguji...' : 'Tes Koneksi'}
                            </Button>
                        </div>
                    )}

                    <Button type="submit" disabled={submitting}>
                        <Save className="mr-1.5 h-3.5 w-3.5" />
                        {submitting ? 'Menyimpan...' : 'Simpan'}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}

function DatabaseTab() {
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
        <div className="space-y-6">
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
        </div>
    );
}

function ContentTab() {
    const { site_settings } = usePage<PageProps>().props;
    const [blurAdult, setBlurAdult] = useState(site_settings.blur_adult_content);
    const [saving, setSaving] = useState(false);

    function handleToggle(checked: boolean) {
        setBlurAdult(checked);
        setSaving(true);
        router.put(route('admin.settings.content.update'), { blur_adult_content: checked }, {
            preserveScroll: true,
            onError: () => setBlurAdult(!checked),
            onFinish: () => setSaving(false),
        });
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                    <Shield className="h-4 w-4" />
                    Konten 18+
                </CardTitle>
                <CardDescription>
                    Series yang ditandai 18+ saat import dari AniList akan otomatis di-blur di halaman katalog dan koleksi
                    sampai pengguna klik untuk melihatnya — mirip Reddit/Instagram.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="flex items-center justify-between rounded-lg border p-4">
                    <div className="flex items-start gap-3">
                        <Eye className="mt-0.5 h-4 w-4 text-muted-foreground" />
                        <div>
                            <p className="text-sm font-medium">Blur konten 18+ secara default</p>
                            <p className="text-xs text-muted-foreground">
                                Berlaku untuk semua pengguna. Cover akan diblur, klik untuk mengungkap sementara.
                            </p>
                        </div>
                    </div>
                    <Switch checked={blurAdult} onCheckedChange={handleToggle} disabled={saving} />
                </div>
            </CardContent>
        </Card>
    );
}

export default function SettingsIndex({ setting }: Props) {
    return (
        <AdminLayout
            header={
                <PageHeader
                    title="Pengaturan"
                    description="Konfigurasi penyimpanan file, backup database, dan konten."
                />
            }
        >
            <Head title="Pengaturan" />

            <div className="max-w-2xl">
                <Tabs defaultValue="storage">
                    <TabsList className="mb-4">
                        <TabsTrigger value="storage">Penyimpanan</TabsTrigger>
                        <TabsTrigger value="database">Database</TabsTrigger>
                        <TabsTrigger value="content">Konten</TabsTrigger>
                    </TabsList>
                    <TabsContent value="storage">
                        <StorageTab setting={setting} />
                    </TabsContent>
                    <TabsContent value="database">
                        <DatabaseTab />
                    </TabsContent>
                    <TabsContent value="content">
                        <ContentTab />
                    </TabsContent>
                </Tabs>
            </div>
        </AdminLayout>
    );
}
