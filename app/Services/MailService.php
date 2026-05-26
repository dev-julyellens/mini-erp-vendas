<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Helpers\MailConfig;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PhpMailerException;

final class MailService
{
    public function sendPasswordReset(string $toEmail, string $userName, string $resetUrl): bool
    {
        $appName = 'Mini ERP de Vendas';
        $subject = $appName . ' — Redefinição de senha';
        $safeName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<p>Olá, {$safeName}.</p>
<p>Recebemos uma solicitação para redefinir a senha da sua conta.</p>
<p><a href="{$safeUrl}">Clique aqui para criar uma nova senha</a></p>
<p>O link expira em 2 horas. Se você não solicitou esta alteração, ignore este e-mail.</p>
<p style="color:#666;font-size:12px;">{$appName}</p>
HTML;

        $text = "Olá, {$userName}.\n\n"
            . "Acesse o link abaixo para redefinir sua senha (válido por 2 horas):\n"
            . "{$resetUrl}\n\n"
            . "Se você não solicitou, ignore este e-mail.\n";

        return $this->send($toEmail, $subject, $html, $text);
    }

    public function send(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        $to = trim($to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL))
        {
            return false;
        }

        return match (MailConfig::driver())
        {
            'smtp', 'mail' => $this->sendViaPhpMailer($to, $subject, $htmlBody, $textBody),
            default => $this->sendViaLog($to, $subject, $htmlBody, $textBody),
        };
    }

    private function sendViaLog(string $to, string $subject, string $htmlBody, ?string $textBody): bool
    {
        $path = dirname(__DIR__, 2) . '/storage/logs/mail.log';
        $dir = dirname($path);
        if (!is_dir($dir))
        {
            mkdir($dir, 0755, true);
        }

        $entry = sprintf(
            "[%s] TO=%s SUBJECT=%s\n%s\n---\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $textBody ?? strip_tags($htmlBody)
        );

        $written = file_put_contents($path, $entry, FILE_APPEND | LOCK_EX);

        if ($written === false)
        {
            Logger::warning('Falha ao gravar e-mail no log.', ['to' => $to]);

            return false;
        }

        Logger::info('E-mail gravado em storage/logs/mail.log (MAIL_DRIVER=log).', ['to' => $to]);

        return true;
    }

    private function sendViaPhpMailer(string $to, string $subject, string $htmlBody, ?string $textBody): bool
    {
        try
        {
            $mail = $this->createMailer();
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody ?? strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            $mail->send();

            Logger::info('E-mail enviado.', [
                'to' => $to,
                'driver' => MailConfig::driver(),
            ]);

            return true;
        }
        catch (PhpMailerException $e)
        {
            Logger::exception($e, 'PHPMailer: falha ao enviar e-mail.', ['to' => $to]);

            return false;
        }
    }

    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom(MailConfig::fromAddress(), MailConfig::fromName());

        if (MailConfig::driver() === 'mail')
        {
            $mail->isMail();

            return $mail;
        }

        $host = MailConfig::smtpHost();
        if ($host === '')
        {
            throw new PhpMailerException('MAIL_SMTP_HOST não configurado.');
        }

        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = MailConfig::smtpPort();
        $mail->SMTPAuth = MailConfig::smtpAuth();

        $user = MailConfig::smtpUser();
        if ($mail->SMTPAuth && $user !== '')
        {
            $mail->Username = $user;
            $mail->Password = MailConfig::smtpPassword();
        }

        $encryption = MailConfig::smtpEncryption();
        $mail->SMTPSecure = match ($encryption)
        {
            'ssl' => PHPMailer::ENCRYPTION_SMTPS,
            'tls' => PHPMailer::ENCRYPTION_STARTTLS,
            default => '',
        };

        if (MailConfig::smtpDebug())
        {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = static function (string $str, int $level): void
            {
                Logger::debug('PHPMailer SMTP', ['level' => $level, 'message' => trim($str)]);
            };
        }

        return $mail;
    }
}
