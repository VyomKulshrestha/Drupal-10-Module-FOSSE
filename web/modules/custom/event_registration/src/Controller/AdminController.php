<?php

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\event_registration\Service\EventRegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller for admin listing and export functionality.
 *
 * Provides administrative features:
 * - Registration listing with AJAX filtering
 * - CSV export functionality
 * - Participant count display
 */
class AdminController extends ControllerBase {

  /**
   * The event registration service.
   *
   * @var \Drupal\event_registration\Service\EventRegistrationService
   */
  protected $registrationService;

  /**
   * Constructs an AdminController object.
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
   * Displays the registrations listing page.
   *
   * @return array
   *   A render array.
   */
  public function listRegistrations() {
    $event_dates = $this->registrationService->getAllEventDates();

    $build = [];

    $build['#attached']['library'][] = 'core/drupal.ajax';
    $build['#attached']['library'][] = 'event_registration/admin';

    $build['filters'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['event-registration-filters']],
    ];

    $build['filters']['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#options' => ['' => $this->t('- Select Date -')] + $event_dates,
      '#attributes' => [
        'id' => 'edit-event-date-filter',
        'class' => ['event-date-filter'],
      ],
    ];

    $build['filters']['event_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#options' => ['' => $this->t('- Select Event -')],
      '#attributes' => [
        'id' => 'edit-event-name-filter',
        'class' => ['event-name-filter'],
      ],
      '#prefix' => '<div id="event-name-filter-wrapper">',
      '#suffix' => '</div>',
    ];

    $build['filters']['filter_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Filter'),
      '#attributes' => [
        'id' => 'filter-registrations-btn',
        'class' => ['button', 'button--primary'],
      ],
    ];

    $build['export'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['event-registration-export']],
    ];

    $build['export']['export_link'] = [
      '#type' => 'link',
      '#title' => $this->t('Export to CSV'),
      '#url' => \Drupal\Core\Url::fromRoute('event_registration.export_csv'),
      '#attributes' => [
        'id' => 'export-csv-link',
        'class' => ['button', 'button--secondary'],
      ],
    ];

    $build['participant_count'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'participant-count',
        'class' => ['participant-count'],
      ],
      '#markup' => '<strong>' . $this->t('Total Participants:') . '</strong> <span id="count-value">0</span>',
    ];

    $build['registrations_table'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'registrations-table-wrapper',
        'class' => ['registrations-table-wrapper'],
      ],
    ];

    $build['registrations_table']['table'] = [
      '#type' => 'table',
      '#header' => [
        'name' => $this->t('Name'),
        'email' => $this->t('Email'),
        'event_date' => $this->t('Event Date'),
        'college_name' => $this->t('College Name'),
        'department' => $this->t('Department'),
        'submission_date' => $this->t('Submission Date'),
      ],
      '#rows' => [],
      '#empty' => $this->t('Select an event date and event name to view registrations.'),
      '#attributes' => ['id' => 'registrations-table'],
    ];

    return $build;
  }

  /**
   * AJAX callback to get events for a date.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The JSON response.
   */
  public function ajaxGetEvents(Request $request) {
    $date = $request->query->get('date', '');

    $events = [];
    if (!empty($date)) {
      $events = $this->registrationService->getEventsForDate($date);
    }

    return new Response(
      json_encode($events),
      200,
      ['Content-Type' => 'application/json']
    );
  }

  /**
   * AJAX callback to get registrations for an event.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The JSON response.
   */
  public function ajaxGetRegistrations(Request $request) {
    $event_id = $request->query->get('event_id', '');

    $data = [
      'count' => 0,
      'rows' => [],
    ];

    if (!empty($event_id)) {
      $registrations = $this->registrationService->getRegistrationsForEvent($event_id);
      $data['count'] = count($registrations);

      foreach ($registrations as $registration) {
        $data['rows'][] = [
          'name' => htmlspecialchars($registration->full_name),
          'email' => htmlspecialchars($registration->email),
          'event_date' => date('F j, Y', strtotime($registration->event_date)),
          'college_name' => htmlspecialchars($registration->college_name),
          'department' => htmlspecialchars($registration->department),
          'submission_date' => date('F j, Y g:i A', $registration->created),
        ];
      }
    }

    return new Response(
      json_encode($data),
      200,
      ['Content-Type' => 'application/json']
    );
  }

  /**
   * Exports registrations to CSV.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   The CSV file response.
   */
  public function exportCsv(Request $request) {
    $event_id = $request->query->get('event_id', NULL);
    $registrations = $this->registrationService->getAllRegistrations($event_id);

    $category_labels = [
      'online_workshop' => 'Online Workshop',
      'hackathon' => 'Hackathon',
      'conference' => 'Conference',
      'one_day_workshop' => 'One-day Workshop',
    ];

    $response = new StreamedResponse(function () use ($registrations, $category_labels) {
      $handle = fopen('php://output', 'w');

      // Add UTF-8 BOM for Excel compatibility.
      fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

      // Write header.
      fputcsv($handle, [
        'ID',
        'Full Name',
        'Email',
        'College Name',
        'Department',
        'Category',
        'Event Name',
        'Event Date',
        'Submission Date',
      ]);

      // Write data rows.
      foreach ($registrations as $registration) {
        fputcsv($handle, [
          $registration->id,
          $registration->full_name,
          $registration->email,
          $registration->college_name,
          $registration->department,
          $category_labels[$registration->category] ?? $registration->category,
          $registration->event_name,
          date('Y-m-d', strtotime($registration->event_date)),
          date('Y-m-d H:i:s', $registration->created),
        ]);
      }

      fclose($handle);
    });

    $filename = 'event_registrations_' . date('Y-m-d_His') . '.csv';
    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    $response->headers->set('Cache-Control', 'max-age=0');

    return $response;
  }

}
