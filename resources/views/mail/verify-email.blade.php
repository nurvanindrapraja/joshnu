@php($setting = \App\Models\JournalSetting::current())
@php($j = $setting->short_name ?? 'JOSHNU')
<div class="bg-gray-50" style="padding: 24px; font-family: Inter, -apple-system, 'Segoe UI', Roboto, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
        <div style="background: #1b6e5a; padding: 20px 32px; color: #ffffff;">
            <p style="margin: 0; font-size: 15px; font-weight: 700; letter-spacing: 0.02em;">{{ $j }}</p>
            <p style="margin: 2px 0 0; font-size: 12px; opacity: 0.85;">Journal of Sustainable Health and New Urbanism</p>
        </div>
        <div style="padding: 32px;">
            <h1 style="margin: 0 0 16px; font-size: 18px; font-weight: 700; color: #111827;">Verifikasi Alamat Email</h1>

            <p style="margin: 0 0 16px; font-size: 14px; color: #374151; line-height: 1.6;">
                Yth. {{ $user->name }},<br><br>
                Terima kasih telah mendaftar di {{ $j }}. Untuk mengaktifkan akun Anda, silakan klik tombol di bawah ini untuk memverifikasi alamat email <strong>{{ $user->email }}</strong>. Setelah terverifikasi, Anda dapat melakukan login.
            </p>

            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 0 16px;">
                <tr>
                    <td style="border-radius: 8px; background: #1b6e5a;">
                        <a href="{{ $url }}" style="display: inline-block; padding: 12px 28px; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none;">Verifikasi Email</a>
                    </td>
                </tr>
            </table>

            <p style="margin: 0 0 8px; font-size: 13px; color: #6b7280; line-height: 1.6;">
                Atau salin tautan berikut ke browser:<br>
                <a href="{{ $url }}" style="color: #1b6e5a; word-break: break-all; text-decoration: none;">{{ $url }}</a>
            </p>

            <p style="margin: 0; font-size: 12px; color: #9ca3af; line-height: 1.6;">
                Tautan ini berlaku 2 hari. Jika Anda tidak mendaftar di {{ $j }}, abaikan email ini.
            </p>
        </div>
        <div style="padding: 16px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0; font-size: 11px; color: #9ca3af;">Email ini dikirim otomatis oleh sistem {{ $j }}. Mohon balas email ini.</p>
        </div>
    </div>
</div>
