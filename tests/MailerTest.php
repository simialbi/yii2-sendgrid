<?php

namespace MarketforceInfo\SendGrid\Tests;

use MarketforceInfo\SendGrid\Mailer;

class MailerTest extends TestCase
{
    public function testApiKeyIsRequired()
    {
        $this->expectException('\yii\base\InvalidConfigException');
        $this->expectExceptionMessage('"MarketforceInfo\SendGrid\Mailer::apiKey" cannot be null.');
        new Mailer();
    }

    public function testApiKeyMustBeString()
    {
        $this->expectException('TypeError');
        new Mailer(['apiKey' => []]);
    }

    public function testApiKeyLengthGreaterThanZero()
    {
        $this->expectException('\yii\base\InvalidConfigException');
        $this->expectExceptionMessage('"MarketforceInfo\SendGrid\Mailer::apiKey" length should be greater than 0');

        new Mailer(['apiKey' => '']);
    }

    public function testOptions()
    {
        $mailer = new Mailer([
            'apiKey' => 'testing',
            'options' => [
                'host' => 'https://api.eu.sendgrid.com',
                'version' => 'v2',
                'impersonateSubuser' => 'hans_muster',
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false
                ]
            ]
        ]);
        $sendGrid = $mailer->getSendGrid();
        $this->assertEquals('https://api.eu.sendgrid.com', $sendGrid->client->getHost());
        $this->assertEquals('v2', $sendGrid->client->getVersion());
        $this->assertEquals('On-Behalf-Of: hans_muster', $sendGrid->client->getHeaders()[3]);
        $this->assertArrayHasKey(CURLOPT_SSL_VERIFYPEER, $sendGrid->client->getCurlOptions());
        $this->assertFalse($sendGrid->client->getCurlOptions()[CURLOPT_SSL_VERIFYPEER]);
    }

    public function testGetSendGrid()
    {
        $mailer = new Mailer(['apiKey' => 'testing']);
        $this->assertInstanceOf('\SendGrid', $mailer->getSendGrid());
    }
}
