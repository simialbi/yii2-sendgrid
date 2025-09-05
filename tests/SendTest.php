<?php

namespace MarketforceInfo\SendGrid\Tests;

use MarketforceInfo\SendGrid\Mailer;
use Yii;

class SendTest extends TestCase
{
    private string $_apiKey;


    private string $_fromAddress;


    private string $_toAddress;

    public function testSendMessage()
    {
        $mailer = new Mailer([
            'apiKey' => $this->_apiKey,
        ]);
        $result = $mailer->compose('test')
            ->setFrom($this->_fromAddress)
            ->setTo($this->_toAddress)
            ->setSubject('test email')
            ->embed($this->getTestImagePath())
            ->attach($this->getTestPdfPath())
            ->send();

        $response = $mailer->getLastResponse();
        $this->assertInstanceOf('\SendGrid\Response', $response);
        $this->assertGreaterThanOrEqual(200, $response->statusCode());
        $this->assertLessThanOrEqual(202, $response->statusCode());
        $this->assertTrue($result);
    }

    /**
     * @depends testSendMessage
     */
    public function testSendAt()
    {
        $mailer = new Mailer([
            'apiKey' => $this->_apiKey,
        ]);
        $result = $mailer->compose('test')
            ->setFrom($this->_fromAddress)
            ->setTo($this->_toAddress)
            ->setSubject('test send at email')
            ->setSendAt(Yii::$app->formatter->asDate('+5min', 'yyyy-MM-dd HH:mm:ss'))
            ->send();

        $response = $mailer->getLastResponse();
        $this->assertInstanceOf('\SendGrid\Response', $response);
        $this->assertGreaterThanOrEqual(200, $response->statusCode());
        $this->assertLessThanOrEqual(202, $response->statusCode());
        $this->assertTrue($result);
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->_apiKey = getenv('SENDGRID_API_KEY');
        $this->_fromAddress = getenv('SENDGRID_FROM_ADDRESS');
        $this->_toAddress = getenv('SENDGRID_TO_ADDRESS');

        if (!$this->_apiKey || !$this->_fromAddress || !$this->_toAddress) {
            $this->markTestSkipped('One of "API key", "from address" or "to address" not set in secrets. Test skipped.');
        }
    }


    private function getTestImagePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'test.png';
    }


    private function getTestPdfPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'test.pdf';
    }
}
