<?php
/**
 * PHPMailer RFC821 SMTP email transport class.
 *
 * @author    Chris Ryan <chris@greatbridge.com>
 * @author    Marcus Bointon <phpmailer@synchromedia.co.uk>
 * @author    Sascha Presnac <sascha@presnac.de>
 * @copyright 2001-2020, Chris Ryan
 * @license   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @link      https://github.com/PHPMailer/PHPMailer
 *
 * This file is a simplified version of the original SMTP class for this environment.
 * It contains the essential properties and methods.
 */

namespace PHPMailer\PHPMailer;

class SMTP
{
    const VERSION = '6.5.3';
    const LE = "\r\n";
    const DEFAULT_PORT = 25;
    const DEFAULT_SECURE_PORT = 465;
    const MAX_LINE_LENGTH = 998;
    const MAX_REPLY_LENGTH = 512;

    public $do_debug = 0;
    public $Debugoutput = 'echo';
    public $do_verp = false;
    public $Timeout = 300;
    public $Timelimit = 300;
    protected $smtp_conn;
    protected $error = ['error' => '', 'detail' => '', 'smtp_code' => '', 'smtp_code_ex' => ''];
    protected $helo_rply;
    protected $server_caps;
    protected $last_reply = '';

    public function connect($host, $port = null, $timeout = 30, $options = [])
    {
        $this->setError('');
        if ($this->connected()) {
            $this->setError('Already connected to a server');
            return false;
        }
        if (empty($port)) {
            $port = self::DEFAULT_PORT;
        }
        $this->smtp_conn = @stream_socket_client(
            $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            stream_context_create($options)
        );
        if (!is_resource($this->smtp_conn)) {
            $this->setError(
                'Failed to connect to server',
                '',
                (string)$errno,
                (string)$errstr
            );
            return false;
        }
        $this->debug('Connection: opening to ' . $host . ':' . $port, self::DEBUG_CONNECTION);
        $this->setTimelimit();
        if (substr(PHP_OS, 0, 3) !== 'WIN') {
            $max = (int)ini_get('max_execution_time');
            if (0 !== $max && $timeout > $max) {
                @set_time_limit($timeout);
            }
        }
        $this->last_reply = $this->get_lines();
        return true;
    }

    public function startTLS()
    {
        if (!$this->sendCommand('STARTTLS', 'STARTTLS', 220)) {
            return false;
        }
        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }
        $this->debug('Starting TLS', self::DEBUG_CONNECTION);
        set_error_handler([$this, 'errorHandler']);
        $crypto_ok = stream_socket_enable_crypto($this->smtp_conn, true, $crypto_method);
        restore_error_handler();
        return (bool)$crypto_ok;
    }

    public function authenticate($username, $password, $authtype = null, $realm = '', $workstation = '')
    {
        if (!$this->server_caps) {
            $this->setError('Authentication is not supported');
            return false;
        }
        if (null === $authtype) {
            $authtype = $this->getBestAuthType();
        }
        if (!array_key_exists($authtype, $this->server_caps['AUTH'])) {
            $this->setError('The selected authentication method is not supported');
            return false;
        }
        // Simplified auth logic
        if ($authtype == 'LOGIN') {
            if (!$this->sendCommand('AUTH LOGIN', 'AUTH LOGIN', 334)) {
                return false;
            }
            if (!$this->sendCommand('Username', base64_encode($username), 334)) {
                return false;
            }
            if (!$this->sendCommand('Password', base64_encode($password), 235)) {
                return false;
            }
        } else {
             $this->setError('Unsupported auth type');
             return false;
        }
        return true;
    }

    public function connected()
    {
        if (is_resource($this->smtp_conn)) {
            $sock_status = stream_get_meta_data($this->smtp_conn);
            if ($sock_status['eof']) {
                $this->debug('Connection: closed', self::DEBUG_CONNECTION);
                $this->close();
                return false;
            }
            return true;
        }
        return false;
    }

    public function close()
    {
        $this->setError('');
        if (is_resource($this->smtp_conn)) {
            $this->debug('Connection: closing', self::DEBUG_CONNECTION);
            fclose($this->smtp_conn);
            $this->smtp_conn = null;
        }
    }

    public function data($msg_data)
    {
        if (!$this->sendCommand('DATA', 'DATA', 354)) {
            return false;
        }
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $msg_data));
        $byte_count = 0;
        foreach ($lines as $line) {
            $out = $line;
            if (isset($out[0]) && $out[0] === '.') {
                $out = '.' . $out;
            }
            $this->client_send($out . self::LE);
        }

        if (!$this->sendCommand('DATA END', '.', 250)) {
            return false;
        }
        return true;
    }

    public function hello($host = '')
    {
        if ($this->sendCommand('EHLO', 'EHLO ' . $host, 250)) {
            $this->parseHelloFields('EHLO');
        } else {
             if ($this->sendCommand('HELO', 'HELO ' . $host, 250)) {
                $this->parseHelloFields('HELO');
            } else {
                 $this->setError('HELO not accepted from server');
                 return false;
            }
        }
        return true;
    }

    protected function parseHelloFields($type)
    {
        $this->server_caps = [];
        $lines = explode("\n", $this->helo_rply);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) > 1) {
                $this->server_caps[strtoupper($parts[0])] = array_slice($parts, 1);
            }
        }
    }

    public function mail($from)
    {
        $command = 'MAIL FROM:<' . $from . '>';
        if ($this->do_verp) {
            $command .= ' XVERP';
        }
        return $this->sendCommand('MAIL FROM', $command, 250);
    }

    public function quit($orig_handle = true)
    {
        if ($this->sendCommand('QUIT', 'QUIT', 221)) {
            $this->close();
            return true;
        }
        $this->close();
        return false;
    }

    public function rcpt($to, $dsn = [])
    {
        return $this->sendCommand('RCPT TO', 'RCPT TO:<' . $to . '>', [250, 251]);
    }

    public function rset()
    {
        return $this->sendCommand('RSET', 'RSET', 250);
    }

    protected function sendCommand($command_name, $command, $expect)
    {
        if (!$this->connected()) {
            $this->setError('Called ' . $command_name . ' without being connected');
            return false;
        }
        $this->client_send($command . self::LE);
        $this->last_reply = $this->get_lines();
        $matches = [];
        if (preg_match('/^([0-9]{3})(-| )?(.*)/', $this->last_reply, $matches)) {
            $code = (int)$matches[1];
            $code_ex = (count($matches) > 3 ? $matches[3] : '');
            $detail = (count($matches) > 3 ? $matches[3] : '');
            $this->setError('', $detail, (string)$code, $code_ex);
            if (!in_array($code, (array)$expect, true)) {
                $this->setError(
                    $command_name . ' command failed',
                    $detail,
                    (string)$code,
                    $code_ex
                );
                return false;
            }
        } else {
            $this->setError($command_name . ' command failed', 'Invalid server response');
        }
        return true;
    }

    public function getBestAuthType()
    {
        $available = array_keys($this->server_caps['AUTH']);
        foreach (['CRAM-MD5', 'LOGIN', 'PLAIN'] as $method) {
            if (in_array($method, $available, true)) {
                return $method;
            }
        }
        return '';
    }

    protected function get_lines()
    {
        if (!is_resource($this->smtp_conn)) {
            return '';
        }
        $data = '';
        $endtime = 0;
        stream_set_timeout($this->smtp_conn, $this->Timeout);
        if ($this->Timelimit > 0) {
            $endtime = time() + $this->Timelimit;
        }
        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            $str = @fgets($this->smtp_conn, self::MAX_REPLY_LENGTH);
            $data .= $str;
            if ((isset($str[3]) && $str[3] === ' ')) {
                break;
            }
            if ($endtime && time() > $endtime) {
                break;
            }
        }
        return $data;
    }

    protected function client_send($data)
    {
        $this->debug('Client -> Server: ' . rtrim($data, "\r\n"), self::DEBUG_CLIENT);
        return fwrite($this->smtp_conn, $data);
    }

    protected function setError($str, $detail = '', $smtp_code = '', $smtp_code_ex = '')
    {
        $this->error = [
            'error' => $str,
            'detail' => $detail,
            'smtp_code' => $smtp_code,
            'smtp_code_ex' => $smtp_code_ex,
        ];
    }

    public function getError()
    {
        return $this->error;
    }

    protected function debug($str, $level)
    {
        if ($this->do_debug >= $level) {
             call_user_func($this->Debugoutput, $str);
        }
    }

    protected function setTimelimit()
    {
        if ($this->Timelimit > 0) {
            if (function_exists('stream_set_timeout')) {
                stream_set_timeout($this->smtp_conn, $this->Timelimit);
            }
        }
    }

    protected function errorHandler($errno, $errmsg)
    {
        $this->setError('Stream ' . $errmsg, '', (string)$errno);
    }
}
