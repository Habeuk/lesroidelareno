<?php

namespace Drupal\lesroidelareno\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Symfony\Component\Mime\Header\Headers;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\Mime\Header\UnstructuredHeader;

/**
 * Defines the default Drupal mail backend, using PHP's native mail() function.
 *
 * @Mail(
 *   id = "test_php_mailer_plugin",
 *   label = @Translation("TestPhpMailerPlugin"),
 *   description = @Translation("Sends the message as plain text, using PHP's native mail() function.")
 * )
 */
class TestPhpMailerPlugin extends PhpMail {
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Mail\Plugin\Mail\PhpMail::mail()
   */
  public function mail(array $message) {
    // \Stephane888\Debug\debugLog::kintDebugDrupal($message,
    // 'TestPhpMailerPlugin--', true);
    return parent::mail($message);
  }
  
}