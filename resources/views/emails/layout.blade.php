<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title ?? 'ACDM' }}</title>
</head>
<body style="margin:0;padding:0;background:#f4efe9;font-family:Georgia,'Times New Roman',serif;color:#2b2522;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4efe9;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:6px;overflow:hidden;">
    <tr>
        <td align="center" style="padding:26px 28px 18px;border-bottom:4px solid #8b1e2d;">
            <a href="{{ url('/') }}" style="text-decoration:none;">
                <img src="{{ asset('images/logo-newsletter.png') }}" width="402" height="96" alt="ACDM – Les Amis des Concerts du Dimanche Matin, Liège" style="display:block;width:402px;max-width:100%;height:auto;border:0;color:#8b1e2d;font-size:20px;font-style:italic;">
            </a>
            <div style="margin-top:10px;font-size:13px;letter-spacing:.12em;text-transform:uppercase;color:#6b625c;">Concerts ACDM &amp; FoliaMusica</div>
        </td>
    </tr>
    <tr><td style="padding:28px;font-size:16px;line-height:1.55;">
        {{ $slot }}
    </td></tr>
    <tr>
        <td style="padding:18px 28px;background:#f8f4ef;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#6b625c;line-height:1.5;">
            {{ $footer ?? '' }}
            ASBL Les Amis des Concerts du Dimanche Matin — {{ config('acdm.legal.street') }}, {{ config('acdm.legal.postal_code') }} {{ config('acdm.legal.city') }} — BCE {{ config('acdm.legal.enterprise_number') }}<br>
            <a href="{{ url('/') }}" style="color:#8b1e2d;">{{ preg_replace('#^https?://#', '', url('/')) }}</a>
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
