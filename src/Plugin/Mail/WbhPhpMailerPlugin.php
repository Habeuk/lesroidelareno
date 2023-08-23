<?php

namespace Drupal\lesroidelareno\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Symfony\Component\Mime\Header\Headers;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\Mime\Header\UnstructuredHeader;
use Drupal\mimemail\Plugin\Mail\MimeMail;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\mimemail\Utility\MimeMailFormatHelper;

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
    // si les données sont dans un array render.
    if (is_array($message['body']) && !empty($message['body']['#theme'])) {
      /**
       *
       * @var \Drupal\Core\Render\Renderer $renderer
       */
      $renderer = \Drupal::service('renderer');
      $message['body'] = $renderer->renderPlain($message['body']);
    }
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
   * Prepares the message for sending.
   *
   * @param array $message
   *        An array containing the message data. The optional parameters are:
   *        - plain: (optional) Whether to send the message as plaintext only or
   *        HTML. If this evaluates to TRUE the message will be sent as
   *        plaintext.
   *        - plaintext: (optional) Plaintext portion of a multipart email.
   *        - attachments: (optional) An array where each element is an array
   *        that
   *        describes an attachment. Existing files may be added by path while
   *        dynamically-generated files may be added by content. Each internal
   *        array contains the following elements:
   *        - filepath: Relative Drupal path to an existing file
   *        (filecontent is NULL).
   *        - filecontent: The actual content of the file (filepath is NULL).
   *        - filename: (optional) The filename of the file.
   *        - filemime: (optional) The MIME type of the file.
   *        The array of arrays looks something like this:
   *     @code
   *     [
   *       0 => [
   *         'filepath' => '/sites/default/files/attachment.txt',
   *         'filecontent' => NULL,
   *         'filename' => 'attachment1.txt',
   *         'filemime' => 'text/plain',
   *       ],
   *       1 => [
   *         'filepath' => NULL,
   *         'filecontent' => 'This is the contents of my second attachment.',
   *         'filename' => 'attachment2.txt',
   *         'filemime' => 'text/plain',
   *       ],
   *     ]
   *     @endcode
   *
   * @return array All details of the message.
   */
  protected function prepareMessage(array $message) {
    $module = $message['module'];
    $key = $message['key'];
    $to = $message['to'];
    $from = $message['from'];
    $subject = $message['subject'];
    $body = $message['body'];
    
    $headers = $message['params']['headers'] ?? [];
    $plain = $message['params']['plain'] ?? NULL;
    $plaintext = $message['params']['plaintext'] ?? NULL;
    $attachments = $message['params']['attachments'] ?? [];
    
    $site_name = $this->configFactory->get('system.site')->get('name');
    $site_mail = $this->configFactory->get('system.site')->get('mail');
    $simple_address = $this->configFactory->get('mimemail.settings')->get('simple_address');
    
    // Override site mail's default sender.
    if ((empty($from) || $from == $site_mail)) {
      $mimemail_name = $this->configFactory->get('mimemail.settings')->get('name');
      $mimemail_mail = $this->configFactory->get('mimemail.settings')->get('mail');
      $from = [
        'name' => !empty($mimemail_name) ? $mimemail_name : $site_name,
        'mail' => !empty($mimemail_mail) ? $mimemail_mail : $site_mail
      ];
    }
    
    if (empty($body)) {
      // Body is empty, this is a plaintext message.
      $plain = TRUE;
    }
    // Try to determine recipient's text mail preference.
    elseif (is_null($plain)) {
      if (is_string($to) && $this->emailValidator->isValid($to)) {
        $user_plaintext_field = $this->configFactory->get('mimemail.settings')->get('user_plaintext_field');
        if (is_object($account = user_load_by_mail($to)) && $account->hasField($user_plaintext_field)) {
          /** @var boolean $plain */
          $plain = $account->{$user_plaintext_field}->value;
          // Might as well pass the user object to the address function.
          $to = $account;
        }
      }
    }
    
    // MailFormatHelper::htmlToText() removes \r and adds \n both directly and
    // within the utility method MailFormatHelper::wrapMailLine(). Subject
    // headers can't contain \n characters, so we remove those here.
    $subject = str_replace([
      "\n"
    ], '', trim(MailFormatHelper::htmlToText($subject)));
    
    $body = [
      '#theme' => 'lesroidelareno_main_mail',
      '#module' => $module,
      '#key' => $key,
      '#recipient' => $to,
      '#subject' => $subject,
      '#body' => $body
    ];
    
    $body = $this->renderer->renderPlain($body);
    
    $plain = $plain || $this->configFactory->get('mimemail.settings')->get('textonly');
    $from = MimeMailFormatHelper::mimeMailAddress($from);
    $mail = MimeMailFormatHelper::mimeMailHtmlBody($body, $subject, $plain, $plaintext, $attachments);
    $headers = array_merge($message['headers'], $headers, $mail['headers']);
    
    $message['to'] = MimeMailFormatHelper::mimeMailAddress($to, $simple_address);
    $message['from'] = $from;
    $message['subject'] = $subject;
    $message['body'] = $mail['body'];
    $message['headers'] = MimeMailFormatHelper::mimeMailHeaders($headers, $from);
    
    return $message;
  }
  
  /**
   *
   * {@inheritdoc}
   * @see \Drupal\Core\Mail\Plugin\Mail\PhpMail::mail()
   */
  public function mail(array $message) {
    return parent::mail($message);
  }
  
}