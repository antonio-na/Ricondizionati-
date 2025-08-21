<?php
/**
 * PHPMailer - A full-featured email creation and transfer class for PHP.
 *
 * @author    Marcus Bointon <phpmailer@synchromedia.co.uk>
 * @author    Jim Jagielski <jim@jagunet.com>
 * @author    Andy Prevost <codeworxtech@users.sourceforge.net>
 * @author    Brent R. Matzelle <bmatzelle@yahoo.com>
 * @copyright 2001-2020, Marcus Bointon
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @link      https://github.com/PHPMailer/PHPMailer
 *
 * This file is a simplified version of the original PHPMailer class for this environment.
 */

namespace PHPMailer\PHPMailer;

class PHPMailer
{
    public $Version = '6.5.3';
    public $CharSet = 'iso-8859-1';
    public $ContentType = 'text/plain';
    public $Encoding = '8bit';
    public $ErrorInfo = '';
    public $From = 'root@localhost';
    public $FromName = 'Root User';
    public $Sender = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $WordWrap = 0;
    public $Mailer = 'mail';
    public $Sendmail = '/usr/sbin/sendmail';
    public $UseSendmailOptions = true;
    public $PluginDir = '';
    public $ConfirmReadingTo = '';
    public $Hostname = '';
    public $MessageID = '';
    public $MessageDate = '';
    public $Host = 'localhost';
    public $Port = 25;
    public $Helo = '';
    public $SMTPSecure = '';
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $AuthType = '';
    public $Realm = '';
    public $Workstation = '';
    public $Timeout = 300;
    public $SMTPDebug = 0;
    public $Debugoutput = 'echo';
    public $SMTPKeepAlive = false;
    public $SingleTo = false;
    public $do_verp = false;
    public $AllowEmpty = false;
    public $LE = "\r\n";

    protected $smtp = null;
    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $ReplyTo = [];
    protected $all_recipients = [];
    protected $attachment = [];
    protected $CustomHeader = [];
    protected $MIMEBody = '';
    protected $MIMEHeader = '';
    protected $mailHeader = '';

    public function __construct($exceptions = null)
    {
        if ($exceptions !== null) {
            $this->exceptions = (bool)$exceptions;
        }
    }

    public function isSMTP()
    {
        $this->Mailer = 'smtp';
    }

    public function addAddress($address, $name = '')
    {
        return $this->addAnAddress('to', $address, $name);
    }

    public function addReplyTo($address, $name = '')
    {
        return $this->addAnAddress('Reply-To', $address, $name);
    }

    public function send()
    {
        try {
            if (!$this->preSend()) {
                return false;
            }
            return $this->postSend();
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
    }

    protected function preSend()
    {
        if (('mail' !== $this->Mailer) && !$this->smtpConnect()) {
            return false;
        }
        $this->mailHeader = $this->createHeader();
        $this->MIMEBody = $this->createBody();
        return true;
    }

    protected function postSend()
    {
        try {
            switch ($this->Mailer) {
                case 'smtp':
                    return $this->smtpSend(
                        $this->mailHeader,
                        $this->MIMEBody
                    );
                default:
                    // Fallback or other mailer implementations not included for simplicity
                    $this->setError('Mailer ' . $this->Mailer . ' is not supported');
                    return false;
            }
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    protected function smtpSend($header, $body)
    {
        $this->smtp->data($header . $body);
        if ($this->SMTPKeepAlive) {
            $this->smtp->reset();
        } else {
            $this->smtp->quit();
            $this->smtp->close();
        }
        return true;
    }

    public function smtpConnect($options = [])
    {
        if (null !== $this->smtp) {
            return true;
        }
        $this->smtp = new SMTP();
        if (!$this->smtp->connect($this->Host, $this->Port, $this->Timeout, $options)) {
            throw new Exception('SMTP Error: Could not connect to SMTP host.');
        }
        if ($this->SMTPSecure === 'tls') {
             if (!$this->smtp->startTLS()) {
                 throw new Exception('Could not start TLS: ' . $this->smtp->getError()['error']);
             }
        }
        if ($this->SMTPAuth) {
            if (!$this->smtp->authenticate($this->Username, $this->Password, $this->AuthType)) {
                 throw new Exception('SMTP Error: Could not authenticate.');
            }
        }
        return true;
    }

    public function smtpClose()
    {
        if (null !== $this->smtp && $this->smtp->connected()) {
            $this->smtp->quit();
            $this->smtp->close();
        }
        $this->smtp = null;
    }

    protected function addAnAddress($kind, $address, $name)
    {
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $this->setError('Invalid address: ' . htmlspecialchars($address));
            return false;
        }
        $this->{$kind}[] = [$address, $name];
        return true;
    }

    public function createHeader()
    {
        $header = [];
        $header[] = 'Date: ' . self::rfcDate();
        $header[] = 'To: ' . $this->addrFormat($this->to[0]);
        $header[] = 'From: ' . $this->addrFormat([$this->From, $this->FromName]);
        $header[] = 'Subject: ' . $this->encodeHeader($this->Subject);
        $header[] = 'Message-ID: <' . $this->generateMessageID() . '>';
        $header[] = 'X-Mailer: PHPMailer ' . $this->Version . ' (https://github.com/PHPMailer/PHPMailer)';
        $header[] = 'MIME-Version: 1.0';
        $header[] = 'Content-Type: ' . $this->ContentType . '; charset=' . $this->CharSet;
        $header[] = 'Content-Transfer-Encoding: ' . $this->Encoding;

        return implode($this->LE, $header) . $this->LE . $this->LE;
    }

    public function createBody()
    {
        return $this->Body;
    }

    protected function addrFormat($addr)
    {
        if (empty($addr[1])) {
            return $this->secureHeader($addr[0]);
        }
        return $this->encodeHeader($this->secureHeader($addr[1]), 'phrase') . ' <' . $this->secureHeader($addr[0]) . '>';
    }

    public function encodeHeader($str, $position = 'text')
    {
        // Simplified header encoding
        return '=?'. $this->CharSet . '?B?' . base64_encode($str) . '?=';
    }

    protected function setError($msg)
    {
        $this->ErrorInfo = $msg;
    }

    public static function rfcDate()
    {
        return date('D, j M Y H:i:s O');
    }

    public function generateMessageID()
    {
        return hash('sha256', uniqid(mt_rand(), true)) . '@' . $this->Hostname;
    }

    protected function secureHeader($str)
    {
        return trim(str_replace(["\r", "\n"], '', $str));
    }
}
