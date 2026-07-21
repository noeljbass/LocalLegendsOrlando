<?php
require_once __DIR__ . '/functions.php';

function load_phpmailer(): bool {
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (file_exists($autoload)) { require_once $autoload; return class_exists('PHPMailer\\PHPMailer\\PHPMailer'); }
    foreach (['PHPMailer.php','SMTP.php','Exception.php'] as $file) {
        $path = dirname(__DIR__) . '/PHPMailer/src/' . $file;
        if (file_exists($path)) { require_once $path; }
    }
    return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
}

function send_funnel_email(array $subscriber, array $client, array $template, ?string $toOverride = null): array {
    if (!load_phpmailer()) { return ['success'=>false, 'error'=>'PHPMailer not found. Install PHPMailer in vendor/ or PHPMailer/src/.']; }
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $client['smtp_host'] ?: config('smtp.host');
        $mail->Port = (int)($client['smtp_port'] ?: config('smtp.port', 587));
        $mail->SMTPAuth = true;
        $mail->Username = $client['smtp_username'] ?: config('smtp.username');
        $mail->Password = config('smtp.password');
        $enc = $client['smtp_encryption'] ?: config('smtp.encryption');
        if ($enc) { $mail->SMTPSecure = $enc; }
        $fromEmail = $client['from_email'] ?: config('default_from_email');
        $fromName = $client['from_name'] ?: config('default_from_name');
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toOverride ?: $subscriber['email'], $subscriber['name']);
        $mail->isHTML(true);
        $mail->Subject = render_template($template['subject'], $subscriber, $client);
        $html = render_template($template['html_body'], $subscriber, $client);
        if (stripos($html, '{{unsubscribe_url}}') === false && stripos($html, 'unsubscribe') === false) {
            $html .= '<p><a href="' . e(unsubscribe_url($subscriber['unsubscribe_token'])) . '">Unsubscribe</a></p>';
        }
        $plain = render_template($template['plain_text_body'] ?: strip_tags($html), $subscriber, $client);
        if (stripos($plain, 'unsubscribe') === false) { $plain .= "\n\nUnsubscribe: " . unsubscribe_url($subscriber['unsubscribe_token']); }
        $mail->Body = $html;
        $mail->AltBody = $plain;
        $mail->send();
        return ['success'=>true, 'error'=>null, 'subject'=>$mail->Subject];
    } catch (Throwable $e) {
        log_error_message('Mailer error: ' . $e->getMessage());
        return ['success'=>false, 'error'=>$e->getMessage(), 'subject'=>$mail->Subject ?? $template['subject']];
    }
}
