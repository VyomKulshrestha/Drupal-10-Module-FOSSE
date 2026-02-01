<?php

namespace Drupal\event_registration\Service;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for sending event registration emails.
 *
 * Handles email notifications using Drupal Mail API:
 * - User confirmation emails
 * - Admin notification emails
 * - Configurable email settings
 */
class EventMailService {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs an EventMailService object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    MailManagerInterface $mail_manager,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    LanguageManagerInterface $language_manager
  ) {
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('event_registration');
    $this->languageManager = $language_manager;
  }

  /**
   * Sends registration confirmation emails.
   *
   * @param array $params
   *   The email parameters.
   *
   * @return bool
   *   TRUE if all emails were sent successfully.
   */
  public function sendRegistrationConfirmation(array $params) {
    $config = $this->configFactory->get('event_registration.settings');
    $success = TRUE;

    // Send confirmation to user.
    if ($config->get('enable_user_notification') !== FALSE) {
      $user_result = $this->sendMail('registration_confirmation', $params['email'], $params);
      if (!$user_result) {
        $this->logger->error('Failed to send confirmation email to user: @email', [
          '@email' => $params['email'],
        ]);
        $success = FALSE;
      }
    }

    // Send notification to admin.
    if ($config->get('enable_admin_notification')) {
      $admin_email = $config->get('admin_email');
      if (!empty($admin_email)) {
        $admin_result = $this->sendMail('admin_notification', $admin_email, $params);
        if (!$admin_result) {
          $this->logger->error('Failed to send notification email to admin: @email', [
            '@email' => $admin_email,
          ]);
          $success = FALSE;
        }
      }
    }

    return $success;
  }

  /**
   * Sends an email.
   *
   * @param string $key
   *   The email key.
   * @param string $to
   *   The recipient email address.
   * @param array $params
   *   The email parameters.
   *
   * @return bool
   *   TRUE if the email was sent successfully.
   */
  protected function sendMail($key, $to, array $params) {
    $langcode = $this->languageManager->getDefaultLanguage()->getId();
    $result = $this->mailManager->mail('event_registration', $key, $to, $langcode, $params, NULL, TRUE);

    return !empty($result['result']);
  }

}
