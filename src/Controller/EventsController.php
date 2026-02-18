<?php

namespace Drupal\as_events\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\as_events\Service\EventsApiService;
use Drupal\as_events\Service\EventsFormatterService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for displaying events from Localist.
 */
class EventsController extends ControllerBase {

  /**
   * The events API service.
   *
   * @var \Drupal\as_events\Service\EventsApiService
   */
  protected $eventsApi;

  /**
   * The events formatter service.
   *
   * @var \Drupal\as_events\Service\EventsFormatterService
   */
  protected $eventsFormatter;

  /**
   * Constructs an EventsController object.
   *
   * @param \Drupal\as_events\Service\EventsApiService $events_api
   *   The events API service.
   * @param \Drupal\as_events\Service\EventsFormatterService $events_formatter
   *   The events formatter service.
   */
  public function __construct(EventsApiService $events_api, EventsFormatterService $events_formatter) {
    $this->eventsApi = $events_api;
    $this->eventsFormatter = $events_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('as_events.api'),
      $container->get('as_events.formatter')
    );
  }

  /**
   * Display the markup.
   *
   * @param int $events_shown
   *   Number of events to display.
   * @param string $keyword_params
   *   Keyword parameters for filtering events.
   *
   * @return array
   *   Render array.
   */
  public function content($events_shown, $keyword_params) {
    $main = "";
    $event_count = 0;
    $events_json = $this->eventsApi->getEventsJson($events_shown, $keyword_params);

    if (!empty($events_json)) {
      foreach ($events_json as $event_data) {
        if ($event_count <= $events_shown) {
          $main .= $this->eventsFormatter->generateEventItemMarkup($event_data);
          $event_count++;
        }
      }
    }
    // There were no events.
    else {
      $main = "<main>
                <h1>Calendar</h1>
                <p>There are no Upcoming Events.</p>
                </main>";
    }

    return [
      '#type' => 'markup',
      '#markup' => $this->t('<h1>Calendar</h1><div class="slides">
<article class="slide-aside">@main</article></div>', ['@main' => $main]),
    ];
  }

}

