<?php

namespace Drupal\event_registration\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for managing event registrations.
 */
class EventRegistrationService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * Constructs an EventRegistrationService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    Connection $database,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('event_registration');
  }

  /**
   * Saves a new event.
   *
   * @param array $data
   *   The event data.
   *
   * @return int|bool
   *   The event ID on success, FALSE on failure.
   */
  public function saveEvent(array $data) {
    try {
      $result = $this->database->insert('event_registration_events')
        ->fields([
          'event_name' => $data['event_name'],
          'category' => $data['category'],
          'event_date' => $data['event_date'],
          'registration_start_date' => $data['registration_start_date'],
          'registration_end_date' => $data['registration_end_date'],
          'created' => time(),
        ])
        ->execute();

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error saving event: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Gets all events.
   *
   * @return array
   *   An array of event objects.
   */
  public function getAllEvents() {
    try {
      return $this->database->select('event_registration_events', 'e')
        ->fields('e')
        ->orderBy('event_date', 'ASC')
        ->execute()
        ->fetchAll();
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching events: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Gets an event by ID.
   *
   * @param int $id
   *   The event ID.
   *
   * @return object|null
   *   The event object or NULL if not found.
   */
  public function getEventById($id) {
    try {
      return $this->database->select('event_registration_events', 'e')
        ->fields('e')
        ->condition('id', $id)
        ->execute()
        ->fetchObject();
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching event: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets categories with available events (within registration period).
   *
   * @return array
   *   An array of categories.
   */
  public function getAvailableCategories() {
    $today = date('Y-m-d');

    try {
      $categories = $this->database->select('event_registration_events', 'e')
        ->fields('e', ['category'])
        ->condition('registration_start_date', $today, '<=')
        ->condition('registration_end_date', $today, '>=')
        ->distinct()
        ->execute()
        ->fetchCol();

      $category_labels = [
        'online_workshop' => t('Online Workshop'),
        'hackathon' => t('Hackathon'),
        'conference' => t('Conference'),
        'one_day_workshop' => t('One-day Workshop'),
      ];

      $result = [];
      foreach ($categories as $category) {
        if (isset($category_labels[$category])) {
          $result[$category] = $category_labels[$category];
        }
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching categories: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Gets available event dates for a category.
   *
   * @param string $category
   *   The event category.
   *
   * @return array
   *   An array of event dates.
   */
  public function getAvailableEventDates($category) {
    $today = date('Y-m-d');

    try {
      $dates = $this->database->select('event_registration_events', 'e')
        ->fields('e', ['event_date'])
        ->condition('category', $category)
        ->condition('registration_start_date', $today, '<=')
        ->condition('registration_end_date', $today, '>=')
        ->distinct()
        ->orderBy('event_date', 'ASC')
        ->execute()
        ->fetchCol();

      $result = [];
      foreach ($dates as $date) {
        $result[$date] = date('F j, Y', strtotime($date));
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching event dates: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Gets available event names for a category and date.
   *
   * @param string $category
   *   The event category.
   * @param string $date
   *   The event date.
   *
   * @return array
   *   An array of event names keyed by event ID.
   */
  public function getAvailableEventNames($category, $date) {
    $today = date('Y-m-d');

    try {
      $events = $this->database->select('event_registration_events', 'e')
        ->fields('e', ['id', 'event_name'])
        ->condition('category', $category)
        ->condition('event_date', $date)
        ->condition('registration_start_date', $today, '<=')
        ->condition('registration_end_date', $today, '>=')
        ->orderBy('event_name', 'ASC')
        ->execute()
        ->fetchAllKeyed();

      return $events;
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching event names: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Saves a registration.
   *
   * @param array $data
   *   The registration data.
   *
   * @return int|bool
   *   The registration ID on success, FALSE on failure.
   */
  public function saveRegistration(array $data) {
    try {
      $result = $this->database->insert('event_registration_registrations')
        ->fields([
          'full_name' => $data['full_name'],
          'email' => $data['email'],
          'college_name' => $data['college_name'],
          'department' => $data['department'],
          'category' => $data['category'],
          'event_id' => $data['event_id'],
          'created' => time(),
        ])
        ->execute();

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error saving registration: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Checks if a registration is a duplicate.
   *
   * @param string $email
   *   The email address.
   * @param string $event_date
   *   The event date.
   *
   * @return bool
   *   TRUE if duplicate, FALSE otherwise.
   */
  public function isDuplicateRegistration($email, $event_date) {
    try {
      $query = $this->database->select('event_registration_registrations', 'r');
      $query->join('event_registration_events', 'e', 'r.event_id = e.id');
      $count = $query->condition('r.email', $email)
        ->condition('e.event_date', $event_date)
        ->countQuery()
        ->execute()
        ->fetchField();

      return $count > 0;
    }
    catch (\Exception $e) {
      $this->logger->error('Error checking duplicate: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Gets all event dates that have registrations.
   *
   * @return array
   *   An array of event dates.
   */
  public function getEventDatesWithRegistrations() {
    try {
      $query = $this->database->select('event_registration_events', 'e');
      $query->join('event_registration_registrations', 'r', 'e.id = r.event_id');
      $dates = $query->fields('e', ['event_date'])
        ->distinct()
        ->orderBy('event_date', 'DESC')
        ->execute()
        ->fetchCol();

      $result = [];
      foreach ($dates as $date) {
        $result[$date] = date('F j, Y', strtotime($date));
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching event dates: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Gets all event dates from events table.
   *
   * @return array
   *   An array of event dates.
   */
  public function getAllEventDates() {
    try {
      $dates = $this->database->select('event_registration_events', 'e')
        ->fields('e', ['event_date'])
        ->distinct()
        ->orderBy('event_date', 'DESC')
        ->execute()
        ->fetchCol();

      $result = [];
      foreach ($dates as $date) {
        $result[$date] = date('F j, Y', strtotime($date));
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching event dates: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Gets events for a specific date.
   *
   * @param string $date
   *   The event date.
   *
   * @return array
   *   An array of events keyed by ID.
   */
  public function getEventsForDate($date) {
    try {
      return $this->database->select('event_registration_events', 'e')
        ->fields('e', ['id', 'event_name'])
        ->condition('event_date', $date)
        ->orderBy('event_name', 'ASC')
        ->execute()
        ->fetchAllKeyed();
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching events: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Gets registrations for a specific event.
   *
   * @param int $event_id
   *   The event ID.
   *
   * @return array
   *   An array of registration objects.
   */
  public function getRegistrationsForEvent($event_id) {
    try {
      $query = $this->database->select('event_registration_registrations', 'r');
      $query->join('event_registration_events', 'e', 'r.event_id = e.id');
      return $query->fields('r')
        ->fields('e', ['event_name', 'event_date', 'category'])
        ->condition('r.event_id', $event_id)
        ->orderBy('r.created', 'DESC')
        ->execute()
        ->fetchAll();
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching registrations: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Gets the count of registrations for a specific event.
   *
   * @param int $event_id
   *   The event ID.
   *
   * @return int
   *   The count of registrations.
   */
  public function getRegistrationCountForEvent($event_id) {
    try {
      return $this->database->select('event_registration_registrations', 'r')
        ->condition('event_id', $event_id)
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      $this->logger->error('Error counting registrations: @message', [
        '@message' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Gets all registrations for CSV export.
   *
   * @param int|null $event_id
   *   Optional event ID to filter by.
   *
   * @return array
   *   An array of registration objects.
   */
  public function getAllRegistrations($event_id = NULL) {
    try {
      $query = $this->database->select('event_registration_registrations', 'r');
      $query->join('event_registration_events', 'e', 'r.event_id = e.id');
      $query->fields('r', ['id', 'full_name', 'email', 'college_name', 'department', 'category', 'created']);
      $query->fields('e', ['event_name', 'event_date']);

      if ($event_id) {
        $query->condition('r.event_id', $event_id);
      }

      return $query->orderBy('r.created', 'DESC')
        ->execute()
        ->fetchAll();
    }
    catch (\Exception $e) {
      $this->logger->error('Error fetching registrations: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
