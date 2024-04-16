<?php

/**
 * PHPMailer.Lite.php
 *
 * @see        https://sourceforge.net/projects/phpmailer-lite/
 *
 * @category   Email Transport
 * @package    PHPMailer Lite
 * @author     Andy Prevost <andy@codeworxtech.com>
 * @copyright  2004-2023 (C) Andy Prevost - All Rights Reserved
 * @version    2024.1.3.0
 * @requires   PHP version 8.0.0 (and up)
 * @license    MIT - Distributed under the MIT License
 *             shown here:
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the 'Software'), to deal in the Software without
 * restriction, including without limitation the rights to use, copy,
 * modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 **/
/* Last updated: 21 Mar 2024 @ 16:34 (EST) */

namespace codeworxtech\PHPMailerLite;

if (version_compare(PHP_VERSION, "8.0.0", "<=")) {
    exit("Sorry, this version of PHPMailer Lite will only run on PHP version 8.0.0 or greater!\n");
}

class PHPMailerLite
{

    /* CONSTANTS */
    const VERSION = "2024.1.3.0";
    const CRLF = "\r\n";
    const MAILSEP = ", ";

    /* SMTP CONSTANTS */
    const TIMEVAL = 30; // seconds

    /* PROPERTIES, PRIVATE & PROTECTED */
    private array $attachments = [];
    private string $bcc = "";
    private array $boundary = [];
    private string $charSet = "utf-8";
    private string $cc = "";
    private string $confirm_read = "";
    private array $customHeader = [];
    private string $encode_hdr = "base64";
    private string $msgType = "";
    private string $recipients = "";
    private string $recipients_rt = "";
    private string $replyTo = "";
    private string $sender = "";
    private string $senderEmail = "root@localhost";
    private string $senderName = "No Reply";
    private string $sendmailPath = "";
    private string $transport = "";
    private array $transports = ["smtp", "sendmail", "imap"];
    private int $wordWrapLen = 70;
    private string $timeStart = "";

    /* PROPERTIES, PUBLIC */
    /**
     * debug has a cap
     */
    public string $debug = "";
    public string $hostname = "";
    public bool $imapAddToSent = false;
    public string $imapHost = "";
    public string $imapPort = "143/imap/notls";
    public string $imapUsername = "";
    public string $imapPassword = "";
    public string $messageICal = "";
    public string $messageHTML = "";
    public string $messageText = "";
    public string $mimeMail = "";
    public int $priority = 0;
    public string $returnPath = "";
    public string $subject = "";
    public bool $useIMAP = false;

    /* SMTP PROPERTIES, PUBLIC PRIVATE & PROTECTED */
    public array $smtpAccount = [];
    public bool $smtpDebug = false;
    public string $smtpDomain = "";
    private $smtperr = "";
    private $smtperrstr = "";
    public string $smtpFrom = "";
    public string $smtpHost = "";
    public bool $smtpKeepAlive = false;
    public array $smtpOptions = []; //["ssl"=>["verify_peer"=>false,"verify_peer_name"=>false,"allow_self_signed"=>true]];
    public string $smtpPassword = "";
    public string $smtpPort = "";
    private $smtpStream = 0;
    public string $smtpUsername = "";
    public bool $smtpUseVerp = false;

    /* METHODS ************/

    function __construct()
    {
        $this->timeStart = microtime(true);
        // Set boundaries
        $uniqId = md5(uniqid(time()));
        $this->boundary[0] = "P0_" . $uniqId;
        $this->boundary[1] = "P1_" . $uniqId;
        $this->boundary[2] = "P2_" . $uniqId;
    }

    function __destruct()
    {
        unset($this->cc);
        unset($this->bcc);
        unset($this->recipients);
        unset($this->attachments);
        unset($this->messageICal);
        unset($this->messageHTML);
        unset($this->messageText);
    }

    /**
     * Adds an attachment from a path on the filesystem.
     * Returns false if the file could not be found
     * or accessed.
     * @param string $path Path to the attachment.
     * @param string $name Overrides the attachment name.
     * @param string $encoding File encoding (see $Encoding).
     * @param string $type File extension (MIME) type.
     * @return bool
     */
    public function AddAttachment($path, $name = "", $encoding = "base64", $type = "")
    {
        self::IsExploitPath($path, true);
        if ($type == "") {
            $type = self::GetMimeType($path);
        }
        $filename = basename($path);
        if ($name == "") {
            $name = $filename;
        }
        $this->attachments[] = [0 => $path, 1 => $filename, 2 => $name, 3 => $encoding, 4 => $type, 5 => false, 6 => "attachment", 7 => 0];
        return true;
    }

    /**
     * Add a BCC
     * @param string $bcc
     */
    public function AddBCC($param)
    {
        $sep = (trim($this->bcc) != "") ? self::MAILSEP : "";
        $this->bcc .= $sep . self::EmailFormatRFC($param);
        $sep = (trim($this->recipients_rt) != "") ? self::MAILSEP : "";
        $this->recipients_rt .= $sep . self::EmailExtractEmail($param);
    }

    /**
     * Add a CC
     * @param string $cc
     */
    public function AddCC($param)
    {
        $sep = (trim($this->cc) != "") ? self::MAILSEP : "";
        $this->cc .= $sep . self::EmailFormatRFC($param);
        $sep = (trim($this->recipients_rt) != "") ? self::MAILSEP : "";
        $this->recipients_rt .= $sep . self::EmailExtractEmail($param);
    }

    /**
     * Adds a custom header
     */
    public function AddCustomHeader($custom_header)
    {
        $this->customHeader = explode(":", $custom_header, 2);
    }

    /**
     * Adds an embedded attachment. This can include images (backgrounds,etc).
     * @param string $path Path (location) of attachment.
     * @param string $cid Content ID of the attachment. Use to identify
     *                 the Id for accessing the image in an HTML doc.
     * @param string $name Overrides the attachment name.
     * @param string $encoding Mime encoding.
     * @param string $type File extension.
     * @return bool
     */
    public function AddEmbeddedImage($path, $cid, $name = "", $encoding = "", $type = "", $attach = "")
    {
        if ($encoding == "") {
            $encoding = "base64";
        }
        self::IsExploitPath($path, true);
        if (!@is_file($path)) {
            return false;
        }
        if ($type == "") {
            $type = mime_content_type($path);
        }
        $filename = basename($path);
        if ($name == "") {
            $name = $filename;
        }
        if (!self::IsImageDuplicate(0, $path)) {
            // Append to $attachments array
            $this->attachments[] = [0 => $path, 1 => $filename, 2 => $name, 3 => $encoding, 4 => $type, 5 => false, 6 => "inline", 7 => $cid];
            // Add second copy as downloadable attachment
            if ($attach != "") {
                $mimeType = mime_content_type($path);
                $this->attachments[] = [0 => $path, 1 => $attach, 2 => $attach, 3 => $encoding, 4 => $mimeType, 5 => false, 6 => "attachment", 7 => 0];
            }
            return true;
        }
        return false;
    }

    /**
     * Adds email message to IMAP INBOX.Sent
     * @param string $message (optional)
     * @param string $folder (optional)
     * @param string $options (optional)
     */
    public function AddMessageToSent($folder = "INBOX.Sent", $options = null)
    {
        if (!$this->imapAddToSent) {
            return;
        }
        $mailbox = "{" . $this->imapHost . ":" . $this->imapPort . "}" . $folder;
        if (!empty($this->imapHost) && !empty($this->imapUsername) && !empty($this->imapPassword)) {
            $cnx = @imap_open($mailbox, $this->imapUsername, $this->imapPassword);
            $res = imap_append($cnx, $mailbox, $this->mimeMail, null, date("r"));
            @imap_errors();
            @imap_alerts();
            @imap_close($cnx);
        }
    }

    /**
     * Add a recipient
     * @param string $email
     */
    public function AddRecipient($param)
    {
        $sep = (trim($this->recipients) != "") ? self::MAILSEP : "";
        $this->recipients .= $sep . self::EmailFormatRFC($param);
        $sep = (trim($this->recipients_rt) != "") ? self::MAILSEP : "";
        $this->recipients_rt .= $sep . self::EmailExtractEmail(self::EmailFormatRFC($param));
    }

    /**
     * Adds a string or binary attachment (non-filesystem) to the list.
     * This method can be used to attach ascii or binary data,
     * such as a BLOB record from a database.
     * @param string $string String attachment data.
     * @param string $filename Name of the attachment.
     * @param string $encoding File encoding (see $Encoding).
     * @param string $type File extension (MIME) type.
     * @return void
     */
    public function AddStringAttachment($string, $filename, $encoding = "base64", $type = "")
    {
        if ($type == "") {
            self::GetMimeType($string, "string");
        }
        $this->attachments[] = [0 => $string, 1 => $filename, 2 => basename($filename), 3 => $encoding, 4 => $type, 5 => true, 6 => "attachment", 7 => 0];
    }

    /**
     * Build attachment
     * @param string $attachment
     * @return string
     */
    private function BuildAttachment($attachment = "", $bkey = 0)
    {
        $mime = $cidUniq = $incl = [];
        // add parameter passed in function
        if (!empty($attachment) && is_string($attachment)) {
            if (self::IsPathSafe($attachment) !== true) {
                return false;
            }
            $mimeType = mime_content_type($attachment);
            $fileContent = file_get_contents($attachment);
            $fileContent = (!empty($this->encode_hdr)) ? chunk_split(base64_encode($fileContent)) : $fileContent;
            $data = "Content-Type: " . $mimeType . "; name=" . basename($attachment) . self::CRLF;
            $data .= (!empty($this->encode_hdr)) ? "Content-Transfer-Encoding: " . $this->encode_hdr . self::CRLF : "";
            $data .= "Content-ID: <" . basename($attachment) . ">" . self::CRLF;
            $data .= self::CRLF . $fileContent . self::CRLF;
            $data = self::GetBoundary($bkey) . $data;
            return $data;
        }
        // Add all other attachments and check for string attachment
        $bString = $attachment[5];
        if ($bString) {
            $string = $attachment[0];
        } else {
            $path = $attachment[0];
            if (self::IsPathSafe($path) !== true) {
                return false;
            }
        }
        if (in_array($attachment[0], $incl)) {
            return;
        }
        if (@in_array($path, $incl)) {
            return;
        }

        $filename = $attachment[1];
        $name = $attachment[2];
        $encoding = $attachment[3];
        $type = $attachment[4];
        $disposition = $attachment[6];
        $cid = $attachment[7];
        $incl[] = $attachment[0];

        if ($disposition == "inline" && isset($cidUniq[$cid])) {
            return;
        }
        $cidUniq[$cid] = true;

        $mime[] = "Content-Type: " . $type . "; name=\"" . $name . "\"" . self::CRLF;
        if (!empty($encoding)) {
            $mime[] = "Content-Transfer-Encoding: " . $encoding . self::CRLF;
        }

        if ($disposition == "inline") {
            $mime[] = "Content-ID: <" . $cid . ">" . self::CRLF;
        }
        $mime[] = "Content-Disposition: " . $disposition . "; filename=\"" . $name . "\"" . self::CRLF . self::CRLF;

        // Encode as string attachment
        if ($bString) {
            $str = (!empty($encoding)) ? chunk_split(base64_encode($string), $this->wordWrapLen, self::CRLF) : chunk_split($string, $this->wordWrapLen, self::CRLF);
            $mime[] = $str;
            $mime[] = self::CRLF . self::CRLF;
        } else {
            $str = (!empty($encoding)) ? chunk_split(base64_encode(file_get_contents($path)), $this->wordWrapLen, self::CRLF) : chunk_split(file_get_contents($path), $this->wordWrapLen, self::CRLF);
            $mime[] = chunk_split(base64_encode(file_get_contents($path)), $this->wordWrapLen, self::CRLF);
            $mime[] = self::CRLF . self::CRLF;
        }
        $data = implode("", $mime);
        $data = self::GetBoundary($bkey) . $data;
        return $data;
    }

    /**
     * Build message body
     * @return string
     */
    private function BuildBody()
    {
        self::GetMsgType();
        $body = self::GetMsgPart();
        //        $body .= self::CRLF;

        if ($this->msgType == "message") {
            return $body;
        }
        // attachment only
        elseif ($this->msgType == "attachment") {
            foreach ($this->attachments as $attachment) {
                if ($attachment[6] === "attachment") {
                    $body .= self::BuildAttachment($attachment, 0);
                }
            }
            return $body;
        }
        // message with attachment
        elseif ($this->msgType == "attachment|message") {
            $body .= self::CRLF;
            foreach ($this->attachments as $attachment) {
                if ($attachment[6] === "attachment") {
                    $body .= self::BuildAttachment($attachment, 0);
                }
            }
            $body .= self::GetBoundary(0, "--");
        }
        // message with attachment
        elseif ($this->msgType == "attachment|inline|message") {
            foreach ($this->attachments as $attachment) {
                if ($attachment[6] === "inline") {
                    $body .= self::BuildAttachment($attachment, 1);
                }
            }
            $body .= self::GetBoundary(1, "--");
            $body .= self::CRLF;
            foreach ($this->attachments as $attachment) {
                if ($attachment[6] === "attachment") {
                    $body .= self::BuildAttachment($attachment, 0);
                }
            }
            $body .= self::GetBoundary(0, "--");
            $body .= self::CRLF;
        }
        // message with inline (iCalendar option)
        elseif ($this->msgType == "inline|message") {
            $body .= self::CRLF;
            // inline
            foreach ($this->attachments as $attachment) {
                if ($attachment[6] === "inline") {
                    $body .= self::BuildAttachment($attachment, 0);
                }
            }
            $body .= self::GetBoundary(0, "--");
            $body .= self::CRLF;
        }
        $body = str_replace(self::CRLF . self::CRLF . self::CRLF, self::CRLF . self::CRLF, $body);
        return $body;
    }

    /**
     * Builds message header
     * @return string
     */
    public function BuildHeader($type = "smtp")
    {
        $mimeTxt = "This is a multipart message in MIME format." . self::CRLF;
        $messageID = md5((idate("U") - 1000000000) . uniqid()) . "@" . self::ServerHostname();
        if (empty($this->sender)) {
            if ($this->senderEmail == "root@localhost") {
                $this->senderEmail = "noreply@" . self::GetMailServer();
            }
            self::SetSender([$this->senderEmail => $this->senderName]);
        }
        $hdr = "Return-Path: " . ((!empty($this->returnPath)) ? $this->returnPath : $this->sender) . self::CRLF;
        $hdr .= "Date: " . date("r") . self::CRLF;
        $hdr .= "From: " . $this->sender . self::CRLF;
        $hdr .= "Reply-To: " . ((!empty($this->replyTo)) ? $this->replyTo : $this->sender) . self::CRLF;
        $hdr .= (!empty($this->cc)) ? "Cc: " . $this->cc . self::CRLF : "";
        $hdr .= (!empty($this->bcc)) ? "Bcc: " . $this->bcc . self::CRLF : "";
        $hdr .= "Message-Id: <" . $messageID . ">" . self::CRLF;
        $hdr .= "X-Originating-IP: " . $_SERVER['SERVER_ADDR'] . self::CRLF;
        $hdr .= "X-Mailer: PHPMailer Lite v" . PHPMailerLite::VERSION . " " . $this->transport . " (https://phpmailer.pro/)" . self::CRLF;
        $hdr .= ($this->priority !== 0) ? "X-Priority: " . $this->priority . self::CRLF : "";
        for ($index = 0; $index < count($this->customHeader); $index++) {
            $hdr .= trim($this->customHeader[$index][0]) . ": " . self::MbEncode(trim($this->customHeader[$index][1])) . self::CRLF;
        }
        if ($this->transport == "smtp" || $this->transport == "sendmail") {
            $hdr .= "To: " . $this->recipients . self::CRLF;
            $hdr .= "Subject: " . self::MbEncode($this->subject) . self::CRLF;
        }
        $hdr .= "MIME-Version: 1.0" . self::CRLF;
        if (stripos($this->msgType, "attachment") !== false) {
            $hdr .= self::GetContentTypeHdr("multipart/mixed", 0) . self::CRLF;
            $hdr .= $mimeTxt;
            if (stripos($this->msgType, "inline") !== false) {
                // sub header for inline
                $hdr .= self::GetBoundary(0);
                $hdr .= self::GetContentTypeHdr("multipart/related", 1) . self::CRLF;
                $hdr .= self::CRLF;
                $hdr .= self::GetBoundary(1);
                $hdr .= self::GetContentTypeHdr("multipart/alternative", 2);
                $hdr .= self::CRLF;
            } else {
                // sub header for message
                $hdr .= self::GetBoundary(0);
                $hdr .= self::GetContentTypeHdr("multipart/alternative", 1);
                $hdr .= self::CRLF;
            }
        } elseif (stripos($this->msgType, "inline") !== false) {
            $hdr .= self::GetContentTypeHdr("multipart/related", 0) . self::CRLF;
            $hdr .= $mimeTxt;
            // sub header for message
            $hdr .= self::GetBoundary(0);
            $hdr .= self::GetContentTypeHdr("multipart/alternative", 1) . self::CRLF;
            $hdr .= self::CRLF;
        } elseif ($this->msgType == "message") {
            $hdr .= self::GetContentTypeHdr("multipart/alternative", 0) . self::CRLF;
            $hdr .= $mimeTxt;
        }
        // message only
        return $hdr;
    }

    /**
     * Sets the HTML message and returns modifications for inline images and backgrounds
     * will also set text message if it does not exist (can over ride)
     * @param string $content content of the HTML message
     * @param string $basedir directory to the location of the images (relative to file)
     */
    private function DataToHTML($content, $basedir = "")
    {
        if (is_file($content)) {
            self::IsExploitPath($content, true);
            $thisdir = (dirname($content) != "") ? rtrim(dirname($content), '/') . '/' : "";
            $basedir = ($basedir == "") ? $thisdir : "";
            $content = file_get_contents($content);
        }
        preg_match_all("/(src|background)=\"(.*)\"/Ui", $content, $images);
        if (!empty($images[2])) {
            foreach ($images[2] as $i => $url) {
                if (!preg_match('#^[A-z]+://#', $url)) {
                    if ($basedir != "") {
                        $url = rtrim($basedir, '/') . '/' . $url;
                    }
                    $filename = basename($url);
                    $directory = dirname($url);
                    $cid = "cid:" . md5($filename);
                    if ($directory == ".") {
                        $directory = "";
                    }
                    $mimeType = mime_content_type($url);
                    if (strlen($directory) > 1 && substr($directory, -1) != '/') {
                        $directory .= '/';
                    }
                    self::IsExploitPath($directory . $filename, true);
                    if (self::AddEmbeddedImage($directory . $filename, md5($filename), $filename, "base64", $mimeType)) {
                        $content = preg_replace("/" . $images[1][$i] . "=\"" . preg_quote($images[2][$i], '/') . "\"/Ui", $images[1][$i] . "=\"" . $cid . "\"", $content);
                    }
                }
            }
        }
        $this->messageHTML = $content;
    }

    /**
     * input string or array containing email addresses (separated by comma)
     * in almost any format - can be single address or multiple
     * with or without correct spacing, quote marks
     * removes items without emails
     * returns RFC 5322 formatted string
     * @var string or array
     * @return string
     */
    private function EmailFormatRFC($data)
    {
        $rz = "";
        if (is_string($data)) {
            foreach ((explode(",", $data)) as $key => $val) {
                $val = trim($val);
                preg_match('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,9}/', $val, $bk);
                if (filter_var($bk[0], FILTER_VALIDATE_EMAIL)) {
                    $email = trim($bk[0]);
                    $name = $val;
                    if (!empty($email)) {
                        if (!empty($name)) {
                            $rz .= self::EmailExtractName($name) . " ";
                        }
                        $rz .= "<" . self::EmailExtractEmail($email) . ">, ";
                    }
                }
            }
            return rtrim($rz, ", ");
        } else {
            foreach ($data as $key => $val) {
                $name = $email = "";
                if (is_array($val)) {
                    $kkey = trim(str_replace(["<", ">"], "", key($val)));
                    $vval = trim(str_replace(["<", ">"], "", $val[$kkey]));
                    if (filter_var($kkey, FILTER_VALIDATE_EMAIL)) {
                        $email = "<" . $kkey . ">";
                        $name = ($vval != "") ? $vval : "";
                    } elseif (filter_var($vval, FILTER_VALIDATE_EMAIL)) {
                        $email = "<" . $vval . ">";
                        $name = ($kkey != "") ? $kkey : "";
                    }
                    if (!empty($email)) {
                        if (!empty($name)) {
                            $rz .= self::EmailExtractName($name) . " ";
                        }
                        $rz .= "<" . self::EmailExtractEmail($email) . ">, ";
                    }
                } else {
                    if (is_numeric($key) && str_contains($val, "<")) {
                        $t = explode("<", $val);
                        $key = trim($t[0]);
                        $val = trim(str_replace(["<", ">"], "", $t[1]));
                    }
                    $key = trim($key);
                    $val = trim($val);
                    if (filter_var($key, FILTER_VALIDATE_EMAIL)) {
                        $email = $key;
                        $name = ($val != "") ? $val : "";
                    } elseif (filter_var($val, FILTER_VALIDATE_EMAIL)) {
                        $email = $val;
                        $name = (!is_numeric($key) && $key != "") ? $key : "";
                    }
                    if (!empty($email)) {
                        if (!empty($name)) {
                            $rz .= self::EmailExtractName($name) . " ";
                        }
                        $rz .= "<" . self::EmailExtractEmail($email) . ">, ";
                    }
                }
            }
            return rtrim($rz, ", ");
        }
    }

    /**
     * extracts email address from a string
     * returns clean (shell safe) email address (WITHOUT TOKENS)
     * @var string
     * @return string
     */
    private function EmailExtractEmail($str)
    {
        if (!empty($str) && is_string($str)) {
            preg_match('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,9}/', $str, $bk);
            if (!empty($bk[0])) {
                $email = $bk[0];
            }
            $email = str_ireplace(["\r", "\n", "\t", '"', ",", "<", ">"], "", $email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL) && self::IsValidEmail($email)) {
                return $email;
            }
        }
        return false;
    }

    /**
     * extracts name portion from string email address
     * returns clean (shell safe) name
     * @var string
     * @return string
     */
    private function EmailExtractName($str)
    {
        if (trim($str) == "") {
            return;
        }
        $pattern = '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,9}/';
        preg_match($pattern, $str, $match);
        $addy = (!empty($match[0])) ? $match[0] : "";
        return trim(str_ireplace([$addy, "<", ">", "[", "]", "\r", "\n", "\t"], "", $str));
    }

    /**
     * Changes all CRLF or CR to LF
     * @return string
     */
    private function FixCRLF($str)
    {
        return str_ireplace(["\r\n", "\r", "\n"], self::CRLF, $str);
    }

    /**
     * Creates the boundary line / end boundary line
     * @param string $type = wrap, body, spec, none
     * @param string $end (optional, triggers adding two dashes at end)
     * @return string (boundary line)
     */
    private function GetBoundary($bkey, $end = "")
    {
        return "--" . $this->boundary[$bkey] . $end . self::CRLF;
    }

    /**
     * Creates the Content-Type directive for the body
     * @param string $type = multipart/mixed / multipart/related / multipart/alternative
     * @param string $charset
     * @param string $encoding
     * @param string $cid (optional)
     * @return string (content type line)
     */
    private function GetContentTypeBody($type, $charset, $encoding, $cid = "")
    {
        $data = "Content-Type: " . $type . ";" . self::CRLF;
        $data .= "\t" . $charset . self::CRLF;
        $data .= "Content-Transfer-Encoding: " . $encoding . self::CRLF;
        if ($cid != "") {
            $data .= "Content-ID: <" . $cid . ">" . self::CRLF;
        }
        return $data;
    }

    /**
     * Creates the Content-Type directive for the header
     * type = multipart/mixed / multipart/related / multipart/alternative
     * bkey = boundary (wrap / body / spec)
     * @return string (content type line)
     */
    private function GetContentTypeHdr($type, $bkey)
    {
        $data = "Content-Type: " . $type . ";" . self::CRLF;
        return $data . "\t" . "boundary=\"" . $this->boundary[$bkey] . "\"";
    }

    /**
     * dual use method
     * 1- with only $url passed, either a host or path (string) and returns the MX record domain name
     * 2- with a fully qualified mail server passed, returns true/false if an MX record matches
     * @param string $url
     * @param string $validate
     * @return string (mail server) (if $validate is 'no_test' and mail server found)
     * @return bool (if no mail server found)
     */
    private function GetMailServer($url = "", $validate = "no_test")
    {
        if (empty($url)) {
            $url = $_SERVER['SERVER_NAME'];
        }
        $tld = substr($url, strpos($url, ".") + 1);
        if ($validate === "is_valid") {
            $url = $tld;
        }
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $tld, $match)) {
            getmxrr($match['domain'], $mx_details);
            if (is_array($mx_details) && count($mx_details) > 0) {
                if ($validate === "is_valid") {
                    if ($url == reset($mx_details)) {
                        return true;
                    }
                } else {
                    return reset($mx_details);
                }
            }
        }
        return false;
    }

    /**
     * Gets MIME type of file or string
     * if file: USE ONLY AFTER VERIFYING FILE EXISTS
     * if string: designed for file data read in as string, will not
     *    properly detect html vs text
     * returns 'application/octet-stream' if not found (or file encoded)
     * @param string $resource (filename or string)
     * @param string $type     ('string' or 'file', defaults to 'file')
     * @return string
     */
    private function GetMimeType($resource, $type = "file")
    {
        if ($type == "string") {
            if (function_exists('finfo_buffer') && function_exists('finfo_open') && defined('FILEINFO_MIME_TYPE')) {
                return finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $resource);
            }
        } else {
            if (function_exists('finfo_file') && function_exists('finfo_open') && defined('FILEINFO_MIME_TYPE')) {
                return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $resource);
            }
            return mime_content_type($resource);
        }
        return "application/octet-stream";
    }

    /**
     * Builds plain text and HTML portion of message
     * @return string
     */
    protected function GetMsgPart($bkey = "")
    {
        $data = $html = "";
        $bkey = 0;
        if (!empty($this->messageICal)) {
            if (@is_file($this->messageICal)) {
                self::IsExploitPath($this->messageICal, true);
                $thisdir = (dirname($this->messageICal) != "") ? rtrim(dirname($this->messageICal), '/') . '/' : "";
                $string = file_get_contents($this->messageICal);
                $filename = basename($this->messageICal);
            } else {
                $string = $this->messageICal;
                $filename = basename("calendar.ics");
            }
            self::AddStringAttachment($string, $filename, "base64", "text/calendar");
        }
        if (trim($this->messageHTML) != "") {
            if (is_file($this->messageHTML)) {
                self::IsExploitPath($this->messageHTML, true);
                $thisdir = (dirname($this->messageHTML) != "") ? rtrim(dirname($this->messageHTML), '/') . '/' : "";
                self::DataToHTML(file_get_contents($this->messageHTML), $thisdir);
            }
            self::GetMsgType();
            if (
                stripos($this->msgType, "attachment") !== false &&
                stripos($this->msgType, "inline") !== false
            ) {
                $bkey = 2;
            } elseif (
                stripos($this->msgType, "attachment") !== false ||
                stripos($this->msgType, "inline") !== false
            ) {
                $bkey = 1;
            }
            $html .= self::GetBoundary($bkey);
            $html .= self::GetContentTypeBody("text/html", "charset=\"" . $this->charSet . "\"", "base64") . self::CRLF;
            $html .= base64_encode($this->messageHTML) . self::CRLF . self::CRLF;
        }
        $data .= self::CRLF;
        $data .= self::GetBoundary($bkey);
        $data .= self::GetContentTypeBody("text/plain", "charset=\"" . $this->charSet . "\"", "7bit");
        $data .= self::CRLF;
        $data .= ((trim($this->messageText) != "") ? self::WrapText($this->messageText, $this->wordWrapLen) : "") . self::CRLF;
        $data .= self::CRLF;
        $data .= self::CRLF;
        $data .= $html;
        return $data . self::GetBoundary($bkey, "--");
    }

    /**
     * Gets email message type
     * @return string
     */
    protected function GetMsgType($type = "")
    {
        if (is_string($type) && !empty($type)) {
            $type = explode("|", rtrim($type, "|") . "|");
        } else {
            $type = [];
        }
        if (!in_array("message", $type) && ($this->messageHTML != "" || $this->messageText != "")) {
            $type[] = "message";
        }
        foreach ($this->attachments as $attachment) {
            if ($attachment[6] === "inline" && !in_array("inline", $type)) {
                $type[] = "inline";
            } elseif ($attachment[6] === "attachment" && !in_array("attachment", $type)) {
                $type[] = "attachment";
            }
        }
        if (count($type) == 0) {
            $type[] = "inline";
        }
        sort($type);
        $this->msgType = implode("|", $type);
    }

    /**
     * Check the attachments array for a duplicate image
     * will not add if duplicate exists
     * @param string $id (attachments[0])
     * @param string $param value
     * @return bool
     */
    private function IsImageDuplicate($id, $param)
    {
        if (isset($this->attachments) && ($this->attachments) > 0) {
            foreach ($this->attachments as $key => $val) {
                if ($val[$id] === $param) {
                    return $key;
                }
            }
            return false;
        }
    }

    /**
     * Checks string for multibyte characters
     * @param $str string
     * @return bool (true if multibyte)
     */
    private function IsMultibyte($str)
    {
        return(mb_strlen($str) != strlen($str)) ? true : false;
    }

    /**
     * Check if file path is safe (real, accessible).
     * @param string $path Relative or absolute path to a file
     * @return bool
     */
    protected function IsPathSafe($path, $opt_exit = false)
    {
        // path decode (note %00 - null - removed in decode)
        for ($i = 0; $i <= substr_count($path, "%"); $i++) {
            $path = rawurldecode($path);
        }
        // convert all slashes to system default
        $path = str_replace(["\\", "/"], DIRECTORY_SEPARATOR, $path);
        if (!is_dir($path) && file_exists($path)) {
            return true;
        }
        // check for other exploits
        if (self::IsExploitPath($path)) {
            return false;
        }
        // check for path traversal
        if (strpos(realpath($path), $_SERVER['DOCUMENT_ROOT']) === false) {
            if ($opt_exit) {
                exit('Cannot execute<br>');
            }
            return false;
        }
        // check for valid path, path/file
        if (is_file($path)) {
            $path = str_replace(basename($path), "", $path);
        }
        $realPath = str_replace(rtrim($_SERVER['DOCUMENT_ROOT'] . dirname($_SERVER['PHP_SELF']), '/') . '/', "", realpath($path));
        if (strpos($path, "/")) {
            $realPath = rtrim($realPath, '/') . '/';
        }
        if (($path === false) || (strcmp(rtrim($path, '/'), rtrim($realPath, '/')) !== 0)) {
            return false;
        }
        return(file_exists($path) && is_readable($path) && is_dir($path)) ? true : false;
    }

    /**
     * Prevent attacks by disallowing unsafe shell characters.
     * Modified version (Thanks to Paul Buonopane <paul@namepros.com>)
     * Modification: CRITICAL STOP on NOT safe
     * @param  string  $email (the string to be tested for shell safety)
     * @return bool
     */
    protected function IsShellSafe($email)
    {
        $safe = true;
        if (empty(trim($email))) {
            $safe = false;
        }
        if ($safe && function_exists('ctype_alnum')) {
            for ($i = 0; $i < strlen($email); $i++) {
                $chr = $email[$i];
                if (!ctype_alnum($chr) && strpos('@_-.', $chr) === false) {
                    $safe = false;
                }
            }
        }
        if ($safe) {
            $safe = (bool) preg_match('/\A[\pL\pN._@-]*\z/ui', $email);
        }
        if ($safe === false) {
            exit("Cannot Process: Email Address failed shell safe validation<br>");
        }
        return true;
    }

    /**
     * Check if path (file) exists and is readable
     * @var string $path
     * @return bool
     */
    private function IsExploitPath($path, $optExit = false)
    {
        $isExploit = (is_file($path) && is_readable($path)) ? false : true;
        if ($isExploit && $optExit !== false) {
            echo 'path: ' . $path . '<br>';
            exit("Unable to send, access not allowed");
        }
        return $isExploit;
    }

    /**
     * Validate an email address, probably the most robust validator available
     * @param string $email The email address to check
     * @return boolean
     */
    private function IsValidEmail($email)
    {
        $temp = explode("@", $email);
        $domn = array_pop($temp);
        $rz = true;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $rz = false;
        }
        if ($rz) {
            $rz = false;
            $check = @fsockopen($domn, 80, $errno, $errstr, 1);
            if ($check) {
                $rz = true;
                @fclose($check);
            }
        }
        if ($rz) {
            $rz = (bool) preg_match('/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD', $email);
        }
        return $rz;
    }

    /**
     * Encodes and wraps long multibyte strings for mail headers
     * without breaking lines within a character
     * validates $str as multibyte
     * @param string $str multi-byte string to encode
     * @return string
     */
    private function MbEncode($str, $len = 76)
    {
        $str = self::SafeStr($str);
        if (mb_strlen($str) != strlen($str)) {
            return $str;
        }
        if (function_exists('mb_internal_encoding') && function_exists('mb_encode_mimeheader')) {
            mb_internal_encoding("UTF-8");
            return mb_encode_mimeheader($str, "UTF-8");
        } else {
            $prefs = ["scheme" => "Q", "input-charset" => "utf-8", "output-charset" => "utf-8", "line-length" => $len];
            return iconv_mime_encode($str, $prefs);
        }
    }

    /**
     * Filter data (ascii and url-encoded) to prevent header injection
     * @param string $str String
     * @return string (trimmed)
     */
    private function SafeStr($str)
    {
        return trim(str_ireplace(["\r", "\n", "%0d", "%0a", "Content-Type:", "bcc:", "to:", "cc:"], "", $str));
    }

    /**
     * Send the email
     * @return bool
     */
    public function Send($via = "smtp")
    {
        if ($via != "") {
            $via = strtolower($via);
        }
        if (!in_array($via, $this->transports)) {
            exit($via . " Transport Not Available");
        }
        $this->transport = $via;
        if ($this->recipients == "" && $this->bcc != "") {
            $this->recipients = "undisclosed-recipients:";
        }
        $ret = false;
        if (empty($this->sender)) {
            if ($this->senderEmail == "root@localhost") {
                $this->senderEmail = "noreply@" . self::GetMailServer();
            }
            self::SetSender([$this->senderEmail => $this->senderName]);
        }
        if (trim($this->returnPath) == "") {
            $this->returnPath = self::EmailExtractEmail($this->sender);
        }
        if (empty(trim($this->sender)) || empty(trim($this->recipients))) {
            exit("&#10007; Critical error: missing Sender and/or recipients.<br>" . self::CRLF);
        }
        $body = self::BuildBody();
        // order of Sending preference: SMTP, Sendmail, IMAP, PHP Mail()
        if ($this->transport == "smtp") {
            $hdr = self::BuildHeader("smtp");
            $ret = self::TransportSMTP($hdr, $body);
            $this->mimeMail = $hdr . self::CRLF . $body;
        }
        if (
            $this->transport == "sendmail" ||
            ($ret === false && $this->transport == "smtp")
        ) {
            $ret = self::TransportSendmail($body);
        }
        if (
            $this->transport == "imap" ||
            ($ret === false && ($this->transport == "sendmail" || $this->transport == "smtp"))
        ) {
            $hdr = self::BuildHeader("imap");
            $ret = self::TransportIMAP($hdr, $body);
            $this->mimeMail = $hdr . self::CRLF . $body;
        }
        if ($ret === true) {
            self::AddMessageToSent("INBOX.Sent");
        }
        return $ret;
    }

    /**
     * IMAP transport ONLY
     * Security to ALL the data and email addresses
     * must occur BEFORE calling this function
     * @return bool
     */
    protected function TransportIMAP($hdr, $body)
    {
        if (empty(trim($this->sender)) || empty(trim($this->recipients))) {
            return false;
        }
        self::GetMsgType($this->msgType);
        if ($this->mimeMail == "") {
            $this->mimeMail = $hdr . self::CRLF . $body;
        }
        $to = $this->recipients;
        $subject = self::MbEncode($this->subject);
        $cc = (!empty($this->cc)) ? $this->cc : null;
        $bcc = (!empty($this->bcc)) ? $this->bcc : null;
        $rpath = $this->sender;
        $ret = imap_mail($to, $subject, $body, $hdr, $cc, $bcc, $rpath);
        return $ret;
    }

    /* Sendmail transport ONLY
     * Security to ALL the data and email addresses
     * must occur BEFORE calling this function
     * @return bool
     */
    protected function TransportSendmail($body)
    {
        $ret = false;
        $this->sendmailPath = ini_get("sendmail_path");
        if (!empty($this->sendmailPath)) {
            $opt = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
            $this->sendmailPath = str_replace([" -o "," -i "]," -oi ", $this->sendmailPath);
            $command = $this->sendmailPath . " -f" . self::EmailExtractEmail($this->sender);
            if (function_exists('proc_open')) {
                $hdr = self::BuildHeader('proc');
                $hndl = proc_open($command, $opt, $bk);
                if (is_resource($hndl)) {
                    fwrite($bk[0], $hdr);
                    fwrite($bk[0], $body . self::CRLF);
                    fclose($bk[0]);
                    if (proc_close($hndl) != -1) {
                        $ret = true;
                    }
                }
            }
        }
        $this->mimeMail = $hdr . self::CRLF . $body;
        return $ret;
    }

    /* SMTP transport ONLY
     * Security to ALL the data and email addresses
     * must occur BEFORE calling this function
     * @return bool
     */
    protected function TransportSMTP($hdr, $body)
    {
        if (!is_null($this->debug)) {
            $this->smtpDebug = $this->debug;
        }
        if ($this->smtpDebug == 1) {
            $this->smtpDebug = true;
        }
        if (empty($this->smtpDomain)) {
            $this->smtpDomain = self::GetMailServer();
        }
        if (empty(trim($this->sender)) || empty(trim($this->recipients))) {
            return false;
        }
        if (self::SMTPconnect() === false) {
            return false;
        }
        if (self::SMTPenvelope() === false) {
            return false;
        }
        if (self::SMTPdata($hdr, $body) === false) {
            return false;
        }
        if (is_resource($this->smtpStream)) {
            if ($this->smtpKeepAlive == true) {
                self::SMTPreset();
            }
        } else {
            return false;
        }
        return true;
    }
    /*    */

    /**
     * Returns server hostname or 'localhost.localdomain' if unknown
     * @return string
     */
    private function ServerHostname()
    {
        if (!empty($this->hostname)) {
            return $this->hostname;
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        }
        return "localhost.localdomain";
    }

    /**
     * Set plain text
     * @param string $content
     */
    public function SetBodyText($content)
    {
        $this->messageText = $content;
    }

    /**
     * Set email address for confirm email is read
     * @param string $email The email address
     */
    public function SetConfirmRead($param)
    {
        $this->confirm_read = self::EmailFormatRFC($param);
    }

    /**
     * Set email address for confirm email is received
     * @param string $email The email address
     */
    public function SetConfirmReceipt($param)
    {
        $this->confirm_rcpt = self::EmailFormatRFC($param);
    }

    /*
     * Set priority
     * @param integer $param - from 1 (highest) to 5 (lowest)
     * @return bool
     */
    public function SetPriority($param)
    {
        return(!intval($param)) ? false : $this->priority = intval($param);
    }

    /**
     * Set replyTo
     * @param string $email
     * @return bool
     */
    public function AddReplyTo($param)
    {
        $this->replyTo = self::EmailFormatRFC($param);
        return true;
    }

    /**
     * Set sender
     * @param mixed (string or array) $email/$name
     */
    public function SetSender($param)
    {
        $tmpAddy = self::EmailFormatRFC($param);
        $senderName = self::EmailExtractName($tmpAddy);
        $senderEmail = self::IsShellSafe(self::EmailExtractEmail($tmpAddy));
        $tmpAddy = self::EmailFormatRFC([$senderName => $senderEmail]);
        $this->sender = $this->replyTo = $this->returnPath = self::EmailFormatRFC($param);
        unset($tmpAddy);



        $this->sender = self::EmailFormatRFC($param);
        $this->smtpFrom = self::EmailExtractEmail($this->sender);
    }

    /**
     * Set subject
     * @param string $param The subject of the email
     */
    public function SetSubject($param)
    {
        $this->subject = $param;
    }

    /**
     * Uses SMTP transport by default, set to false to use Sendmail as default
     * @param bool
     */
    public function useIMAP($param = true)
    {
        $this->useIMAP = ($param === true) ? true : false;
    }

    /**
     * Wraps message for use with mailers that do not automatically
     * perform wrapping
     * @param string $message The message to wrap
     * @param integer $length The line length to wrap to
     * @return string
     */
    public function WrapText($message, $length)
    {
        $message = self::FixCRLF($message);
        if (substr($message, -1) == self::CRLF) {
            $message = substr($message, 0, -1);
        }
        $line = explode(self::CRLF, $message);
        $message = "";
        for ($i = 0; $i < count($line); $i++) {
            $line_part = explode(" ", $line[$i]);
            $buf = "";
            for ($e = 0; $e < count($line_part); $e++) {
                $word = $line_part[$e];
                $buf_o = $buf;
                $buf .= ($e == 0) ? $word : (" " . $word);

                if (strlen($buf) > $length and $buf_o != "") {
                    $message .= $buf_o . "\n";
                    $buf = $word;
                }
            }
            $message .= $buf . self::CRLF;
        }
        return $message;
    }

    /* END - METHODS ************/

    /* SMTP METHODS ************/

    /**
     * Sets SMTP Account (Username and password)
     * @return mixed
     */
    public function SetSMTPaccount($array)
    {
        $pwd = trim(reset($array));
        $uname = (is_numeric(key($array))) ? $pwd : trim(key($array));
        $this->smtpUsername = $uname;
        $this->smtpPassword = $pwd;
    }

    /**
     * Set SMTP host
     * @param string $param
     */
    public function SetSMTPhost($param)
    {
        $this->smtpHost = self::SafeStr($param);
    }

    /**
     * Set SMTP port
     * @param integer $param
     */
    public function SetSMTPport($param)
    {
        $this->smtpPort = self::SafeStr($param);
    }

    /**
     * Set SMTP password
     * @param string $param
     */
    public function SetSMTPpass($param)
    {
        $this->smtpPassword = $param;
    }

    /**
     * Set SMTP username
     * @param string $param
     */
    public function SetSMTPuser($param)
    {
        $this->smtpUsername = self::SafeStr($param);
    }

    /**
     * Connect to the server
     * return code: 220 success
     * @return bool
     */
    private function SMTPconnect()
    {
        // check if already connected
        if ($this->smtpStream) {
            return false;
        }
        // check for host
        if (!empty($this->smtpHost)) {
            /*
            if (self::GetMailServer($this->smtpHost, "is_valid") === false) {
                exit(__LINE__ . " " . "&#10007; Critical error: invalid SMTP server.<br>" . self::CRLF);
            }
            */
            $host_name = $this->smtpHost;
            $server_arr = [$this->smtpHost];
        } else {
            $host_name = $this->smtpDomain;
            $server_arr = [$this->smtpDomain];
        }
        // check for port
        if (!empty($this->smtpPort)) {
            $srv_ports = [$this->smtpPort];
        } else {
            $srv_ports = [587, 2525, 25];
        }
        // connect to the smtp server
        $connect_options = $this->smtpOptions;
        $create_options = (!empty($connect_options)) ? stream_context_create($connect_options) : null;
        foreach ($server_arr as $host) {
            if (empty($code) || $code != "220") {
                foreach ($srv_ports as $port) {
                    if (function_exists('stream_socket_client')) {
                        $this->smtpStream = @stream_socket_client($host . ":" . $port, $errno, $errstr, self::TIMEVAL, STREAM_CLIENT_CONNECT, $create_options);
                    } else {
                        $this->smtpStream = @fsockopen($host, $port, $errno, $errstr, self::TIMEVAL);
                    }
                    if ($errno != "") {
                        $this->smtperr = $errno;
                    }
                    if ($errstr != "") {
                        $this->smtperrstr = $errstr;
                    }
                    if (!$this->smtpStream) {
                        return false;
                    }
                    $this->smtpHost = $host;
                    $this->smtpPort = $port;
                    $code = self::SMTPgetResponse(['220'], 'SMTP CONNECT');
                    if ($code == "220") {
                        break;
                    }
                }
            } else {
                break;
            }
        }
        if ($code == "220") {
            stream_set_timeout($this->smtpStream, self::TIMEVAL);
            return $this->smtpStream;
        }
        return false;
    }

    /**
     * Sends header and message to SMTP Server
     * return code: 250 success (possible 251, have to allow for this)
     * @return bool
     */
    private function SMTPdata($hdr, $body)
    {
        if (is_resource($this->smtpStream)) {
            self::SMTPisStreamConnected();
            // initiate DATA stream
            fwrite($this->smtpStream, "DATA" . self::CRLF);
            $code = self::SMTPgetResponse(['354'], 'DATA HEADER');
            // send the header
            $hdr_arr = self::StrSplitUnicode($hdr);
            foreach ($hdr_arr as $line) {
                fwrite($this->smtpStream, $line . self::CRLF);
            }
            // send the body
            $body_arr = self::StrSplitUnicode($body);
            foreach ($body_arr as $line) {
                fwrite($this->smtpStream, $line . self::CRLF);
            }
            // end DATA stream
            fwrite($this->smtpStream, "." . self::CRLF);
            $code = self::SMTPgetResponse(['250'], 'DATA');
            return true;
        }
        return false;
    }

    /**
     * Send envelope to SMTP Server
     * @return bool
     */
    private function SMTPenvelope()
    {
        if (is_resource($this->smtpStream)) {
            // send EHLO command
            fwrite($this->smtpStream, "EHLO " . $this->smtpHost . self::CRLF);
            $code = self::SMTPgetResponse(['250'], 'EHLO');
            self::SMTPisStreamConnected();
            // send STARTTLS command
            fwrite($this->smtpStream, "STARTTLS" . self::CRLF);
            $code = self::SMTPgetResponse(['220'], 'STARTTLS');
            // initiate secure tls encryption
            $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto_method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            } elseif (defined('STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT')) {
                $crypto_method = STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
            } elseif (defined('STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT')) {
                $crypto_method = STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT;
            }
            stream_socket_enable_crypto($this->smtpStream, true, $crypto_method);
            // resend EHLO after tls negotiation
            fwrite($this->smtpStream, "EHLO " . $this->smtpHost . self::CRLF);
            $code = self::SMTPgetResponse(['250'], 'EHLO');
            self::SMTPisStreamConnected();
            if (!empty($this->smtpUsername) && !empty($this->smtpUsername)) {
                // Authenticate
                fwrite($this->smtpStream, "AUTH LOGIN" . self::CRLF);
                $code = self::SMTPgetResponse(['334'], 'AUTH LOGIN');
                // Send encoded username
                fwrite($this->smtpStream, base64_encode($this->smtpUsername) . self::CRLF);
                $code = self::SMTPgetResponse(['334'], 'USER');
                // Send encoded password
                fputs($this->smtpStream, base64_encode($this->smtpPassword) . self::CRLF);
                $code = self::SMTPgetResponse(['235'], 'PASSWORD');
            }
            self::SMTPisStreamConnected();
            fwrite($this->smtpStream, "MAIL FROM: <" . $this->smtpFrom . ">" . (($this->smtpUseVerp) ? "XVERP" : "") . self::CRLF);
            $code = self::SMTPgetResponse(['250'], 'MAIL FROM');
            self::SMTPisStreamConnected();
            if (strpos($this->recipients_rt, ",") !== false) {
                $emails = explode(",", $this->recipients_rt);
                foreach ($emails as $email) {
                    fwrite($this->smtpStream, "RCPT TO: <" . trim($email) . '>' . self::CRLF);
                    $code = self::SMTPgetResponse(['250', '251'], 'RCPT TO');
                }
            } else {
                fwrite($this->smtpStream, "RCPT TO: <" . trim($this->recipients_rt) . '>' . self::CRLF);
                $code = self::SMTPgetResponse(['250', '251'], 'RCPT TO');
            }
            return true;
        }
        return false;
    }

    /**
     * Get response code returned by SMTP server
     * @return string $code
     */
    private function SMTPgetResponse($expected, $desc = "")
    {
        $total = "";
        if ($desc == "SMTP CONNECT" && @$errstr != "") {
            $data = $this->smtperrstr;
            $code = $this->smtperr;
        } elseif ($desc == "CLOSE CONNECTION") {
            $data = "Connection closed (true)";
            $code = $this->smtpStream;
        } else {
            $data = fread($this->smtpStream, 8192);
            $code = substr($data, 0, 3);
        }
        if ($this->smtpDebug === true) {
            $data = str_ireplace(["\r\n", "\r", "\n"], "\n", $data);
            $data = str_replace([$code . "-", $code . " "], "", $data);
            $data = trim(str_replace("  ", " ", $data));
            $data = str_ireplace("\n", "\n" . str_repeat("&nbsp;", 4), $data);
            $data = trim(str_ireplace("\n", "<br>", $data));
        }

        if ($desc == "SMTP CONNECT" && $code == "220") {
            if ($this->smtpDebug === true) {
                echo self::ThrowResponse("TRUE", "", "Server: " . $this->smtpHost . ", Port: " . $this->smtpPort . "<br>", true);
            }
        }

        if (in_array($code, $expected) && $this->smtpDebug === true) {
            echo self::ThrowResponse($code, $desc, $data, true);
        } elseif (!in_array($code, $expected)) {
            echo self::ThrowResponse($code, $desc, $data, false);
        }
        if ($this->smtpDebug === true) {
            if (trim($this->timeStart) != "") {
                if ($desc == "CLOSE CONNECTION") {
                    $total = "Total ";
                }
                echo "<br><i>" . str_repeat("&nbsp;", 4) . $total . "Elapsed time: " . number_format(microtime(true) - $this->timeStart, 3) . " sec.</i><br>";
            }
        }
        return $code;
    }

    /**
     * Returns true if connected to a server otherwise false
     * @access public
     * @return bool
     */
    private function SMTPisStreamConnected($error_msg = "Not connected to SMTP server, aborting.")
    {
        if (!empty($this->smtpStream)) {
            $status = socket_get_status($this->smtpStream);
            if ($status["eof"]) {
                fclose($this->smtpStream);
                $this->smtpStream = 0;
            } else {
                return true;
            }
        }
        self::ThrowResponse("0", "CRITICAL ERROR", $error_msg, false);
        return false;
    }

    /**
     * Sends QUIT to SMTP Server then closes the stream
     * return code: 221 success
     * @return bool
     */
    private function SMTPquit()
    {
        self::SMTPisStreamConnected();
        fwrite($this->smtpStream, "QUIT" . self::CRLF);
        $code = self::SMTPgetResponse(['221'], 'QUIT');
        // close the connection and reset the stream value
        if (!empty($this->smtpStream)) {
            fclose($this->smtpStream);
            $code = self::SMTPgetResponse(['1'], 'CLOSE CONNECTION');
            $this->smtpStream = 0;
        }
        return true;
    }

    /**
     * Sends smtp command RCPT TO
     * Returns true if recipient (email) accepted (false if not)
     * return code: 250 success (possible 251, have to allow for this)
     * @return bool
     */
    public function SMTPrecipient($param)
    {
        $addresses = [];
        if (is_string($param) && strpos($param, ',') !== false) {
            $addresses = explode(',', $param);
        } elseif (is_string($param)) {
            $addresses[] = $param;
        }
        $emails = [];
        foreach ($addresses as $key => $val) {
            $emails[] = self::EmailExtractEmail($val);
        }
        foreach ($emails as $email) {
            fwrite($this->smtpStream, "RCPT TO: <" . $email . ">" . self::CRLF);
            $code = self::SMTPgetResponse(['250', '251'], 'RCPT TO');
        }
    }

    /* Send RSET (aborts any transport in progress and keeps connection alive)
     * Implements RFC 821: RSET <CRLF>
     * return code 250 success
     * @return bool
     */
    private function SMTPreset()
    {
        self::SMTPisStreamConnected("Called SMTP_KeepAlive without connection.");
        fwrite($this->smtpStream, "RSET" . self::CRLF);
        $code = self::SMTPgetResponse(['250'], 'RSET');
        return true;
    }

    /**
     * splits a string into an array of max length $l
     * @return array
     */
    private function StrSplitUnicode($str, $length = 998)
    {
        if ($length > 0) {
            $ret = [];
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $length) {
                $ret[] = mb_substr($str, $i, $length, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    public function ThrowResponse($code, $desc, $data, $success = true)
    {
        $msg_lang = "<span style=\"%sfont-weight:700;\">%s&nbsp;%s (%s)</span><br>&nbsp;&nbsp;&nbsp;&nbsp;" . trim("%s");
        $icn = ($success === true) ? "&#10003;" : "&#10007;";
        $clr = ($success === true) ? "" : "color:red;";
        $typ = ($success === true) ? "Success" : "Error";
        if (trim($desc) != "") {
            $desc = " " . $desc;
        }
        $msg = sprintf($msg_lang, $clr, $icn, $typ, $code . $desc, $data);
        if ($success !== true && $this->smtpDebug === true) {
            throw new Exception($msg . $data . "<br></span>");
            return;
        }
        return $msg . self::CRLF;
    }
    /* END - SMTP METHODS ************/
}
/* PHPMailer Lite part of PHP Exception error handling
 * (note, namespace makes Exception unique)
 */
class Exception extends \Exception
{
    public function errorMessage()
    {
        $msg = str_ireplace(["<br>", "<br/>", "<br />"], "\n", $this->getMessage());
        $errorMsg = "<style>.bmh-alert {width:600px;max-width:600px;border-radius:5px;border-style:solid;border-width:1px;font-family:sans-serif;font-size:22px;font-weight:bold;margin:40px 20px;padding:12px 16px;width:80%;}.bmh-alert.bmh-danger {background-color:rgba(248, 215, 218, 1);border-color:rgba(220, 53, 69, 1);color:rgba(114, 28, 36,1);}.bmh-alert.bmh-info {background-color:rgba(217, 237, 247, 1);color:rgba(49, 112, 143, 1);border-color:rgba(126, 182, 193, 1);}</style>";
        $errorMsg .= "<div class=\"bmh-alert bmh-danger\" role=\"alert\">";
        $errorMsg .= htmlentities($msg);
        $errorMsg .= "</div>\n";
        return $errorMsg;
    }
    public function errorMessageRaw()
    {
        $msg = $this->getMessage();
        if ($this->getCode() == PHPMailerLite::ERR_CRITICAL) {
            return $msg;
            exit();
        }
        return $msg;
    }
}
