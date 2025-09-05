<?php

namespace MarketforceInfo\SendGrid;

use SendGrid;
use SendGrid\Mail\TypeException;
use SendGrid\Response;
use Yii;
use yii\base\InvalidConfigException;
use yii\mail\BaseMailer;

/**
 * Mailer is the SendGrid mailer implementation.
 *
 * @property SendGrid $sendGrid SendGrid instance
 * @property-read Response $lastResponse The last response received from the SendGrid API
 * @property-write string $apiKey SendGrid API Key
 */
class Mailer extends BaseMailer
{
    public const LOG_CATEGORY = 'SendGrid Mailer';

    /**
     * @var string the default class name of the new message instances created by [[createMessage()]]
     */
    public $messageClass = Message::class;

    /**
     * @var string the directory where the email messages are saved when [[useFileTransport]] is true.
     */
    public $fileTransportPath = '@runtime/mail';

    /**
     * @var array a list of options for the sendgrid api
     *  - host: the host of the sendgrid api (default: https://api.sendgrid.com). Use https://api.eu.sendgrid.com for EU region
     *  - curl: the curl options (default: [])
     *  - version: the version of the sendgrid api (default: v3)
     *  - verify_ssl: whether to verify ssl (default: true)
     *  - impersonateSubuser: the subuser to impersonate
     */
    public array $options = [];

    /**
     * @var string the api key for the sendgrid api
     */
    private string $_apiKey;

    /**
     * @var SendGrid SendGrid instance
     */
    private SendGrid $_sendGrid;

    /**
     * @var Response the last response from the SendGrid API
     */
    private Response $_lastResponse;

    /**
     * Initializes the mailer.
     *
     * {@inheritDoc}
     *
     * @throws InvalidConfigException|\Exception
     */
    public function init(): void
    {
        if (empty($this->_apiKey)) {
            throw new InvalidConfigException('"' . get_class($this) . '::apiKey" cannot be null.');
        }

        try {
            $this->_sendGrid = new SendGrid($this->_apiKey, $this->options);
        } catch (\Exception $exc) {
            Yii::error($exc->getMessage());
            throw new \Exception('An error occurred with your mailer. Please check the application logs.', 500);
        }
    }

    /**
     * Sets the SendGrid API Key
     *
     * @param string $apiKey The SendGrid API Key
     *
     * @return void
     * @throws InvalidConfigException
     */
    public function setApiKey(string $apiKey): void
    {
        $apiKey = trim($apiKey);
        if (!(strlen($apiKey) > 0)) {
            throw new InvalidConfigException('"' . get_class($this) . '::apiKey" length should be greater than 0.');
        }
        $this->_apiKey = $apiKey;
    }

    /**
     * Retrieves the last response received from the SendGrid API.
     *
     * @return Response
     */
    public function getLastResponse(): Response
    {
        return $this->_lastResponse;
    }

    /**
     * Get SendGrid instance
     *
     * @return SendGrid instance
     */
    public function getSendGrid(): SendGrid
    {
        return $this->_sendGrid;
    }

    /**
     * @param Message $message
     *
     * @return bool
     * @throws TypeException
     */
    public function sendMessage($message): bool
    {
        Yii::info('Sending email "' . $message->getSubject() . '"', self::LOG_CATEGORY);

        $this->_lastResponse = $this->_sendGrid->send($message->getSendGridMessage());
        return $this->_lastResponse->statusCode() === 202 || $this->_lastResponse->statusCode() === 200;
    }
}
