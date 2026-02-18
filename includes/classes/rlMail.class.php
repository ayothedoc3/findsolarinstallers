<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: RLMAIL.CLASS.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2026 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

use Flynax\Utils\AsyncTasks;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class rlMail
{
    /**
     * @var PHPMailer class object
     */
    public $phpMailer;

    /**
     * @var smtpDebug
     */
    public $smtpDebug = '';

    /**
     * Replace account password to simple text or send non-secure password in email
     *
     * @since 4.7.2
     * @var   boolean
     */
    private $sendAccountPassword = false;

    /**
     * List of email templates where password must be send in original
     *
     * @since 4.7.2
     * @var   array
     */
    private $mailsTemplatesWithPassword = ['quick_account_created'];

    /**
     * Set timeout for sending email (in seconds)
     *
     * @since 4.10.0
     * @var int
     */
    public $timeout = 10;

    /**
     * Class constructor
     */
    public function __construct()
    {
        global $config;

        $this->phpMailer = new PHPMailer(true);
        $this->phpMailer->Timeout = $this->timeout;

        if ($config['mail_method'] == 'smtp') {
            $this->phpMailer->isSMTP();
            $this->phpMailer->getSMTPInstance()->Timeout   = $this->timeout;
            $this->phpMailer->getSMTPInstance()->Timelimit = $this->timeout;

            preg_match('#(https?://)?([^:]+):?(\d+)?#smi', $config['smtp_server'], $smtp_server);

            $this->phpMailer->Host = $smtp_server[2];
            $this->phpMailer->Port = $smtp_server[3];

            if (in_array($config['smtp_method'], ['tls', 'ssl'])) {
                $this->phpMailer->SMTPSecure = $config['smtp_method'];
            }

            $this->phpMailer->Username = $config['smtp_username'];
            $this->phpMailer->Password = $config['smtp_password'];

            if (empty($config['smtp_username']) && empty($config['smtp_password'])) {
                $this->phpMailer->SMTPAuth = false;
            } else {
                $this->phpMailer->SMTPAuth = true;
            }
        }
    }

    /**
     * Sending test email & checking SMTP configuration
     *
     * @return string - Status of sending email
     */
    public function testSMTP()
    {
        $this->smtpDebug = true;

        ob_start();

        $this->send(
            $this->getEmailTemplate('smtp_configuration_success'),
            $GLOBALS['config']['notifications_email']
        );

        return ob_get_clean();
    }

    /**
     * Send mail
     *
     * @since 4.10.1 - Added $async parameter
     * @since 4.9.2 - $to parameter type changed to string
     *
     * @param array  $mail_tpl    - Subject and body of message
     * @param string $to          - Recipient address
     * @param string $attach_file - Local filesystem path of attached file
     * @param string $from_mail   - From mail
     * @param string $from_name   - From address
     * @param bool   $async       - Send email in background
     *
     * @return bool
     */
    public function send($mail_tpl, $to, $attach_file = false, $from_mail = false, $from_name = false, ?bool $async = true)
    {
        global $config, $lang;

        $lang['pages+title+home'] = preg_replace('/\{if location\}(.*?)\{\/if\}/smi', '', $lang['pages+title+home']);

        if (!$mail_tpl['body']) {
            return false;
        }

        if ($GLOBALS['config']['mail_method'] == 'smtp' && $this->smtpDebug) {
            $this->phpMailer->SMTPDebug = 3;
        }

        $subject  = $mail_tpl['subject'];
        $body     = $mail_tpl['body'];
        $template = $mail_tpl;

        /**
         * @since 4.7.0 - Added $to, $template parameters
         * @since 4.4
         */
        $GLOBALS['rlHook']->load("phpMailSend", $subject, $body, $attach_file, $from_mail, $from_name, $to, $template);

        if (!$to) {
            return false;
        }

        if ($mail_tpl['Type'] == 'html' && $mail_tpl['html_source']) {
            $body = str_replace('{content}', $body, $mail_tpl['html_source']);
        }

        $this->phpMailer->From = $config['site_main_email'];
        $this->phpMailer->FromName = $from_name ?: $config['owner_name'];

        if ($from_mail) {
            $this->phpMailer->AddReplyTo($from_mail, $from_name);

            // add real sender of email
            $body = str_replace('{from_mail}', '<a href="mailto:' . $from_mail . '">' . $from_mail . '</a>', $body);
        }

        $this->phpMailer->CharSet = 'UTF-8';
        $this->phpMailer->Subject = $subject;
        $this->phpMailer->Body = $body;
        $this->phpMailer->AltBody = "To view the message, please use an HTML compatible email viewer!";
        $this->phpMailer->isHTML(true);
        $this->phpMailer->AddAddress($to);

        if ($attach_file && is_file($attach_file)) {
            // Make a copy of the attached file to send it in background
            if (!$this->smtpDebug && $config['async_mail'] && $async && !defined('CRON_FILE')) {
                $fileData = explode('.', $attach_file);

                if ($fileData[0] && $fileData[1]) {
                    $asyncAttachFile = $fileData[0] . rand(100, 999) . '.' . $fileData[1];
                    copy($attach_file, $asyncAttachFile);
                    $attach_file = $asyncAttachFile;
                }
            }

            $this->phpMailer->AddAttachment($attach_file);
        }

        $result = false;
        try {
            if (!$this->smtpDebug && $config['async_mail'] && $async && !defined('CRON_FILE')) {
                $phpMailerObject = clone $this->phpMailer;
                $phpMailerObject->Username = base64_encode($phpMailerObject->Username);
                $phpMailerObject->Password = base64_encode($phpMailerObject->Password);
                $phpMailerObject->Body     = base64_encode($phpMailerObject->Body);

                $result = AsyncTasks::create('send_email', [
                    'class'  => serialize($phpMailerObject),
                    'method' => 'send',
                ], true);
            } else {
                $result = $this->phpMailer->send();
            }
        } catch (Exception $e) {
            $GLOBALS['rlDebug']->logger($this->phpMailer->ErrorInfo);
        }

        $this->phpMailer->ClearAddresses();
        $this->phpMailer->ClearAttachments();

        return $result;
    }

    /**
     * Get email template by template key
     *
     * @param string $key  - Email template key
     * @param string $code - Language key
     *
     * @return array - Email template subject and body
     */
    public function getEmailTemplate($key, $code = false)
    {
        global $config, $rlDb;

        $lang_code = $code ?: RL_LANG_CODE;

        $mail_tpl = $rlDb->fetch(
            array('Key', 'Type'),
            array('Key' => $key, 'Status' => 'active'),
            null,
            null,
            'email_templates',
            'row'
        );

        if (!$mail_tpl) {
            return [];
        }

        $rlDb->outputRowsMap = ['Key', 'Value'];
        $phrases = $rlDb->fetch(
            ['Key', 'Value'],
            null,
            "WHERE `Key` LIKE 'email_templates+%+{$key}' AND `Module` = 'email_tpl' AND `Code` = '{$lang_code}'",
            null,
            'lang_keys'
        );

        $mail_tpl['subject'] = $phrases['email_templates+subject+' . $key];
        $mail_tpl['body']    = $phrases['email_templates+body+' . $key];

        if ($code && $code != RL_LANG_CODE) {
            $mail_tpl['Diff_preff_lang'] = $code;
        }

        if ($mail_tpl['Type'] == 'html') {
            $template = defined('TPL_CORE') && TPL_CORE === true ? 'template_core' : $config['template'];
            $tpl_base = RL_ROOT . 'templates' . RL_DS . $template . RL_DS;
            $mail_tpl['html_source'] = file_get_contents($tpl_base . 'tpl' . RL_DS . 'html_email_source.html', true);
        }

        $this->sendAccountPassword = in_array($key, $this->mailsTemplatesWithPassword)
        || (bool) $config['sending_account_password'];

        $mail_tpl = $this->replaceVariables($mail_tpl);

        return $mail_tpl;
    }

    /**
     * Send email using template
     *
     * @since 4.9.2
     *
     * @param  string      $recipientEmail   - Recipient email address
     * @param  string      $templateKey      - Email template key
     * @param  array       $findVariables    - Variable names to find in template like: ['{name}', '{link}']
     * @param  array       $replaceVariables - Values to set instead of variables like: ['Joe', 'https://www.com']
     * @param  string|null $senderEmail      - Sender email address
     * @param  string|null $senderName       - Sender name
     * @param  string|null $langCode         - Email template source language code
     * @param  string|null $attachment       - Attachment file path
     * @return bool                          - Sent email status
     */
    public function sendTemplate(
        string  $recipientEmail,
        string  $templateKey,
        array   $findVariables    = [],
        array   $replaceVariables = [],
        ?string $senderEmail      = null,
        ?string $senderName       = null,
        ?string $langCode         = null,
        ?string $attachment       = null
    ): bool
    {
        $template = $this->getEmailTemplate($templateKey, $langCode);

        if (!$template) {
            return false;
        }

        if ($findVariables && $replaceVariables) {
            $template['body'] = str_replace($findVariables, $replaceVariables, $template['body']);
            $template['subject'] = str_replace($findVariables, $replaceVariables, $template['subject']);
        }

        return $this->send($template, $recipientEmail, $attachment, $senderEmail, $senderName);
    }

    /**
     * Replace email template variables
     *
     * @param array $mail_tpl - Email template
     *
     * @return array - Replaces email template subject and body
     */
    public function replaceVariables($mail_tpl)
    {
        global $account_info, $lang, $config;

        if ($mail_tpl['Diff_preff_lang']) {
            $sql = "SELECT `Key`, `Value`  FROM `{db_prefix}lang_keys` ";
            $sql .= "WHERE (`Key` = 'email_footer' OR `Key` = 'email_site_name' OR `Key` = 'pages+title+home') ";
            $sql .= "AND `Code` = '{$mail_tpl['Diff_preff_lang']}'";
            $mail_tpl['Alt_lang'] = $GLOBALS['rlDb']->getAll($sql, array("Key", "Value"));

            $site_name = $mail_tpl['Alt_lang']['email_site_name'] ?: $mail_tpl['Alt_lang']['pages+title+home'];
        }

        if (!$site_name) {
            $site_name = $lang['email_site_name'] ?: $lang['pages+title+home'];
        }

        $tpl_vars = [
            '{site_name}'  => $site_name,
            '{site_url}'   => '<a href="' . RL_URL_HOME . '">' . RL_URL_HOME . '</a>',
            '{site_email}' => '<a href="mailto:' . $config['site_main_email'] . '">' . $config['site_main_email'] . '</a>',
        ];

        if (!$this->sendAccountPassword) {
            $tpl_vars['{password}'] = $lang['text_for_password'];
        }

        if (!empty($account_info['Full_name']) && !defined('REALM') && !defined('CRON_FILE')) {
            $tpl_vars['{name}'] = $account_info['Full_name'];
        }

        if ($account_info['Username'] && !defined('CRON_FILE')) {
            $tpl_vars['{username}'] = $account_info['Username'];
        }

        if ($mail_tpl['Type'] == 'html' && $mail_tpl['html_source']) {
            $find = array(
                '{tpl_base}',
                '{site_name}',
                '{footer}',
                '{site_url}',
                '{rtl}',
                '{logo_ext}'
            );

            $svg_logo_file = sprintf('%stemplates/%s/img/logo.svg', RL_ROOT, $config['template']);
            $replace = [
                RL_URL_HOME . 'templates/' . $config['template'] . '/',
                $site_name,
                $mail_tpl['Alt_lang']['email_footer'] ?: $lang['email_footer'],
                RL_URL_HOME,
                RL_LANG_DIR == 'rtl' ? 'text-align:right; direction:rtl;' : '',
                is_readable($svg_logo_file) ? 'svg' : 'png'
            ];

            $mail_tpl['html_source'] = str_replace($find, $replace, $mail_tpl['html_source']);
        }

        $mail_tpl['body'] = str_replace(PHP_EOL, '<br />', $mail_tpl['body']);
        foreach ($tpl_vars as $key => $value) {
            $mail_tpl['subject'] = str_replace($key, $value, $mail_tpl['subject']);
            $mail_tpl['body'] = str_replace($key, $value, $mail_tpl['body']);
            $mail_tpl['html_source'] = str_replace($key, $value, $mail_tpl['html_source']);
        }

        return $mail_tpl;
    }

    /**
     * Add custom email template to system list in which the password must be saved
     *
     * @since 4.7.2
     *
     * @param  string $emailTemplateKey
     *
     * @return bool
     */
    public function addMailTemplateForSendingPassword($emailTemplateKey)
    {
        if (!$emailTemplateKey = (string) $emailTemplateKey) {
            return false;
        }

        $this->mailsTemplatesWithPassword = array_merge($this->mailsTemplatesWithPassword, [$emailTemplateKey]);

        return true;
    }
}
