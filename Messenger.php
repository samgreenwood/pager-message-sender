<?php

require "vendor/autoload.php";

use PEAR2\Net\RouterOS;
use Symfony\Component\Yaml\Yaml;

$config = Yaml::parse(file_get_contents(__DIR__ . '/config.yml'));

$dataFilePath = $config['messenger']['data_file'];

if ( ! file_exists($dataFilePath)) die('Could not find datafile');

// RouterOS Credentials for sending SMS

$routerOsUsername = $config['messenger']['routeros']['username'];
$routerOsPassword = $config['messenger']['routeros']['password'];
$routerOsHost = $config['messenger']['routeros']['host'];

// SMTP Server Details

$emailUsername = $config['messenger']['smtp']['username'];
$emailPassword = $config['messenger']['smtp']['password'];
$emailHost = $config['messenger']['smtp']['host'];
$emailPort = $config['messenger']['smtp']['port'];
$fromName = $config['messenger']['smtp']['from_name'];
$fromAddress = $config['messenger']['smtp']['from_address'];

// Email addresses and mobile numbers to send notifications to

$emails = $config['messenger']['emails'];
$phones = $config['messenger']['mobiles'];

$sendEmail = $config['messenger']['send_email'];
$sendSMS = $config['messenger']['send_sms'];

// Get the data from the file
$message = file_get_contents($dataFilePath);
$message = trim(preg_replace('/\s+/', ' ', $message));

if ($sendEmail) {
    $transport = Swift_SmtpTransport::newInstance($emailHost, $emailPort)
        ->setUsername($emailUsername)
        ->setPassword($emailPassword);

    $mailer = Swift_Mailer::newInstance($transport);

    foreach ($emails as $email) {
        echo sprintf("Sending E-Mail to %s", $email) . PHP_EOL;
    }

    // Create a message
    $emailMessage = Swift_Message::newInstance($fromName)
        ->setFrom([$fromAddress => $fromName])
        ->setTo($emails)
        ->setBody($message);

    // Send the message
    $result = $mailer->send($emailMessage);
}

if ($sendSMS) {

    $client = new RouterOS\Client($routerOsHost, $routerOsUsername, $routerOsPassword);

    foreach ($phones as $phone) {

        echo sprintf("Sending SMS to %s", $phone) . PHP_EOL;

        $smsRequest = new RouterOS\Request("/tool sms send port=usb1 channel=3");
        $smsRequest->setArgument('phone-number', $phone);
        $smsRequest->setArgument('message', $message);

        $client->sendSync($smsRequest);
    }

}