<?php

namespace MarketforceInfo\SendGrid\Tests;

use MarketforceInfo\SendGrid\Mailer;
use MarketforceInfo\SendGrid\Message;
use Yii;

class MessageTest extends TestCase
{
    private Message $_message;

    private string $_testImageBinary;

    private string $_testPdfBinary;

    public function testMessageInstance()
    {
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message);
    }

    public function testMessageSetCharset()
    {
        $this->assertNull($this->_message->getCharset());
    }

    public function testSendAt()
    {
        $date = Yii::$app->formatter->asDate('+5min', 'yyyy-MM-dd HH:mm:ss');
        $this->assertNull($this->_message->getSendAt());
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setSendAt($date));
        $this->assertEquals(strtotime($date), $this->_message->getSendAt());
    }

    public function testMessageSetRecipient()
    {
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setTo('email@email.it'));
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setTo([
            'email2@email.it' => 'fakeuser',
            'email@email.it',
            'email3@email.it',
        ]));
        $contactList = $this->_message->getTo();
        $this->assertCount(3, $contactList);
        $this->assertContains('email@email.it', $contactList);
        $this->assertArrayHasKey('email2@email.it', $contactList);
        $this->assertContains('email3@email.it', $contactList);
        $this->assertContains('fakeuser', $contactList);
    }

    public function testMessageSetSender()
    {
        $this->assertEquals('My Application <admin@example.com>', $this->_message->getFrom());

        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setFrom('email@email.it'));
        $this->assertEquals('My Application <email@email.it>', $this->_message->getFrom());

        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setFrom(['email2@email.it']));
        $this->assertEquals('My Application <email@email.it>', $this->_message->getFrom());

        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setFrom(['asdf']));
        $this->assertEquals('My Application <email@email.it>', $this->_message->getFrom());

        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setFrom('asdf'));
        $this->assertEquals('My Application <email@email.it>', $this->_message->getFrom());

        $this->assertInstanceOf(
            '\MarketforceInfo\SendGrid\Message',
            $this->_message->setFrom([
                'fakeuser' => 'email4@email.it',
            ])
        );
        $this->assertEquals('My Application <email@email.it>', $this->_message->getFrom());

        $this->assertInstanceOf(
            '\MarketforceInfo\SendGrid\Message',
            $this->_message->setFrom([
                'email2@email.it' => 'fakeuser',
            ])
        );
        $this->assertEquals('fakeuser <email2@email.it>', $this->_message->getFrom());

        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setFrom([
            'email3@email.it' => '',
        ]));
        $this->assertEquals('My Application <email3@email.it>', $this->_message->getFrom());

        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setFrom([
            'email4@email.it' => [],
        ]));
        $this->assertEquals('My Application <email4@email.it>', $this->_message->getFrom());

        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setFrom('email@email.it'));

        $this->assertEquals('My Application <email@email.it>', $this->_message->getFrom());
    }

    public function testMessageSetCC()
    {
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setCc('email@email.it'));
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setCc([
            'email2@email.it' => 'fakeuser',
            'email@email.it',
            'email3@email.it',
        ]));
        $contactList = $this->_message->getCc();
        $this->assertCount(3, $contactList);
        $this->assertContains('email@email.it', $contactList);
        $this->assertArrayHasKey('email2@email.it', $contactList);
        $this->assertContains('email3@email.it', $contactList);
        $this->assertContains('fakeuser', $contactList);
    }

    public function testMessageSetBCC()
    {
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setBcc('email@email.it'));
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setBcc([
            'email2@email.it' => 'fakeuser',
            'email@email.it',
            'email3@email.it',
        ]));
        $contactList = $this->_message->getBcc();
        $this->assertCount(3, $contactList);
        $this->assertContains('email@email.it', $contactList);
        $this->assertArrayHasKey('email2@email.it', $contactList);
        $this->assertContains('email3@email.it', $contactList);
        $this->assertContains('fakeuser', $contactList);
    }

    public function testMessageSetSubject()
    {
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->setSubject('    <a>Testo '));
        $this->assertEquals('<a>Testo', $this->_message->getSubject());
    }

    public function testMessageSetTextBody()
    {
        $this->assertInstanceOf(
            '\MarketforceInfo\SendGrid\Message',
            $this->_message->setTextBody('testo<script>alert("ciao");</script>')
        );
        $this->assertEquals('testo', $this->_message->getTextBody());
    }

    public function testMessageSetHtmlBody()
    {
        $this->assertInstanceOf(
            '\MarketforceInfo\SendGrid\Message',
            $this->_message->setHtmlBody('<a>testo & co</a><script>alert("ciao");</script>')
        );
        $this->assertEquals('<a>testo & co</a><script>alert("ciao");</script>', $this->_message->getHtmlBody());
    }

    public function testMessageAttach()
    {
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->attach($this->getTestImagePath()));
        $this->assertInstanceOf(
            '\MarketforceInfo\SendGrid\Message',
            $this->_message->attach($this->getTestImagePath(), [
                'fileName' => 'test2.png',
                'contentType' => 'text/html',
            ])
        );
        $this->assertInstanceOf(
            '\MarketforceInfo\SendGrid\Message',
            $this->_message->attach(__DIR__ . DIRECTORY_SEPARATOR . 'asdf.png')
        );
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->attach(__DIR__));
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->attachContent($this->getTestPdfBinary()));
        $this->assertInstanceOf(
            '\MarketforceInfo\SendGrid\Message',
            $this->_message->attachContent($this->getTestPdfBinary(), [
                'fileName' => '12.txt',
                'contentType' => 'image/png',
            ])
        );

        $attachments = $this->_message->getAttachments();
        $this->assertCount(4, $attachments);

        //        var_dump($attachments); exit;

        $this->assertEquals($this->getTestImageBinary(true), $attachments[0][0]);
        $this->assertEquals('test.png', $attachments[0][2]);
        $this->assertEquals('image/png', $attachments[0][1]);
        $this->assertEquals('attachment', $attachments[0][3]);

        $this->assertEquals($this->getTestImageBinary(true), $attachments[1][0]);
        $this->assertEquals('test2.png', $attachments[1][2]);
        $this->assertEquals('text/html', $attachments[1][1]);
        $this->assertEquals('attachment', $attachments[1][3]);

        $this->assertEquals($this->getTestPdfBinary(true), $attachments[2][0]);
        $this->assertEquals('file_2', $attachments[2][2]);
        $this->assertEquals('application/pdf', $attachments[2][1]);
        $this->assertEquals('attachment', $attachments[2][3]);

        $this->assertEquals($this->getTestPdfBinary(true), $attachments[3][0]);
        $this->assertEquals('12.txt', $attachments[3][2]);
        $this->assertEquals('image/png', $attachments[3][1]);
        $this->assertEquals('attachment', $attachments[3][3]);
    }

    public function testMessageEmbed()
    {
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->embed($this->getTestImagePath()));
        $this->assertInstanceOf(
            '\MarketforceInfo\SendGrid\Message',
            $this->_message->embed(
                $this->getTestImagePath(),
                [
                    'fileName' => 'test2.png',
                    'contentType' => 'image/jpeg',
                ]
            )
        );
        $this->assertInstanceOf(
            '\MarketforceInfo\SendGrid\Message',
            $this->_message->embed(__DIR__ . DIRECTORY_SEPARATOR . 'asdf.png')
        );
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->embed(__DIR__));
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->embed($this->getTestPdfPath()));
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->embedContent('ancora un po'));
        $this->assertInstanceOf('\MarketforceInfo\SendGrid\Message', $this->_message->embedContent($this->getTestImageBinary()));
        $this->assertInstanceOf(
            '\MarketforceInfo\SendGrid\Message',
            $this->_message->embedContent(
                $this->getTestImageBinary(),
                [
                    'fileName' => '12.txt',
                    'contentType' => 'text/html',
                ]
            )
        );

        $attachments = $this->_message->getEmbeddedContent();
        $this->assertCount(4, $attachments);

        $this->assertEquals($this->getTestImageBinary(true), $attachments[0][0]);
        $this->assertEquals('test.png', $attachments[0][2]);
        $this->assertEquals('image/png', $attachments[0][1]);
        $this->assertEquals('inline', $attachments[0][3]);

        $this->assertEquals($this->getTestImageBinary(true), $attachments[1][0]);
        $this->assertEquals('test2.png', $attachments[1][2]);
        $this->assertEquals('image/jpeg', $attachments[1][1]);
        $this->assertEquals('inline', $attachments[1][3]);

        $this->assertEquals($this->getTestImageBinary(true), $attachments[2][0]);
        $this->assertEquals('file_2', $attachments[2][2]);
        $this->assertEquals('image/png', $attachments[2][1]);
        $this->assertEquals('inline', $attachments[2][3]);

        $this->assertEquals($this->getTestImageBinary(true), $attachments[3][0]);
        $this->assertEquals('12.txt', $attachments[3][2]);
        $this->assertEquals('text/html', $attachments[3][1]);
        $this->assertEquals('inline', $attachments[3][3]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $mailer = new Mailer([
            'apiKey' => 'testing',
        ]);
        $this->_message = $mailer->compose();
    }


    private function getTestImagePath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'test.png';
    }

    /**
     * @param boolean $encode
     */
    private function getTestImageBinary(bool $encode = false): string
    {
        if (!isset($this->_testImageBinary)) {
            $this->_testImageBinary = file_get_contents($this->getTestImagePath());
        }

        return $encode ? base64_encode($this->_testImageBinary) : $this->_testImageBinary;
    }


    private function getTestPdfPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'test.pdf';
    }

    /**
     * @param boolean $encode
     */
    private function getTestPdfBinary(bool $encode = false): string
    {
        if (!isset($this->_testPdfBinary)) {
            $this->_testPdfBinary = file_get_contents($this->getTestPdfPath());
        }

        return $encode ? base64_encode($this->_testPdfBinary) : $this->_testPdfBinary;
    }
}
