<?php

namespace Indo\Mail;

use Psr\Log\LogLevel;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client;
use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use PHPMailer\PHPMailer\PHPMailer;

use codesaur\Http\Client\Mail;

use Indoraptor\Logger\LoggerModel;

class IndoMail extends Mail
{
    public function __construct(
        string $from = 'codesaur+noreply@gmail.com', // this is your email address sending from
        string $fromName = '',
        string $replyTo = '',
        string $replyToName = ''
    ) {
        $this->setFrom($from, $fromName);
        if (!empty($replyTo)) {
            $this->setReplyTo($replyTo, $replyToName);
        }
    }
    
    public function sendSMTP(): bool
    {
        $host = 'yourhost';
        $port = 465/*587*//*25*/;
        $username = 'username@yourhost.com';
        $password = 'password';
        $smtp_secure = 'ssl';
        $smtp_options = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        $this->assertValues();

        $mailer = new PHPMailer(
            \defined('CODESAUR_DEVELOPMENT')
            && CODESAUR_DEVELOPMENT ? true : null
        );
        $mailer->isSMTP();
        $mailer->CharSet = 'UTF-8';
        $mailer->SMTPAuth = true;
        $mailer->SMTPSecure = $smtp_secure;
        $mailer->Host = $host;
        $mailer->Port = $port;
        $mailer->Username = $username;
        $mailer->Password = $password;
        $mailer->setFrom($this->from, $this->fromName);
        if (empty($this->replyTo)) {
            $replyTo = $this->from;
            $replyToName = $this->fromName;
        } else {
            $replyTo = $this->replyTo;
            $replyToName = $this->replyToName;
        }
        $mailer->addReplyTo($replyTo, $replyToName);
        $mailer->SMTPOptions = $smtp_options;
        $mailer->msgHTML($this->message);
        $mailer->Subject = $this->subject;
        foreach ($this->getRecipients('To') as $to) {
            $mailer->addAddress($to['email'], $to['name'] ?? '');
        }
        foreach ($this->getRecipients('Cc') as $cc) {
            $mailer->addCC($cc['email'], $cc['name'] ?? '');
        }
        foreach ($this->getRecipients('Bcc') as $bcc) {
            $mailer->addBCC($bcc['email'], $bcc['name'] ?? '');
        }

        foreach ($this->getAttachments() as $attachment) {
            if (isset($attachment['path'])) {
                $mailer->addAttachment($attachment['path'], $attachment['name']);
            } elseif (isset($attachment['url'])) {
                throw new \Exception('Explicitly *does not* support passing URLs; PHPMailer is not an HTTP client.');
            } elseif (isset($attachment['content'])) {
                $mailer->addStringAttachment($attachment['content'], $attachment['name']);
            }
        }

        if (!empty($this->languageCode)) {
            $mailer->setLanguage($this->languageCode);
        }

        return $mailer->send();
    }
    
    public function sendBrevoTransactional(): array
    {
        $this->assertValues();
        
        $credentials = Configuration::getDefaultConfiguration()->setApiKey(
            'api-key',
            'your-api-key' // your Brevo API key
        );
        $apiInstance = new TransactionalEmailsApi(new Client(), $credentials);
        $options = [
            'subject' => $this->subject,
            'htmlContent' => $this->message,
            'to' => $this->getRecipients('To')
        ];
        $cc = $this->getRecipients('Cc');
        if (!empty($cc)) {
            $options['cc'] = $cc;
        }
        $bcc = $this->getRecipients('Bcc');
        if (!empty($bcc)) {
            $options['bcc'] = $bcc;
        }        
        if (!empty($this->fromName)) {
            $options['sender'] = ['name' => $this->fromName, 'email' => $this->from];
        } else {
            $options['sender'] = ['email' => $this->from];
        }
        if (!empty($this->replyTo)) {
            if (!empty($this->replyToName)) {
                $options['replyTo'] = ['name' => $this->replyToName, 'email' => $this->replyTo];
            } else {
                $options['replyTo'] = ['email' => $this->replyTo];
            }
        }
        
        $attachments = [];
        foreach ($this->getAttachments() as $attachment) {
            if (isset($attachment['path'])) {
                throw new \Exception("Brevo's SendSmtpEmail doesn't support local file!");
            } elseif (isset($attachment['url'])) {
                $attachments[] = ['url' => $attachment['url'], 'name' => $attachment['name']];
            } elseif (isset($attachment['content'])) {
                $attachments[] = ['content' => $attachment['content'], 'name' => $attachment['name']];
            }
        }
        if (!empty($attachments)) {
            $options['attachment'] = $attachments;
        }
        
        $sendSmtpEmail = new SendSmtpEmail($options);
        return (array) $apiInstance->sendTransacEmail($sendSmtpEmail);
    }
}

class MailerController extends \Indoraptor\IndoController
{
    public function send(): ResponseInterface
    {
        try {
            $context = [];
            $payload = $this->getParsedBody();
            if (!isset($payload['subject'])
                || !isset($payload['message'])
                || (!isset($payload['to']) && !isset($payload['recipients']))
            ) {
                throw new \Exception('Invalid Request');
            }

            $mail = new IndoMail();
            $mail->setSubject($payload['subject']);
            $mail->setMessage($payload['message']);

            if (isset($payload['to'])) {
                $mail->targetTo($payload['to'], $payload['name'] ?? '');
            }
            if (isset($payload['recipients'])
                && \is_array($payload['recipients'])
            ) {
                foreach ($payload['recipients'] as $type => $recipients) {
                    if (\is_array($recipients)) {
                        foreach ($recipients as $recipient) {
                            try {
                                switch ($type) {
                                    case 'To': $mail->addRecipient($recipient['email'] ?? 'null', $recipient['name'] ?? '');
                                        break;
                                    case 'Cc': $mail->addCCRecipient($recipient['email'] ?? 'null', $recipient['name'] ?? '');
                                        break;
                                    case 'Bcc': $mail->addBCCRecipient($recipient['email'] ?? 'null', $recipient['name'] ?? '');
                                        break;
                                }
                            } catch (\Throwable $e) {
                                if ($this->isDevelopment()) {
                                    \error_log($e->getMessage());
                                }
                            }
                        }
                    }
                }
            }
            if (isset($payload['attachments'])
                && \is_array($payload['attachments'])
            ) {
                foreach ($payload['attachments'] as $attachment) {
                    $mail->addAttachment($attachment);
                }
            }
            
            // -> U r using PHP mail() function
            if (!$mail->send()) {
            //
            // -> OR u can use SMTP email send via PHPMailer
            //if (!$mail->sendSMTP()) {
            //
            // -> OR u can use Brevo's transactional email API
            //$context['brevo-result'] = $mail->sendBrevoTransactional();
            //if (empty($context['brevo-result'])) {
                throw new \RuntimeException('Email sending failed!');
            }

            $level = LogLevel::NOTICE;
            $context['status'] = 'success';
            $context['message'] = 'Email successfully sent to destination';
            return $this->respond($context);
        } catch (\Throwable $e) {
            if ($this->isDevelopment()) {
                \error_log($e->getMessage());
            }

            $level = LogLevel::ERROR;
            $context['status'] = 'error';
            $context['code'] = $e->getCode();
            $context['message'] = $e->getMessage();
            return $this->respond($context, $e->getCode());
        } finally {
            $logger = new LoggerModel($this->pdo);
            $logger->setTable('mailer', $_ENV['INDO_DB_COLLATION'] ?? 'utf8_unicode_ci');
            $to = $payload['to'] ?? '';
            $name = $payload['name'] ?? '';
            $subject = $payload['subject'] ?? 'Unknown message';
            $logger->log(
                $level,
                "$name - [$to] - $subject",
                $context + ['remote_addr' => $this->getRemoteAddr()]
            );
        }
    }
    
    private function isValidIP(string $ip): bool
    {
        $real = \ip2long($ip);
        if (empty($ip) || $real == -1 || $real === false) {
            return false;
        }

        $private_ips = [
            ['0.0.0.0', '2.255.255.255'],
            ['10.0.0.0', '10.255.255.255'],
            ['127.0.0.0', '127.255.255.255'],
            ['169.254.0.0', '169.254.255.255'],
            ['172.16.0.0', '172.31.255.255'],
            ['192.0.2.0', '192.0.2.255'],
            ['192.168.0.0', '192.168.255.255'],
            ['255.255.255.0', '255.255.255.255']
        ];
        foreach ($private_ips as $r) {
            $min = \ip2long($r[0]);
            $max = \ip2long($r[1]);
            if ($real >= $min && $real <= $max) {
                return false;
            }
        }

        return true;
    }

    private function getRemoteAddr(): string
    {
        $server = $this->getRequest()->getServerParams();
        if (!empty($server['HTTP_X_FORWARDED_FOR'])) {
            if (!empty($server['HTTP_CLIENT_IP'])
                && $this->isValidIP($server['HTTP_CLIENT_IP'])
            ) {
                return $server['HTTP_CLIENT_IP'];
            }
            foreach (\explode(',', $server['HTTP_X_FORWARDED_FOR']) as $ip) {
                if ($this->isValidIP(\trim($ip))) {
                    return $ip;
                }
            }
        }

        if (!empty($server['HTTP_X_FORWARDED'])
            && $this->isValidIP($server['HTTP_X_FORWARDED'])
        ) {
            return $server['HTTP_X_FORWARDED'];
        } elseif (!empty($server['HTTP_X_CLUSTER_CLIENT_IP'])
            && $this->isValidIP($server['HTTP_X_CLUSTER_CLIENT_IP'])
        ) {
            return $server['HTTP_X_CLUSTER_CLIENT_IP'];
        } elseif (!empty($server['HTTP_FORWARDED_FOR'])
            && $this->isValidIP($server['HTTP_FORWARDED_FOR'])
        ) {
            return $server['HTTP_FORWARDED_FOR'];
        } elseif (!empty($server['HTTP_FORWARDED'])
            && $this->isValidIP($server['HTTP_FORWARDED'])
        ) {
            return $server['HTTP_FORWARDED'];
        }

        return $server['REMOTE_ADDR'] ?? '';
    }
}
