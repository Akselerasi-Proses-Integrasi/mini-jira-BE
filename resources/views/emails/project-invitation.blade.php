<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $subject ?? 'Undangan Proyek' }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 6px; }
        .header { background: #4f46e5; color: #fff; padding: 16px 24px; border-radius: 6px 6px 0 0; margin: -20px -20px 20px; }
        .footer { margin-top: 24px; font-size: 12px; color: #888; }
        .btn { display: inline-block; padding: 12px 24px; background: #4f46e5; color: #fff; text-decoration: none; border-radius: 6px; margin: 16px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Undangan Proyek — {{ config('app.name') }}</h2>
        </div>

        <p>Halo,</p>

        @if($isExistingUser)
            <p><strong>{{ $inviterName }}</strong> telah menambahkan kamu sebagai <strong>{{ $role }}</strong> pada proyek <strong>{{ $projectName }}</strong>.</p>
            <p>Kamu dapat langsung mengakses proyek tersebut setelah login.</p>
        @else
            <p><strong>{{ $inviterName }}</strong> mengundang kamu untuk bergabung ke proyek <strong>{{ $projectName }}</strong> dengan peran <strong>{{ $role }}</strong>.</p>
            <p>Klik tombol di bawah untuk menerima undangan:</p>
            <a href="{{ $invitationUrl }}" class="btn">Terima Undangan</a>
            <p>Atau salin tautan ini: <br><code>{{ $invitationUrl }}</code></p>
            <p>Tautan ini akan kadaluarsa dalam 7 hari.</p>
        @endif

        <div class="footer">
            <p>Email ini dikirim secara otomatis. Mohon tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>