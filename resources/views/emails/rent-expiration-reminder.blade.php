<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $heading }}</title>
</head>
<body style="margin:0;padding:32px 16px;background-color:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <div style="max-width:560px;margin:0 auto;overflow:hidden;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:14px;">
        <div style="padding:22px 30px;background-color:#1d4e80;">
            <div style="font-size:21px;font-weight:700;letter-spacing:.2px;color:#ffffff;">nesaKost</div>
            <div style="margin-top:4px;font-size:12px;color:#dbeafe;">Informasi dan pengingat masa sewa</div>
        </div>

        <div style="padding:30px;">
            <p style="margin:0 0 8px;font-size:14px;color:#6b7280;">Halo, {{ $tenantName }}.</p>
            <h1 style="margin:0 0 16px;font-size:22px;line-height:1.35;color:#172b4d;">{{ $heading }}</h1>

            <p style="margin:0 0 22px;font-size:14px;line-height:1.8;color:#4b5563;">
                {{ $bodyText }}
            </p>

            <div style="margin:0 0 24px;padding:18px;background-color:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;">
                <div style="margin-bottom:12px;font-size:12px;font-weight:700;letter-spacing:.5px;color:#64748b;text-transform:uppercase;">
                    Detail Sewa
                </div>
                <table role="presentation" style="width:100%;border-collapse:collapse;font-size:13px;line-height:1.7;">
                    <tr>
                        <td style="padding:5px 0;color:#64748b;">Kode Sewa</td>
                        <td style="padding:5px 0;text-align:right;font-weight:600;color:#1f2937;">{{ $rent->rent_code }}</td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#64748b;">Kode Kamar</td>
                        <td style="padding:5px 0;text-align:right;font-weight:600;color:#1f2937;">{{ $rent->room?->room_code ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#64748b;">Tanggal Mulai</td>
                        <td style="padding:5px 0;text-align:right;font-weight:600;color:#1f2937;">{{ $rent->start_date?->format('d-m-Y') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:5px 0;color:#64748b;">Tanggal Berakhir</td>
                        <td style="padding:5px 0;text-align:right;font-weight:600;color:#1f2937;">{{ $rent->end_date?->format('d-m-Y') ?? '-' }}</td>
                    </tr>
                </table>
            </div>

            <div style="margin:0 0 24px;text-align:center;">
                <a href="{{ $actionUrl }}"
                   style="display:inline-block;padding:12px 22px;background-color:#1d4e80;border-radius:8px;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;">
                    {{ $actionLabel }}
                </a>
            </div>

            @if ($showRenewalAction)
                <p style="margin:0 0 18px;font-size:12px;line-height:1.7;color:#6b7280;">
                    Pengajuan perpanjangan mengikuti periode dan ketentuan yang tersedia di aplikasi. Pengajuan belum berarti perpanjangan telah disetujui atau dibayar.
                </p>
            @endif

            <p style="margin:0;font-size:12px;line-height:1.7;color:#9ca3af;">
                Email ini dikirim secara otomatis oleh nesaKost. Jika membutuhkan informasi lebih lanjut, silakan masuk ke akun nesaKost Anda.
            </p>
        </div>

        <div style="padding:16px 30px;background-color:#f8fafc;border-top:1px solid #e5e7eb;text-align:center;font-size:11px;color:#9ca3af;">
            © {{ date('Y') }} nesaKost. Pesan otomatis, mohon tidak membalas email ini.
        </div>
    </div>
</body>
</html>
