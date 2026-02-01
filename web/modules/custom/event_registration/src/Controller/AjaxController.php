<?php

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\event_registration\Service\EventRegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for AJAX callbacks in registration form.
 */
class AjaxController extends ControllerBase {

  /**
   * The event registration service.
   *
   * @var \Drupal\event_registration\Service\EventRegistrationService
   */
  protected $registrationService;

  /**
   * Constructs an AjaxController object.
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
   * Returns event dates for a category.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function getEventDates(Request $request) {
    $category = $request->query->get('category', '');

    $dates = [];
    if (!empty($category)) {
      $dates = $this->registrationService->getAvailableEventDates($category);
    }

    return new JsonResponse($dates);
  }

  /**
   * Returns event names for a category and date.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function getEventNames(Request $request) {
    $category = $request->query->get('category', '');
    $date = $request->query->get('date', '');

    $events = [];
    if (!empty($category) && !empty($date)) {
      $events = $this->registrationService->getAvailableEventNames($category, $date);
    }

    return new JsonResponse($events);
  }

}
