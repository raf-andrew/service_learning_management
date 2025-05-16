<?php

namespace Setup\Utils;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class MailManager {
    private Logger $logger;
    private array $config;
    private ?Swift_Mailer $mailer = null;

    public function __construct() {
        $this->logger = new Logger();
    }

    public function setConfig(array $config): void {
        $this->config = $config;
    }

    public function initialize(): void {
        if ($this->mailer !== null) {
            return;
        }

        try {
            $transport = new Swift_SmtpTransport(
                $this->config['smtp_host'],
                $this->config['smtp_port'],
                $this->config['encryption']
            );

            if (isset($this->config['username'])) {
                $transport->setUsername($this->config['username']);
            }

            if (isset($this->config['password'])) {
                $transport->setPassword($this->config['password']);
            }

            $this->mailer = new Swift_Mailer($transport);
            $this->logger->info('Mail system initialized');
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to initialize mail system: ' . $e->getMessage());
        }
    }

    public function send(
        string $to,
        string $subject,
        string $body,
        string $from = null,
        array $cc = [],
        array $bcc = [],
        array $attachments = []
    ): bool {
        if ($this->mailer === null) {
            $this->initialize();
        }

        try {
            $message = new Swift_Message($subject);
            $message->setFrom($from ?? $this->config['from_address'] ?? 'noreply@example.com');
            $message->setTo($to);
            $message->setBody($body, 'text/html');

            if (!empty($cc)) {
                $message->setCc($cc);
            }

            if (!empty($bcc)) {
                $message->setBcc($bcc);
            }

            foreach ($attachments as $attachment) {
                if (is_string($attachment)) {
                    $message->attach(\Swift_Attachment::fromPath($attachment));
                } elseif (is_array($attachment)) {
                    $message->attach(
                        \Swift_Attachment::fromPath($attachment['path'])
                            ->setFilename($attachment['name'] ?? basename($attachment['path']))
                    );
                }
            }

            $result = $this->mailer->send($message);
            $this->logger->info("Email sent to {$to}");
            return $result > 0;
        } catch (\Exception $e) {
            $this->logger->error("Failed to send email to {$to}: " . $e->getMessage());
            return false;
        }
    }

    public function sendTemplate(
        string $to,
        string $template,
        array $data = [],
        string $from = null,
        array $cc = [],
        array $bcc = [],
        array $attachments = []
    ): bool {
        if ($this->mailer === null) {
            $this->initialize();
        }

        try {
            $templateFile = dirname(__DIR__, 2) . "/templates/emails/{$template}.html";
            if (!file_exists($templateFile)) {
                throw new \RuntimeException("Email template not found: {$template}");
            }

            $content = file_get_contents($templateFile);
            if ($content === false) {
                throw new \RuntimeException("Failed to read email template: {$template}");
            }

            // Replace placeholders with data
            foreach ($data as $key => $value) {
                $content = str_replace("{{" . $key . "}}", $value, $content);
            }

            return $this->send($to, $data['subject'] ?? 'No Subject', $content, $from, $cc, $bcc, $attachments);
        } catch (\Exception $e) {
            $this->logger->error("Failed to send template email to {$to}: " . $e->getMessage());
            return false;
        }
    }

    public function sendBulk(
        array $recipients,
        string $subject,
        string $body,
        string $from = null,
        array $attachments = []
    ): array {
        if ($this->mailer === null) {
            $this->initialize();
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($recipients as $recipient) {
            $to = is_array($recipient) ? $recipient['email'] : $recipient;
            $cc = is_array($recipient) ? ($recipient['cc'] ?? []) : [];
            $bcc = is_array($recipient) ? ($recipient['bcc'] ?? []) : [];

            if ($this->send($to, $subject, $body, $from, $cc, $bcc, $attachments)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $to;
            }
        }

        $this->logger->info("Bulk email sent: {$results['success']} successful, {$results['failed']} failed");
        return $results;
    }

    public function sendBulkTemplate(
        array $recipients,
        string $template,
        array $data = [],
        string $from = null,
        array $attachments = []
    ): array {
        if ($this->mailer === null) {
            $this->initialize();
        }

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($recipients as $recipient) {
            $to = is_array($recipient) ? $recipient['email'] : $recipient;
            $cc = is_array($recipient) ? ($recipient['cc'] ?? []) : [];
            $bcc = is_array($recipient) ? ($recipient['bcc'] ?? []) : [];

            // Merge recipient-specific data
            $recipientData = array_merge($data, is_array($recipient) ? $recipient : []);

            if ($this->sendTemplate($to, $template, $recipientData, $from, $cc, $bcc, $attachments)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $to;
            }
        }

        $this->logger->info("Bulk template email sent: {$results['success']} successful, {$results['failed']} failed");
        return $results;
    }

    public function getMailer(): ?Swift_Mailer {
        return $this->mailer;
    }

    public function isInitialized(): bool {
        return $this->mailer !== null;
    }

    public function testConnection(): bool {
        if ($this->mailer === null) {
            $this->initialize();
        }

        try {
            return $this->mailer->getTransport()->start();
        } catch (\Exception $e) {
            $this->logger->error('Failed to test mail connection: ' . $e->getMessage());
            return false;
        }
    }

    public function getTransport(): ?Swift_SmtpTransport {
        if ($this->mailer === null) {
            return null;
        }
        return $this->mailer->getTransport();
    }

    public function setTransport(Swift_SmtpTransport $transport): void {
        $this->mailer = new Swift_Mailer($transport);
    }
} 