import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { CheckCircle2, HardDrive, Save, XCircle, Zap } from 'lucide-react';
import AdminLayout from '@/Layouts/AdminLayout';
import PageHeader from '@/Components/app/PageHeader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { PageProps } from '@/types';

interface StorageSettingData {
    driver: 'local' | 's3';
    access_key_id: string | null;
    bucket: string | null;
    endpoint: string | null;
    region: string | null;
    url: string | null;
    has_secret: boolean;
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

export default function StorageSettingsPage({ setting }: Props) {
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
        <AdminLayout
            header={
                <PageHeader
                    title="Penyimpanan"
                    description="Atur lokasi penyimpanan file cover — lokal atau S3-compatible (misal Cloudflare R2)."
                />
            }
        >
            <Head title="Penyimpanan" />

            <div className="max-w-xl">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <HardDrive className="h-4 w-4" />
                            Konfigurasi Storage
                        </CardTitle>
                        <CardDescription>
                            Perubahan cuma berlaku buat file baru — file yang sudah ada di disk lama tidak otomatis dipindah.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
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
            </div>
        </AdminLayout>
    );
}
