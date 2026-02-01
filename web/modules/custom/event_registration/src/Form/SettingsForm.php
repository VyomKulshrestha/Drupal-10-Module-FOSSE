<?php

namespace Drupal\event_registration\Form;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Event Registration module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The email validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EmailValidatorInterface $email_validator) {
    parent::__construct($config_factory);
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['event_registration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('event_registration.settings');

    $form['email_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Email Notification Settings'),
    ];

    $form['email_settings']['admin_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Admin Notification Email'),
      '#default_value' => $config->get('admin_email'),
      '#description' => $this->t('Email address where admin notifications will be sent.'),
      '#required' => TRUE,
    ];

    $form['email_settings']['enable_admin_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Admin Notifications'),
      '#default_value' => $config->get('enable_admin_notification') ?? TRUE,
      '#description' => $this->t('When enabled, an email notification will be sent to the admin for each new registration.'),
    ];

    $form['email_settings']['enable_user_notification'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable User Confirmation Emails'),
      '#default_value' => $config->get('enable_user_notification') ?? TRUE,
      '#description' => $this->t('When enabled, a confirmation email will be sent to the user after registration.'),
    ];

    $form['general_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General Settings'),
    ];

    $form['general_settings']['site_name_in_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site Name for Emails'),
      '#default_value' => $config->get('site_name_in_email') ?? $this->config('system.site')->get('name'),
      '#description' => $this->t('The site name to use in email notifications.'),
      '#maxlength' => 255,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $email = $form_state->getValue('admin_email');
    if (!empty($email) && !$this->emailValidator->isValid($email)) {
      $form_state->setErrorByName('admin_email', $this->t('Please enter a valid email address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('event_registration.settings')
      ->set('admin_email', $form_state->getValue('admin_email'))
      ->set('enable_admin_notification', $form_state->getValue('enable_admin_notification'))
      ->set('enable_user_notification', $form_state->getValue('enable_user_notification'))
      ->set('site_name_in_email', $form_state->getValue('site_name_in_email'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
