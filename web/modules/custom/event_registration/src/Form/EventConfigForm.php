<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\event_registration\Service\EventRegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Event Configuration Form for administrators.
 */
class EventConfigForm extends FormBase {

  /**
   * The event registration service.
   *
   * @var \Drupal\event_registration\Service\EventRegistrationService
   */
  protected $registrationService;

  /**
   * Constructs an EventConfigForm object.
   *
   * @param \Drupal\event_registration\Service\EventRegistrationService $registration_service
   *   The event registration service.
   */
  public function __construct(EventRegistrationService $registration_service) {
    $this->registrationService = $registration_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_registration.registration_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Event categories.
    $categories = [
      'online_workshop' => $this->t('Online Workshop'),
      'hackathon' => $this->t('Hackathon'),
      'conference' => $this->t('Conference'),
      'one_day_workshop' => $this->t('One-day Workshop'),
    ];

    $form['#prefix'] = '<div id="event-config-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['event_details'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Event Details'),
    ];

    $form['event_details']['event_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('Enter the name of the event.'),
    ];

    $form['event_details']['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#options' => $categories,
      '#required' => TRUE,
      '#description' => $this->t('Select the category of the event.'),
    ];

    $form['event_details']['event_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Date'),
      '#required' => TRUE,
      '#description' => $this->t('The date when the event will be held.'),
    ];

    $form['registration_period'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Registration Period'),
    ];

    $form['registration_period']['registration_start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Registration Start Date'),
      '#required' => TRUE,
      '#description' => $this->t('The date when registration opens.'),
    ];

    $form['registration_period']['registration_end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Registration End Date'),
      '#required' => TRUE,
      '#description' => $this->t('The date when registration closes.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Event'),
      '#button_type' => 'primary',
    ];

    // Display existing events.
    $form['existing_events'] = [
      '#type' => 'details',
      '#title' => $this->t('Existing Events'),
      '#open' => TRUE,
    ];

    $events = $this->registrationService->getAllEvents();
    if (!empty($events)) {
      $header = [
        'id' => $this->t('ID'),
        'event_name' => $this->t('Event Name'),
        'category' => $this->t('Category'),
        'event_date' => $this->t('Event Date'),
        'registration_start' => $this->t('Registration Start'),
        'registration_end' => $this->t('Registration End'),
        'operations' => $this->t('Operations'),
      ];

      $rows = [];
      foreach ($events as $event) {
        $rows[$event->id] = [
          'id' => $event->id,
          'event_name' => $event->event_name,
          'category' => $categories[$event->category] ?? $event->category,
          'event_date' => $event->event_date,
          'registration_start' => $event->registration_start_date,
          'registration_end' => $event->registration_end_date,
          'operations' => '',
        ];
      }

      $form['existing_events']['events_table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No events configured yet.'),
      ];
    }
    else {
      $form['existing_events']['no_events'] = [
        '#markup' => '<p>' . $this->t('No events configured yet.') . '</p>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $event_name = $form_state->getValue('event_name');
    $registration_start = $form_state->getValue('registration_start_date');
    $registration_end = $form_state->getValue('registration_end_date');
    $event_date = $form_state->getValue('event_date');

    // Validate event name for special characters.
    if (!$this->isValidTextField($event_name)) {
      $form_state->setErrorByName('event_name', $this->t('Event name contains invalid characters. Only letters, numbers, spaces, hyphens, and underscores are allowed.'));
    }

    // Validate date logic.
    if ($registration_start && $registration_end) {
      if (strtotime($registration_start) > strtotime($registration_end)) {
        $form_state->setErrorByName('registration_start_date', $this->t('Registration start date must be before or equal to the end date.'));
      }
    }

    if ($registration_end && $event_date) {
      if (strtotime($registration_end) > strtotime($event_date)) {
        $form_state->setErrorByName('registration_end_date', $this->t('Registration end date must be before or on the event date.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = [
      'event_name' => $form_state->getValue('event_name'),
      'category' => $form_state->getValue('category'),
      'event_date' => $form_state->getValue('event_date'),
      'registration_start_date' => $form_state->getValue('registration_start_date'),
      'registration_end_date' => $form_state->getValue('registration_end_date'),
    ];

    $result = $this->registrationService->saveEvent($data);

    if ($result) {
      $this->messenger()->addStatus($this->t('Event "@name" has been saved successfully.', [
        '@name' => $data['event_name'],
      ]));
    }
    else {
      $this->messenger()->addError($this->t('There was an error saving the event. Please try again.'));
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
    // Allow letters (including unicode), numbers, spaces, hyphens, underscores.
    return (bool) preg_match('/^[\p{L}\p{N}\s\-_]+$/u', $value);
  }

}
