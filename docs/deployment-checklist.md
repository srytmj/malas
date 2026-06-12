# Deployment Checklist

## Pre-Deployment Checklist

- [ ] Semua test passing (unit, feature, integration)
- [ ] Code style sudah dicek (Pint)
- [ ] Static analysis (PHPStan level 5+) passing
- [ ] Database backup terbaru sudah ada di R2
- [ ] Environment variables sudah diisi di GitHub Secrets
- [ ] SSH key sudah terdaftar di server

## Deployment Steps

1. [ ] Push ke branch `develop` → cek staging
2. [ ] Testing di staging (manual smoke test)
3. [ ] Buat tag release: `git tag v1.0.0 && git push --tags`
4. [ ] Approve deployment di GitHub Actions
5. [ ] Pantau smoke test otomatis
6. [ ] Cek queue worker: `php artisan horizon:status`

## Rollback Steps

1. [ ] Eksekusi rollback script: `bash deploy/rollback.sh`
2. [ ] Cek migration rollback (jika perlu)
3. [ ] Restart queue: `php artisan queue:restart`
4. [ ] Verifikasi dengan smoke test manual

## Post-Deployment Checklist

- [ ] Health check endpoint (`GET /api/health`) merespons 200
- [ ] Login endpoint berfungsi normal
- [ ] Public series listing dapat diakses
- [ ] Cek log error di Laravel Telescope (non-production) atau monitoring eksternal (Oh Dear / Uptime Robot)
- [ ] Cek tidak ada job yang menumpuk di `failed_jobs`
- [ ] Catat versi yang dideploy beserta waktu dan siapa yang approve