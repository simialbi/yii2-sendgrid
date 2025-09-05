<?php

namespace MarketforceInfo\SendGrid;

use SendGrid\Mail\Bcc;
use SendGrid\Mail\Cc;
use SendGrid\Mail\CustomArg;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Personalization;
use SendGrid\Mail\SendAt;
use SendGrid\Mail\Substitution;
use SendGrid\Mail\To;
use SendGrid\Mail\TypeException;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\HtmlPurifier;
use yii\helpers\Json;
use yii\helpers\StringHelper;
use yii\mail\BaseMessage;

/**
 * Message represents a single email message.
 *
 * @property string|array $from
 * @property string|array $to
 * @property string|array $replyTo
 * @property string|array $cc
 * @property string|array $bcc
 * @property string|null $subject
 * @property string|null $text
 * @property string|null $html
 * @property array $attachments
 * @property array $embeddedContent
 * @property string|null $charset
 * @property string|null $ipPool
 * @property int|null $sendAt
 * @property string|null $subaccount
 * @property boolean $important
 * @property boolean $trackOpens
 * @property boolean $trackClicks
 * @property array $googleAnalytics
 */
class Message extends BaseMessage
{
    public const LOG_CATEGORY = 'SendGrid Mailer';

    public $mailer;

    /**
     * @var ?string Contains the custom from address. If empty the adminEmail param of the
     * application will be used.
     */
    private ?string $_fromAddress = null;
    /**
     * @var ?string Contains the custom from name. If empty the app name will be used.
     */
    private ?string $_fromName = null;
    /**
     * @var array Contains the TO address list.
     */
    private array $_to = [];
    /**
     * @var array|string|null Contains the reply-to address list.
     */
    private array|string|null $_replyTo = null;
    /**
     * @var array Contains the CC address list.
     */
    private array $_cc = [];
    /**
     * @var array Contains the BCC address list.
     */
    private array $_bcc = [];
    /**
     * @var ?string Contains the html-encoded subject.
     */
    private ?string $_subject = null;
    /**
     * @var ?string Contains the email raw text.
     */
    private ?string $_text = null;
    /**
     * @var ?string Contains the email HTML test.
     */
    private ?string $_html = null;
    /**
     * @var array Contains the list of attachments already processed to be used by SendGrid.
     * Each entry within the array is an array with the following keys:
     *
     * ```php
     * [
     *     'name' => 'file.png', //the file name
     *     'type' => 'image/png', //the file mime type
     *     'content' => 'dGhpcyBpcyBzb21lIHRleHQ=' //the base64 encoded binary
     * ]
     * ```
     */
    private array $_attachments = [];
    /**
     * @var ?string The name of the dedicated ip pool that should be used to send the message. If you do not have any
     * dedicated IPs, this parameter has no effect. If you specify a pool that does not exist, your default pool will
     * be used instead.
     */
    private ?string $_ipPool = null;

    /**
     * @var int|null When this message should be sent as a UTC timestamp in YYYY-MM-DD HH:MM:SS format. If you specify
     *     a time in the past, the message will be sent immediately.
     */
    private ?int $_sendAt = null;

    /**
     * @var null|string Subaccount to use for SendGrid
     */
    private ?string $_subaccount = null;

    /**
     * @var array Contains the list of personalizations to be used by SendGrid.
     * Each entry within the array is an array with the following keys:
     */
    private array $_personalizations = [];

    /**
     * @var boolean Give this email more priority in queue
     */
    private bool $_important = false;

    /**
     * @var boolean Whether to enable open tracking for this email.
     */
    private bool $_trackOpens = true;

    /**
     * @var boolean Whether to enable click tracking for this email.
     */
    private bool $_trackClicks = true;

    /**
     * @var array The google analytics parameters to be used by SendGrid.
     */
    private array $_googleAnalytics = [];

    /**
     * SendGrid does not let users set a charset.
     *
     * @return null
     * @see self::setCharset() setter
     *
     */
    public function getCharset()
    {
        return null;
    }

    /**
     * SendGrid does not let users set a charset.
     *
     * @param string $charset character set name.
     *
     * @return static
     * @see self::getCharset() getter
     *
     */
    public function setCharset($charset): Message
    {
        return $this;
    }

    /**
     * The name of the dedicated ip pool that should is set to be used to send the message.
     *
     * @return string|null
     */
    public function getIpPool(): ?string
    {
        return $this->_ipPool;
    }

    /**
     * The name of the dedicated ip pool that should be used to send the message. If you do not have any dedicated IPs,
     * this parameter has no effect. If you specify a pool that does not exist, your default pool will be used instead.
     *
     * @param string $ipPool Ip pool name
     *
     * @return static
     */
    public function setIpPool(string $ipPool): Message
    {
        $this->_ipPool = $ipPool;

        return $this;
    }

    /**
     * When this message should be sent as a UTC timestamp.
     *
     * @return ?int
     */
    public function getSendAt(): ?int
    {
        return $this->_sendAt;
    }

    /**
     * When this message should be sent as a UTC timestamp in YYYY-MM-DD HH:MM:SS format. If you specify a time in the
     * past, the message will be sent immediately.
     *
     * @param string|int $sendAt The date in YYYY-MM-DD HH:MM:SS format
     *
     * @return static
     */
    public function setSendAt(string|int $sendAt): Message
    {
        if (!is_int($sendAt)) {
            $sendAt = Yii::$app->formatter->asTimestamp($sendAt);
        }
        $this->_sendAt = $sendAt;

        return $this;
    }

    /**
     * Returns the from email address in this format:
     *
     * ```
     * Sender Name <email@example.com>
     * ```
     *
     * The default value for the sender name is the application name
     * configuration parameter inside `config/web.php`.
     *
     * The default value for the sender address is the adminEmail parameter
     * inside `config/params.php`.
     *
     * @return string
     * @see self::setFrom() setter
     *
     */
    public function getFrom(): string
    {
        $from = '';

        if ($this->getFromName() !== null) {
            $from .= $this->getFromName();
        }

        $from .= empty($from) ? $this->getFromAddress() : ' <' . $this->getFromAddress() . '>';

        return $from;
    }

    /**
     * Sets the message sender.
     *
     * @param string|array $from sender email address.
     * You may specify sender name in addition to email address using format:
     * `[email => name]`.
     * If you don't set this parameter the application adminEmail parameter will
     * be used as the sender email address and the application name will be used
     * as the sender name.
     *
     * @return static
     * @see self::getFrom() getter
     *
     */
    public function setFrom($from): Message
    {
        if (is_string($from) && filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $this->_fromAddress = $from;
            $this->_fromName = null;
        }

        if (is_array($from)) {
            $address = key($from);
            $name = array_shift($from);
            if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
                return $this;
            }

            $this->_fromAddress = $address;
            if (is_string($name) && strlen(trim($name)) > 0) {
                $this->_fromName = trim($name);
            } else {
                $this->_fromName = null;
            }
        }

        return $this;
    }

    /**
     * Returns an array of email addresses in the following format:
     *
     * ```
     * [
     *  'email1@example.com', //in case no recipient name was submitted
     *  'email2@example.com' => 'John Doe', //in case a recipient name was submitted
     * ]
     * ```
     *
     * @return array|null
     * @see self::setTo() setter
     *
     */
    public function getTo(): ?array
    {
        return $this->_to;
    }

    /**
     * Sets the message recipient(s).
     *
     * @param string|array $to receiver email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     *
     * @return static
     * @see self::getTo() getter
     *
     */
    public function setTo($to): Message
    {
        $this->_to = (array)$to;

        return $this;
    }

    /**
     * Returns an array of email addresses in the following format:
     *
     * ```
     * [
     *  'email1@example.com', //in case no recipient name was submitted
     *  'email2@example.com' => 'John Doe', //in case a recipient name was submitted
     * ]
     * ```
     *
     * @return array|null
     * @see self::setReplyTo() setter
     *
     */
    public function getReplyTo(): ?array
    {
        return $this->_replyTo;
    }

    /**
     * Sets the message recipient(s).
     *
     * @param string|array $replyTo Reply-To email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     *
     * @return static
     * @see self::getReplyTo() getter
     *
     */
    public function setReplyTo($replyTo): Message
    {
        $this->_replyTo = $replyTo;

        return $this;
    }

    /**
     * Returns an array of email addresses in the following format:
     *
     * ```
     * [
     *  'email1@example.com', //in case no recipient name was submitted
     *  'email2@example.com' => 'John Doe', //in case a recipient name was submitted
     * ]
     * ```
     *
     * @return array|null
     * @see self::setCc() setter
     *
     */
    public function getCc(): ?array
    {
        return $this->_cc;
    }

    /**
     * Sets the message recipient(s).
     *
     * @param string|array $cc cc email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     *
     * @return static
     * @see self::getCc() getter
     *
     */
    public function setCc($cc): Message
    {
        $this->_cc = (array)$cc;

        return $this;
    }

    /**
     * Returns an array of email addresses in the following format:
     *
     * ```
     * [
     *  'email1@example.com', //in case no recipient name was submitted
     *  'email2@example.com' => 'John Doe', //in case a recipient name was submitted
     * ]
     * ```
     *
     * @return array
     * @see self::setBcc() setter
     *
     */
    public function getBcc(): array
    {
        return $this->_bcc;
    }

    /**
     * Sets the message recipient(s).
     *
     * @param string|array $bcc bcc email address.
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     *
     * @return static
     * @see self::getBcc() getter
     *
     */
    public function setBcc($bcc): Message
    {
        $this->_bcc = (array)$bcc;

        return $this;
    }

    /**
     * Returns the html-encoded subject.
     *
     * @return string|null
     * @see self::setSubject() setter
     *
     */
    public function getSubject(): ?string
    {
        return $this->_subject;
    }

    /**
     * Sets the message subject.
     *
     * @param string $subject
     * The subject will be trimmed.
     *
     * @return static
     * @see self::getSubject() getter
     *
     */
    public function setSubject($subject): Message
    {
        if (is_string($subject)) {
            $this->_subject = trim($subject);
        }

        return $this;
    }

    /**
     * Returns the html-purified version of the raw text body.
     *
     * @return string|null
     * @see self::setTextBody() setter
     *
     */
    public function getTextBody(): ?string
    {
        return $this->_text;
    }

    /**
     * Sets the raw text body.
     *
     * @param string $text
     * The text will be purified.
     *
     * @return static
     * @see self::getTextBody() getter
     *
     */
    public function setTextBody($text): Message
    {
        if (is_string($text)) {
            $this->_text = HtmlPurifier::process($text);
        }

        return $this;
    }

    /**
     * Returns the html purified version of the html body.
     *
     * @return string|null
     * @see self::setHtmlBody() setter
     *
     */
    public function getHtmlBody(): ?string
    {
        return $this->_html;
    }

    /**
     * Sets the html body.
     *
     * @param string $html
     *
     * @return static
     * @see self::getHtmlBody() getter
     *
     */
    public function setHtmlBody($html): Message
    {
        if (is_string($html)) {
            $this->_html = $html;
        }

        return $this;
    }

    /**
     * Returns the attachments array.
     *
     * @return array
     * @see self::attachContent() setter for binary
     *
     * @see self::attach() setter for file name
     */
    public function getAttachments(): array
    {
        return $this->_attachments;
    }

    /**
     * Attaches existing file to the email message.
     *
     * @param string $fileName full file name
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return static
     * @throws \yii\base\InvalidConfigException|\Exception
     * @see self::getAttachments() getter
     *
     */
    public function attach($fileName, array $options = []): Message
    {
        if (file_exists($fileName) && !is_dir($fileName)) {
            $purifiedOptions = [
                'fileName' => ArrayHelper::getValue($options, 'fileName', basename($fileName)),
                'contentType' => ArrayHelper::getValue($options, 'contentType', FileHelper::getMimeType($fileName)),
            ];
            $this->attachContent(file_get_contents($fileName), $purifiedOptions);
        }

        return $this;
    }

    /**
     * Attach specified content as file for the email message.
     *
     * @param string $content attachment file content.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return static
     * @throws \Exception
     * @see self::getAttachments() getter
     *
     */
    public function attachContent($content, array $options = []): Message
    {
        $purifiedOptions = is_array($options) ? $options : [];

        if (is_string($content) && strlen($content) !== 0) {
            $this->_attachments[] = [
                base64_encode($content),
                ArrayHelper::getValue($purifiedOptions, 'contentType', $this->getMimeTypeFromBinary($content)),
                ArrayHelper::getValue($purifiedOptions, 'fileName', ('file_' . count($this->_attachments))),
                'attachment'
            ];
        }

        return $this;
    }

    /**
     * Returns the images array.
     *
     * @return array list of embedded content
     * @see self::embedContent() setter for binary
     *
     * @see self::embed() setter for file name
     */
    public function getEmbeddedContent(): array
    {
        $embedded = [];
        foreach ($this->_attachments as $attachment) {
            if ($attachment[3] === 'inline') {
                $embedded[] = $attachment;
            }
        }

        return $embedded;
    }

    /**
     * Embeds an image in the email message.
     *
     * @param string $fileName file name.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return static
     * @throws \yii\base\InvalidConfigException|\Exception
     * @see self::getEmbeddedContent() getter
     *
     */
    public function embed($fileName, array $options = []): Message
    {
        if (file_exists($fileName) && !is_dir($fileName) && StringHelper::startsWith(FileHelper::getMimeType($fileName), 'image')) {
            $purifiedOptions = [
                'fileName' => ArrayHelper::getValue($options, 'fileName', basename($fileName)),
                'contentType' => ArrayHelper::getValue($options, 'contentType', FileHelper::getMimeType($fileName)),
            ];
            $this->embedContent(file_get_contents($fileName), $purifiedOptions);
        }

        return $this;
    }

    /**
     * Embed a binary as an image in the message.
     *
     * @param string $content attachment file content.
     * @param array $options options for embed file. Valid options are:
     *
     * - fileName: name, which should be used to attach file.
     * - contentType: attached file MIME type.
     *
     * @return static
     * @throws \Exception
     * @see self::getEmbeddedContent() getter
     */
    public function embedContent($content, array $options = []): Message
    {
        $purifiedOptions = is_array($options) ? $options : [];
        $mimeType = $this->getMimeTypeFromBinary($content);

        if (is_string($content) && strlen($content) !== 0 && StringHelper::startsWith($mimeType, 'image')) {
            $this->_attachments[] = [
                base64_encode($content),
                ArrayHelper::getValue($purifiedOptions, 'contentType', $mimeType),
                ArrayHelper::getValue($purifiedOptions, 'fileName', ('file_' . count($this->_attachments))),
                'inline'
            ];
        }

        return $this;
    }

    /**
     * Returns the subaccount to use for this message.
     *
     * @return string|null
     */
    public function getSubaccount(): ?string
    {
        return $this->_subaccount;
    }

    /**
     * Sets the subaccount for the message.
     *
     * @param string $subaccount The subaccount to be assigned.
     *
     * @return Message Returns the current message instance.
     */
    public function setSubaccount(string $subaccount): Message
    {
        $this->_subaccount = $subaccount;

        return $this;
    }

    /**
     * Returns the personalizations for the message.
     *
     * @return array
     */
    public function getPersonalizations(): array
    {
        return $this->_personalizations;
    }

    /**
     * Sets the personalizations for the message.
     *
     * @param array $personalizations An array of personalizations to set.
     *
     * @return Message
     */
    public function setPersonalizations(array $personalizations): Message
    {
        foreach ($personalizations as $personalization) {
            $this->addPersonalization($personalization);
        }

        return $this;
    }

    /**
     * Adds a personalization object or array to the message.
     *
     * @param Personalization|array $personalization The personalization object or an associative array
     * containing the personalization details.
     *
     * @return Message Returns the current message object with the added personalization.
     * @throws TypeException
     */
    public function addPersonalization(Personalization|array $personalization): Message
    {
        if ($personalization instanceof Personalization && !empty($personalization->getTos())) {
            $this->_personalizations[] = $personalization;
        } elseif (is_array($personalization) && ArrayHelper::isAssociative($personalization) && isset($personalization['to'])) {
            $p = new Personalization();
            $pTo = ArrayHelper::remove($personalization, 'to');
            if (filter_var($pTo, FILTER_VALIDATE_EMAIL)) {
                $p->addTo(new To($pTo));
            } elseif (is_array($pTo)) {
                foreach ($pTo as $to => $name) {
                    if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                        $p->addTo(new To($to, $name));;
                    } elseif (filter_var($name, FILTER_VALIDATE_EMAIL)) {
                        $p->addTo(new To($name));;
                    } elseif (is_array($name) && ArrayHelper::isIndexed($name, true) && filter_var($name[0], FILTER_VALIDATE_EMAIL)) {
                        foreach ($name as $email) {
                            $p->addTo(new To($email));
                        }
                    }
                }
            }
            if (isset($personalization['cc'])) {
                if (filter_var($personalization['cc'], FILTER_VALIDATE_EMAIL)) {
                    $p->addCc(new Cc($personalization['cc']));
                } elseif (is_array($personalization['cc'])) {
                    foreach ($personalization['cc'] as $to => $name) {
                        if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                            $p->addCc(new Cc($to, $name));;
                        } elseif (filter_var($name, FILTER_VALIDATE_EMAIL)) {
                            $p->addCc(new Cc($name));;
                        } elseif (is_array($name) && ArrayHelper::isIndexed($name, true) && filter_var($name[0], FILTER_VALIDATE_EMAIL)) {
                            foreach ($name as $email) {
                                $p->addCc(new Cc($email));
                            }
                        }
                    }
                }
            }
            if (isset($personalization['bcc'])) {
                if (filter_var($personalization['bcc'], FILTER_VALIDATE_EMAIL)) {
                    $p->addBcc(new Bcc($personalization['bcc']));
                } elseif (is_array($personalization['bcc'])) {
                    foreach ($personalization['bcc'] as $to => $name) {
                        if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                            $p->addBcc(new Bcc($to, $name));;
                        } elseif (filter_var($name, FILTER_VALIDATE_EMAIL)) {
                            $p->addBcc(new Bcc($name));;
                        } elseif (is_array($name) && ArrayHelper::isIndexed($name, true) && filter_var($name[0], FILTER_VALIDATE_EMAIL)) {
                            foreach ($name as $email) {
                                $p->addBcc(new Bcc($email));
                            }
                        }
                    }
                }
            }
            if (isset($personalization['subject'])) {
                $p->setSubject($personalization['subject']);
            }
            if (isset($personalization['send_at'])) {
                $sendAt = is_numeric($personalization['send_at']) ? (int)$personalization['send_at'] : Yii::$app->formatter->asTimestamp($personalization['send_at']);
                $p->setSendAt(new SendAt($sendAt));
            }
            if (isset($personalization['custom_args'])) {
                foreach ($personalization['custom_args'] as $key => $value) {
                    $p->addCustomArg(new CustomArg($key, $value));
                }
            }
            if (isset($personalization['substitutions'])) {
                foreach ($personalization['substitutions'] as $key => $value) {
                    $p->addSubstitution(new Substitution($key, $value));
                }
            }
            $this->_personalizations[] = $p;
        }

        return $this;
    }

    /**
     * Make the message important.
     *
     * @return static
     */
    public function setAsImportant(): Message
    {
        $this->_important = true;

        return $this;
    }

    /**
     * Make the message not important.
     * The message is not important by default.
     *
     * @return static
     */
    public function setAsNotImportant(): Message
    {
        $this->_important = false;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isImportant(): bool
    {
        return $this->_important;
    }

    /**
     * Enable tracking of when the message is opened.
     * Tracking is enabled by default.
     *
     * @return static
     */
    public function enableOpensTracking(): Message
    {
        $this->_trackOpens = true;

        return $this;
    }

    /**
     * Disable tracking of when the message is opened.
     *
     * @return static
     */
    public function disableOpensTracking(): Message
    {
        $this->_trackOpens = false;

        return $this;
    }

    /**
     * Returns the status of tracking for when the message is opened.
     *
     * @return boolean
     */
    public function areOpensTracked(): bool
    {
        return $this->_trackOpens;
    }

    /**
     * Enable tracking of when links in the message are being clicked.
     * Tracking is enabled by default.
     *
     * @return static
     */
    public function enableClicksTracking(): Message
    {
        $this->_trackClicks = true;

        return $this;
    }

    /**
     * Disable tracking of when links in the message are being clicked.
     *
     * @return static
     */
    public function disableClicksTracking(): Message
    {
        $this->_trackClicks = false;

        return $this;
    }

    /**
     * Returns the status of tracking for when the links in the message are clicked.
     *
     * @return boolean
     */
    public function areClicksTracked(): bool
    {
        return $this->_trackClicks;
    }

    /**
     * Returns the Google Analytics data.
     *
     * Returns an array with the following structure:
     * [
     *  'utm_source',
     *  'utm_medium',
     *  'utm_term',
     *  'utm_content',
     *  'utm_campaign'
     * ]
     *
     * @return array
     */
    public function getGoogleAnalytics(): array
    {
        return $this->_googleAnalytics;
    }

    /**
     * Collect campaign data with custom URLs
     *
     * @param string $utm_campaign The individual campaign name, slogan, promo code, etc. for a product.
     * @param string $utm_source Identify the advertiser, site, publication, etc. that is sending traffic to your
     * property, for example: google, newsletter4, billboard. Default is email_campaign.
     * @param string $utm_medium The advertising or marketing medium, for example: cpc, banner, email newsletter.
     * Default is email.
     * @param string|null $utm_term Identify paid search keywords. If you're manually tagging paid keyword campaigns,
     *     you should also use utm_term to specify the keyword.
     * @param string|null $utm_content Used to differentiate similar content, or links within the same ad. For example,
     * if you have two call-to-action links within the same email message, you can use utm_content and set different
     * values for each so you can tell which version is more effective.
     *
     * @return static
     */
    public function setGoogleAnalytics(string $utm_campaign, string $utm_source = 'email_campaign', string $utm_medium = 'email', ?string $utm_term = null, ?string $utm_content = null): Message
    {
        $this->_googleAnalytics = [
            $utm_source,
            $utm_medium,
            $utm_term,
            $utm_content,
            $utm_campaign
        ];

        return $this;
    }

    /**
     * Returns the string representation of this message.
     *
     * @return string
     * @throws TypeException
     */
    public function toString(): string
    {
        return Json::encode($this->getSendGridMessage()->jsonSerialize());
    }

    /**
     * @return Mail
     * @throws \SendGrid\Mail\TypeException
     */
    public function getSendGridMessage(): Mail
    {
        $mail = new Mail();

        $personalizations = $this->getPersonalizations();
        foreach ($personalizations as $personalization) {
            $mail->addPersonalization($personalization);
        }

        $mail->setFrom($this->getFromAddress(), $this->getFromName());
        $mail->setSubject($this->_subject);
        foreach ($this->_to as $to => $name) {
            if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $mail->addTo($to, $name);
            } elseif (filter_var($name, FILTER_VALIDATE_EMAIL)) {
                $mail->addTo($name);
            } elseif (is_array($name) && ArrayHelper::isIndexed($name, true) && filter_var($name[0], FILTER_VALIDATE_EMAIL)) {
                call_user_func_array([$mail, 'addTo'], $name);
            }
        }
        foreach ($this->_cc as $to => $name) {
            if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $mail->addCc($to, $name);
            } elseif (filter_var($name, FILTER_VALIDATE_EMAIL)) {
                $mail->addCc($name);
            } elseif (is_array($name) && ArrayHelper::isIndexed($name, true) && filter_var($name[0], FILTER_VALIDATE_EMAIL)) {
                call_user_func_array([$mail, 'addCc'], $name);
            }
        }
        foreach ($this->_bcc as $to => $name) {
            if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $mail->addBcc($to, $name);
            } elseif (filter_var($name, FILTER_VALIDATE_EMAIL)) {
                $mail->addBcc($name);
            } elseif (is_array($name) && ArrayHelper::isIndexed($name, true) && filter_var($name[0], FILTER_VALIDATE_EMAIL)) {
                call_user_func_array([$mail, 'addBCc'], $name);
            }
        }
        if (is_array($this->_replyTo)) {
            $mail->setReplyTo($this->_replyTo[0], $this->_replyTo[1]);
        } elseif (is_string($this->_replyTo)) {
            $mail->setReplyTo($this->_replyTo);
        }
        if ($this->_sendAt !== null) {
            $mail->setSendAt($this->_sendAt);
        }
        if ($this->_text !== null) {
            $mail->addContent('text/plain', $this->_text);
        }
        if ($this->_html !== null) {
            $mail->addContent('text/html', $this->_html);
        }
        $mail->addAttachments($this->_attachments);
        if (!is_null($this->_ipPool)) {
            $mail->setIpPoolName($this->_ipPool);
        }
        $mail->setClickTracking($this->_trackClicks);
        $mail->setOpenTracking($this->_trackOpens);
        if (!empty($this->_googleAnalytics) && count($this->_googleAnalytics) >= 3) {
            $googleAnalytics = $this->_googleAnalytics;
            array_unshift($googleAnalytics, true);
            call_user_func_array([$mail, 'setGanalytics'], $googleAnalytics);
        }

        return $mail;
    }

    /**
     * Returns the Mime Type from the file binary.
     *
     * @param string $binary
     *
     * @return string
     */
    private function getMimeTypeFromBinary(string $binary): string
    {
        if (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            return $finfo->buffer($binary);
        }

        return 'application/octet-stream';
    }

    /**
     * Returns the from name default value if no one was set by the user.
     *
     * @return string|null
     */
    private function getFromName(): ?string
    {
        return $this->_fromName ?: Yii::$app->name;
    }

    /**
     * Returns the from address default value if no one was set by the user.
     *
     * @return string|null
     */
    private function getFromAddress(): ?string
    {
        return $this->_fromAddress ?: Yii::$app->params['adminEmail'];
    }
}
