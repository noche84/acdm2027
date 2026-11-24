<x-mail-layout title="Confirmez votre inscription">
    <p>Bonjour,</p>
    <p>Vous avez demandé à recevoir la newsletter des Amis des Concerts du Dimanche Matin (ACDM) et de FoliaMusica.</p>
    <p>Pour confirmer votre inscription, cliquez sur le bouton ci-dessous :</p>
    <p style="text-align:center;margin:28px 0;">
        <a href="{{ $subscriber->confirm_url }}" style="background:#8b1e2d;color:#ffffff;text-decoration:none;padding:12px 22px;border-radius:4px;font-family:Arial,Helvetica,sans-serif;">Je confirme mon inscription</a>
    </p>
    <p style="font-size:14px;color:#6b625c;">Si vous n'êtes pas à l'origine de cette demande, ignorez simplement ce message : vous ne recevrez rien et votre adresse sera effacée dans 30 jours.</p>
</x-mail-layout>
