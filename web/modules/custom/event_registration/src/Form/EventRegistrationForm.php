<?php

namespace Drupal\event_registration\Form;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\event_registration\Service\EventRegistrationService;
use Drupal\event_registration\Service\EventMailService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Event Registration Form for users.
 *
 * Provides a public-facing registration form with:
 * - AJAX-powered cascading dropdowns (Category -> Date -> Event)
 * - Validation for duplicates and special characters
 * - Email notifications on successful registration
 */
class EventRegistrationForm extends FormBase {

  /**
   * The event registration service.
   *
   * @var \Drupal\event_registration\Service\EventRegistrationService
   */
  protected $registrationService;

  /**
   * The event mail service.
   *
   * @var \Drupal\event_registration\Service\EventMailService
   */
  protected $mailService;

  /**
   * The email validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * Constructs an EventRegistrationForm object.
   *
   * @param \Drupal\event_registration\Service\EventRegistrationService $registration_service
   *   The event registration service.
   * @param \Drupal\event_registration\Service\EventMailService $mail_service
   *   The event mail service.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator service.
   */
  public function __construct(
    EventRegistrationService $registration_service,
    EventMailService $mail_service,
    EmailValidatorInterface $email_validator
  ) {
    $this->registrationService = $registration_service;
    $this->mailService = $mail_service;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_registration.registration_service'),
      $container->get('event_registration.mail_service'),
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Check if there are any events available for registration.
    $available_categories = $this->registrationService->getAvailableCategories();

    if (empty($available_categories)) {
      $form['no_events'] = [
        '#markup' => '<div class="messages messages--warning">' .
          $this->t('There are currently no events available for registration. Please check back later.') .
          '</div>',
      ];
      return $form;
    }

    $form['#prefix'] = '<div id="event-registration-form-wrapper">';
    $form['#suffix'] = '</div>';

    // Personal Information fieldset.
    $form['personal_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Personal Information'),
    ];

    $form['personal_info']['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter your full name.'),
    ];

    $form['personal_info']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter your email address.'),
    ];

    $form['personal_info']['college_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('College Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter your college name.'),
    ];

    $form['personal_info']['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter your department.'),
    ];

    // Event Selection fieldset.
    $form['event_selection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Event Selection'),
    ];

    $form['event_selection']['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category of the Event'),
      '#options' => ['' => $this->t('- Select Category -')] + $available_categories,
      '#required' => TRUE,
      '#description' => $this->t('Select the category of the event.'),
      '#ajax' => [
        'callback' => '::updateEventDates',
        'wrapper' => 'event-date-wrapper',
        'event' => 'change',
      ],
    ];

    // Get selected category.
    $selected_category = $form_state->getValue('category', '');

    // Get event dates for selected category.
    $event_dates = [];
    if (!empty($selected_category)) {
      $event_dates = $this->registrationService->getAvailableEventDates($selected_category);
    }

    $form['event_selection']['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#options' => ['' => $this->t('- Select Event Date -')] + $event_dates,
      '#required' => TRUE,
      '#description' => $this->t('Select the event date.'),
      '#prefix' => '<div id="event-date-wrapper">',
      '#suffix' => '</div>',
      '#validated' => TRUE,
      '#ajax' => [
        'callback' => '::updateEventNames',
        'wrapper' => 'event-name-wrapper',
        'event' => 'change',
      ],
    ];

    // Get selected event date.
    $selected_date = $form_state->getValue('event_date', '');

    // Get event names for selected category and date.
    $event_names = [];
    if (!empty($selected_category) && !empty($selected_date)) {
      $event_names = $this->registrationService->getAvailableEventNames($selected_category, $selected_date);
    }

    $form['event_selection']['event_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#options' => ['' => $this->t('- Select Event -')] + $event_names,
      '#required' => TRUE,
      '#description' => $this->t('Select the event.'),
      '#prefix' => '<div id="event-name-wrapper">',
      '#suffix' => '</div>',
      '#validated' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * AJAX callback to update event dates dropdown.
   */
  public function updateEventDates(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Reset the event date selection.
    $form['event_selection']['event_date']['#value'] = '';

    // Reset the event name selection.
    $form['event_selection']['event_name']['#value'] = '';
    $form['event_selection']['event_name']['#options'] = ['' => $this->t('- Select Event -')];

    $response->addCommand(new ReplaceCommand('#event-date-wrapper', $form['event_selection']['event_date']));
    $response->addCommand(new ReplaceCommand('#event-name-wrapper', $form['event_selection']['event_name']));

    return $response;
  }

  /**
   * AJAX callback to update event names dropdown.
   */
  public function updateEventNames(array &$form, FormStateInterface $form_state) {
    return $form['event_selection']['event_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $full_name = $form_state->getValue('full_name');
    $email = $form_state->getValue('email');
    $college_name = $form_state->getValue('college_name');
    $department = $form_state->getValue('department');
    $event_name = $form_state->getValue('event_name');

    // Validate text fields for special characters.
    if (!empty($full_name) && !$this->isValidTextField($full_name)) {
      $form_state->setErrorByName('full_name', $this->t('Full name contains invalid characters. Only letters, numbers, spaces, hyphens, and periods are allowed.'));
    }

    if (!empty($college_name) && !$this->isValidTextField($college_name)) {
      $form_state->setErrorByName('college_name', $this->t('College name contains invalid characters. Only letters, numbers, spaces, hyphens, and periods are allowed.'));
    }

    if (!empty($department) && !$this->isValidTextField($department)) {
      $form_state->setErrorByName('department', $this->t('Department contains invalid characters. Only letters, numbers, spaces, hyphens, and periods are allowed.'));
    }

    // Validate email format.
    if (!empty($email) && !$this->emailValidator->isValid($email)) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
    }

    // Check for duplicate registration (email + event).
    if (!empty($email) && !empty($event_name)) {
      $event = $this->registrationService->getEventById($event_name);
      if ($event && $this->registrationService->isDuplicateRegistration($email, $event->event_date)) {
        $form_state->setErrorByName('email', $this->t('You have already registered for an event on this date with this email address.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event_id = $form_state->getValue('event_name');
    $event = $this->registrationService->getEventById($event_id);

    if (!$event) {
      $this->messenger()->addError($this->t('The selected event is no longer available.'));
      return;
    }

    $data = [
      'full_name' => $form_state->getValue('full_name'),
      'email' => $form_state->getValue('email'),
      'college_name' => $form_state->getValue('college_name'),
      'department' => $form_state->getValue('department'),
      'category' => $form_state->getValue('category'),
      'event_id' => $event_id,
    ];

    $result = $this->registrationService->saveRegistration($data);

    if ($result) {
      // Get category label.
      $categories = [
        'online_workshop' => $this->t('Online Workshop'),
        'hackathon' => $this->t('Hackathon'),
        'conference' => $this->t('Conference'),
        'one_day_workshop' => $this->t('One-day Workshop'),
      ];

      $email_params = [
        'full_name' => $data['full_name'],
        'email' => $data['email'],
        'college_name' => $data['college_name'],
        'department' => $data['department'],
        'event_name' => $event->event_name,
        'event_date' => $event->event_date,
        'category' => $categories[$data['category']] ?? $data['category'],
      ];

      // Send confirmation emails.
      $this->mailService->sendRegistrationConfirmation($email_params);

      $this->messenger()->addStatus($this->t('Thank you for registering! A confirmation email has been sent to @email.', [
        '@email' => $data['email'],
      ]));
    }
    else {
      $this->messenger()->addError($this->t('There was an error processing your registration. Please try again.'));
    }
  }

  /**
   * Validates text field for special characters.
   *
   * @param string $value
   *   The value to validate.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  protected function isValidTextField($value) {
    // Allow letters (including unicode), numbers, spaces, hyphens, periods.
    return (bool) preg_match('/^[\p{L}\p{N}\s\-\.]+$/u', $value);
  }

}
