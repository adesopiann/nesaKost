<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi nesaKost</title>
</head>
<body style="margin:0;padding:32px 16px;background-color:#f5f6f8;font-family:Arial,Helvetica,sans-serif;color:#202124;">
    <div style="max-width:520px;margin:0 auto;padding:32px;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:12px;">
        <h1 style="margin:0 0 16px;font-size:22px;color:#202124;">Verifikasi Email nesaKost</h1>
        <p style="margin:0 0 16px;font-size:14px;line-height:1.7;">Gunakan kode berikut untuk menyelesaikan verifikasi alamat email akun nesaKost kamu.</p>
        <div style="margin:24px 0;padding:18px;background-color:#f3f4f6;border-radius:8px;text-align:center;">
            <span style="font-size:32px;font-weight:700;letter-spacing:8px;color:#111827;">{{ $otp }}</span>
        </div>
        <p style="margin:0 0 12px;font-size:14px;line-height:1.7;">Kode ini berlaku selama <strong>{{ $expiresInMinutes }} menit</strong>.</p>
        <p style="margin:0 0 16px;font-size:13px;line-height:1.7;color:#6b7280;">Jangan bagikan kode ini kepada siapa pun. Tim nesaKost tidak akan pernah meminta kode verifikasi kamu.</p>
        <p style="margin:24px 0 0;padding-top:16px;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;">Jika kamu tidak merasa melakukan pendaftaran atau meminta kode ini, abaikan email ini.</p>
    </div>
</body>
</html>
