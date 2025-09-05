# MarketforceInfo/Yii2-SendGrid

[![Code Checks](https://img.shields.io/github/actions/workflow/status/marketforce-info/yii2-sendgrid/code-checks.yml?branch=main&logo=github)](https://github.com/marketforce-info/yii2-sendgrid/actions/workflows/code-checks.yml)
[![Latest Stable Version](https://img.shields.io/github/v/release/marketforce-info/yii2-sendgrid?logo=packagist)](https://github.com/marketforce-info/yii2-sendgrid/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/marketforce-info/yii2-sendgrid?logo=packagist)](https://packagist.org/packages/marketforce-info/yii2-sendgrid)
![Licence](https://img.shields.io/github/license/marketforce-info/yii2-sendgrid.svg)

## Description
Yii2 Mailer extension for SendGrid with batch mailing support.

### Recommended alternative

A possible alternative to this component is the following combination
- [Yii2 Symfony Mailer](https://github.com/yiisoft/yii2-symfonymailer)
- [Symfony Mailer](https://symfony.com/doc/current/mailer.html)
- [SendGrid Bridge](https://github.com/symfony/symfony/blob/6.3/src/Symfony/Component/Mailer/Bridge/Sendgrid/README.md)

---

### Installation

```bash
$ composer require marketforce-info/yii2-sendgrid
```

Then configure your `mailer` component in your `main-local.php` (advanced) or `web.php` (basic) like so:
```php
    'mailer' => [
        'class' => \MarketforceInfo\SendGrid\Mailer::class,
        'viewPath' => '@common/mail',
        // send all mails to a file by default. You have to set
        // 'useFileTransport' to false and configure a transport
        // for the mailer to send real emails.
        'useFileTransport' => false,
        'apiKey' => '[YOUR_SENDGRID_API_KEY]',
    ],
```

Do not forget to replace `apiKey` with your SendGrid API key. It must have permissions to send emails.

### Usage

#### Basic

```php
    $mailer = Yii::$app->mailer;
    $message = $mailer->compose()
        ->setTo('user@example.com')
        ->setFrom(['alerts@example.com' => 'Alerts'])
        ->setReplyTo('noreply@example.com')
        ->setSubject('Example Email Subject')
        ->setTextBody('Example email body.')
        ->send();
```

#### Single Mailing

```php
    $user = \common\models\User::find()->select(['id', 'username', 'email'])->where(['id' => 1])->one();

    $mailer = Yii::$app->mailer;
    $message = $mailer->compose()
        ->setTo([$user->email => $user->username])
        ->setFrom(['alerts@example.com' => 'Alerts'])
        ->setReplyTo('noreply@example.com')
        ->setSubject('Example Email Subject')
        ->setHtmlBody('Dear -username-,<br><br>Example email HTML body.')
        ->setTextBody('Dear -username-,\n\nExample email text body.')
        ->send();

    if ($message === true) {
        echo 'Success!';
        echo '<pre>' . print_r($mailer->getRawResponses(), true) . '</pre>';
    } else {
        echo 'Error!<br>';
        echo '<pre>' . print_r($mailer->getErrors(), true) . '</pre>';
    }
```

#### Batch Mailing

If you want to send to multiple recipients, you need to use the below method to batch send.
```php
    $mailer = Yii::$app->mailer;

    foreach (User::find()->select(['id', 'username', 'email'])->batch(500) as $users) {

        $message = $mailer->compose()
            ->setFrom(['alerts@example.com' => 'Alerts'])
            ->setReplyTo('noreply@example.com')
            ->setSubject('Hey -username-, Example Email Subject')
            ->setHtmlBody('Dear -username-,<br><br>Example email HTML body.')
            ->setTextBody('Dear -username-,\n\nExample email text body.')

        foreach ( $users as $user )
        {
            // A Personalization Object Helper would be nice here...
            $personalization = [
                'to' => [$user->email => $user->username],      // or just `email@example.com`
                //'cc' => 'cc@example.com',
                //'bcc' => 'bcc@example.com',
                //'subject' => 'Hey -username-, Custom message for you!',
                'substitutions' => [
                    '-username-' => $user->username,
                ],
                //'custom_args' => [
                //    'user_id' => $user->id,
                //    'type' => 'marketing',
                //],
                //'send_at' => $sendTime,
            ];
            $message->addPersonalization($personalization);
        }

        $result = $message->send();
    }

    if ($result === true) {
        echo 'Success!';
        echo '<pre>' . print_r($mailer->getRawResponses(), true) . '</pre>';
    } else {
        echo 'Error!<br>';
        echo '<pre>' . print_r($mailer->getErrors(), true) . '</pre>';
    }
```

**NOTE:** SendGrid supports a max of 1,000 recipients. This is a total of the to, bcc, and cc addresses. I recommend using `500` for the batch size. This should be large enough to process thousands of emails efficiently without risking getting errors by accidentally breaking the 1,000 recipients rule. If you are not using any bcc or cc addresses, you *could* raise the batch number a little higher. Theoretically, you should be able to do 1,000 but I would probably max at 950 to leave some wiggle room.

---

### Known Issues

- `addSection()` - There is currently an issue with the SendGrid API where sections are not working.
- `setSendAt()` - There is currently an issue with the SendGrid API where using `send_at` where the time shows the queued time not the actual time that the email was sent.
- `setReplyTo()` - There is currently an issue with the SendGrid PHP API where the ReplyTo address only accepts the email address as a string. So you can't set a name.

---

### Todo

There are a few things left that I didn't get to:

- ASM
- mail_settings
- tracking_settings

Contributions gratefully accepted in the form issues or PRs.

## Attribution
This extension was originally created by https://www.github.com/wadeshuler
