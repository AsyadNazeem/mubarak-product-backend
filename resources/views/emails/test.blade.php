<?php
// Save this file as test-email.php in your Laravel project root

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Load configuration
$config = config('mail');

echo "Testing Mail Configuration:\n";
echo "==========================\n";
echo "Default Mailer: " . $config['default'] . "\n";
echo "SMTP Host: " . $config['mailers']['smtp']['host'] . "\n";
echo "SMTP Port: " . $config['mailers']['smtp']['port'] . "\n";
echo "From Address: " . $config['from']['address'] . "\n";
echo "From Name: " . $config['from']['name'] . "\n";
echo "Contact Form Recipient: " . $config['contact_form_recipient'] . "\n\n";

echo "Attempting to send a test email...\n";

try {
    // Create a simple message
    $message = (new Swift_Message('Laravel Mail Test'))
        ->setFrom([$config['from']['address'] => $config['from']['name']])
        ->setTo([$config['contact_form_recipient']])
        ->setBody('This is a test email to verify your Laravel mail configuration is working.');

    // Create the Transport
    $transport = (new Swift_SmtpTransport($config['mailers']['smtp']['host'], $config['mailers']['smtp']['port']))
        ->setUsername(env('MAIL_USERNAME'))
        ->setPassword(env('MAIL_PASSWORD'))
        ->setEncryption(env('MAIL_ENCRYPTION'));

    // Create the Mailer using your created Transport
    $mailer = new Swift_Mailer($transport);

    // Send the message
    $result = $mailer->send($message);

    if ($result) {
        echo "SUCCESS: Test email sent successfully!\n";
    } else {
        echo "ERROR: Failed to send test email.\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
