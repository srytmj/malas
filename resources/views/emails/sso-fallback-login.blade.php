<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; background:#f5f5f5; padding:32px 0; margin:0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; padding:32px;">
                    <tr>
                        <td style="font-size:20px; font-weight:700; color:#111827; padding-bottom:16px;">Malas</td>
                    </tr>
                    <tr>
                        <td style="font-size:14px; color:#374151; line-height:1.6; padding-bottom:8px;">
                            Halo {{ $user->name }},
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:14px; color:#374151; line-height:1.6; padding-bottom:24px;">
                            Ada permintaan login ke Malas tanpa lewat SSO (whitearchive.id). Kalau ini kamu, klik tombol di bawah untuk masuk. Link ini cuma berlaku 15 menit dan cuma bisa dipakai sekali.
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-bottom:24px;">
                            <a href="{{ $loginUrl }}" style="background:#111827; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:6px; font-size:14px; font-weight:600; display:inline-block;">
                                Login ke Malas
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size:12px; color:#9ca3af; line-height:1.6;">
                            Kalau bukan kamu yang minta ini, abaikan saja email ini — tidak ada yang berubah di akunmu.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
