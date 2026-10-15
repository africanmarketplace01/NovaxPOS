<?php
namespace App\Traits;

trait MailInfo {

    /**
     * Apply SMTP settings from a MailSetting record to the runtime mail config.
     *
     * Laravel 9+ reads SMTP options from `mail.mailers.smtp.*` (the old top-level
     * `mail.host` / `mail.port` keys are ignored). The Symfony Mailer DSN also
     * requires the port to be an int (?int), so a raw string from the DB throws
     * "Argument #5 ($port) must be of type ?int, string given". Both are handled here.
     *
     * Two MailSetting models share this trait with different column names:
     *   App\Models\MailSetting          → username / from_address / from_name
     *   App\Models\landlord\MailSetting → user_name / email / name
     * so each value is resolved tolerantly across both schemas.
     */
    public function setMailInfo($mail_setting)
    {
        // Nothing usable configured — leave the existing (config/mail.php) settings in place.
        if (!$mail_setting || empty($mail_setting->host)) {
            return;
        }

        $encryption = $mail_setting->encryption;
        if ($encryption === 'none' || $encryption === '') {
            $encryption = null;
        }

        // Cast the port to int; fall back to null so Symfony can pick the scheme default.
        $port = is_numeric($mail_setting->port) ? (int) $mail_setting->port : null;

        // Resolve across both MailSetting schemas (missing attributes read as null).
        $username    = $mail_setting->username     ?? $mail_setting->user_name ?? null;
        $fromAddress = $mail_setting->from_address ?? $mail_setting->email      ?? config('mail.from.address');
        $fromName    = $mail_setting->from_name    ?? $mail_setting->name       ?? config('mail.from.name');

        config()->set([
            // Correct Laravel 9+ keys — these are what the SMTP transport reads.
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.transport'  => 'smtp',
            'mail.mailers.smtp.host'       => $mail_setting->host,
            'mail.mailers.smtp.port'       => $port,
            'mail.mailers.smtp.username'   => $username,
            'mail.mailers.smtp.password'   => $mail_setting->password,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.from.address'            => $fromAddress,
            'mail.from.name'               => $fromName,

            // Legacy keys kept in sync for any older code paths that still read them.
            'mail.driver'                  => $mail_setting->driver,
            'mail.host'                    => $mail_setting->host,
            'mail.port'                    => $port,
            'mail.username'                => $username,
            'mail.password'                => $mail_setting->password,
            'mail.encryption'              => $encryption,
        ]);

        // Flush the cached mailer instance so the new config is used this request.
        try {
            app('mail.manager')->forgetMailers();
        } catch (\Throwable $e) {
            // forgetMailers() was added in Laravel 9 — safe to ignore on older builds.
        }
    }

    /**
     * Configure the mailer from the landlord SaasSettings (smtp_host, smtp_port, …).
     *
     * This is the SAME source the "Settings → Test Email" uses, so any flow that
     * calls this sends through the SMTP the admin actually configured — unlike the
     * legacy `mail_settings` table, which is often empty (producing a blank host,
     * e.g. "Connection could not be established with host \":587\"").
     *
     * @return bool true if SaaS SMTP settings were applied, false if not configured.
     */
    public function setMailInfoFromSaasSettings(): bool
    {
        $settingsClass = \App\Models\landlord\SaasSettings::class;
        if (!class_exists($settingsClass)) {
            return false;
        }

        try {
            $s = $settingsClass::allCached();
        } catch (\Throwable $e) {
            return false;
        }

        $host = $s['smtp_host'] ?? null;
        if (empty($host)) {
            return false; // SaaS SMTP not configured — let the caller fall back.
        }

        $encryption = $s['smtp_encryption'] ?? 'tls';
        if ($encryption === 'none' || $encryption === '') {
            $encryption = null;
        }

        $port = is_numeric($s['smtp_port'] ?? null) ? (int) $s['smtp_port'] : 587;

        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.transport'  => 'smtp',
            'mail.mailers.smtp.host'       => $host,
            'mail.mailers.smtp.port'       => $port,
            'mail.mailers.smtp.username'   => $s['smtp_username'] ?? null,
            'mail.mailers.smtp.password'   => $s['smtp_password'] ?? null,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.from.address'            => $s['mail_from_address'] ?? config('mail.from.address'),
            'mail.from.name'               => $s['mail_from_name']    ?? config('mail.from.name'),
        ]);

        try {
            app('mail.manager')->forgetMailers();
        } catch (\Throwable $e) {
            // older Laravel — ignore
        }

        return true;
    }
}
