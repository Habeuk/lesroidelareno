<?php

namespace Drupal\lesroidelareno\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Symfony\Component\Mime\Header\Headers;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\Mime\Header\UnstructuredHeader;
use Drupal\mimemail\Plugin\Mail\MimeMail;
use function Dflydev\DotAccessConfiguration\__construct;

/**
 * Defines the default Drupal mail backend, using PHP's native mail() function.
 *
 * @Mail(
 *   id = "wbh_php_mailer_plugin",
 *   label = @Translation("WbhPhpMailerPlugin"),
 *   description = @Translation("Permet d'envoyer les mails pour l'environnment wb-horizon")
 * )
 */
class WbhPhpMailerPlugin extends MimeMail {
  
  /**
   * On formate les données pour que cela soit compatible pour notre
   * environnement.
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Mail\Plugin\Mail\PhpMail::format()
   */
  public function format(array $message) {
    // Build the default headers.
    $headers = [
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/html; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal'
    ];
    // add default header
    foreach ($headers as $k => $value) {
      $message['headers'][$k] = $value;
    }
    // check from
    if (empty($message['from']) && !empty($message['headers']['From'])) {
      $message['from'] = $message['headers']['From'];
    }
    // check key
    if (empty($message['key'])) {
      $message['key'] = 'wbh_php_mailer_plugin_key';
    }
    // check module
    if (empty($message['module'])) {
      $message['module'] = 'lesroidelareno';
    }
    // check id
    if (empty($message['id'])) {
      $message['id'] = 'wbh_php_mailer_plugin_id';
    }
    $message = parent::format($message);
    return $message;
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Mail\Plugin\Mail\PhpMail::mail()
   */
  public function mail(array $message) {
    $message = $this->format($message);
    return parent::mail($message);
  }
  
}