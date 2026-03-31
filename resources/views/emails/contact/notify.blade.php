<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>Neue Kontaktanfrage</title>
</head>
<body style="margin:0;padding:0;background:#0b1120;font-family:Inter,Segoe UI,system-ui,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0b1120;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" style="max-width:560px;background:linear-gradient(165deg,#151e2e 0%,#0c1220 100%);border-radius:16px;overflow:hidden;border:1px solid rgba(255,122,31,0.22);box-shadow:0 24px 48px -24px rgba(0,0,0,0.85);">
                <tr>
                    <td style="padding:22px 26px 10px;">
                        <p style="margin:0;font-size:11px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#ff9466;">Mochi Kontakt-Hub</p>
                        <h1 style="margin:10px 0 0;font-size:20px;font-weight:800;color:#f8fafc;">Neue Nachricht</h1>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 26px 18px;">
                        <table role="presentation" width="100%" style="border-collapse:collapse;border-radius:12px;overflow:hidden;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                            <tr>
                                <td style="padding:14px 16px;font-size:13px;color:#94a3b8;width:100px;">Von</td>
                                <td style="padding:14px 16px;font-size:14px;color:#f1f5f9;font-weight:600;">{{ $record->name }}</td>
                            </tr>
                            <tr>
                                <td style="padding:14px 16px;font-size:13px;color:#94a3b8;border-top:1px solid rgba(255,255,255,0.06);">E-Mail</td>
                                <td style="padding:14px 16px;font-size:14px;border-top:1px solid rgba(255,255,255,0.06);">
                                    <a href="mailto:{{ $record->email }}" style="color:#ff9a4d;text-decoration:none;">{{ $record->email }}</a>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:14px 16px;font-size:13px;color:#94a3b8;border-top:1px solid rgba(255,255,255,0.06);">Betreff</td>
                                <td style="padding:14px 16px;font-size:14px;color:#f1f5f9;font-weight:600;border-top:1px solid rgba(255,255,255,0.06);">{{ $record->subject->label() }}</td>
                            </tr>
                        </table>
                        <p style="margin:18px 0 6px;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Nachricht</p>
                        <div style="padding:16px 18px;border-radius:12px;background:rgba(4,7,18,0.65);border:1px solid rgba(255,255,255,0.08);color:#e2e8f0;font-size:14px;line-height:1.55;white-space:pre-wrap;">{{ $record->message }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:6px 26px 24px;">
                        <a href="{{ $adminUrl }}" style="display:inline-block;padding:12px 22px;border-radius:999px;background:linear-gradient(90deg,#ff7a1f,#ffb547);color:#0b0f16;font-size:13px;font-weight:800;text-decoration:none;box-shadow:0 12px 32px -12px rgba(255,122,31,0.55);">Im Admin öffnen</a>
                    </td>
                </tr>
            </table>
            <p style="margin:20px 0 0;font-size:11px;color:#64748b;">{{ config('app.name') }} · automatische Benachrichtigung</p>
        </td>
    </tr>
</table>
</body>
</html>
