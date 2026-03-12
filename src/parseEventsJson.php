<?php

namespace Drupal\as_events;

use Drupal\as_events\Service\EventsApiService;
use Drupal\as_events\Service\EventsFormatterService;

/**
 * Extends Drupal's Twig_Extension class.
 */
class parseEventsJson extends \Twig\Extension\AbstractExtension {

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
   * Constructs a parseEventsJson object.
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
   * Let Drupal know the name of custom extension.
   */
  public function getName() {
    return 'as_events.parse.json';
  }

  /**
   * {@inheritdoc}
   * Return custom twig function to Drupal.
   */
  public function getFunctions() {
    return [
      new \Twig\TwigFunction('parse_events_json', [$this, 'parse_events_json']),
    ];
  }

  /**
   * Parses events JSON data into array for theming.
   *
   * @param int $events_shown
   *   Number of events to display.
   * @param string $keyword_params
   *   Keyword parameters for filtering events.
   *
   * @return array
   *   Data in array for theming.
   */
  public function parse_events_json($events_shown, $keyword_params) {
    $event_record = [];
    $event_count = 0;
    $events_json = $this->eventsApi->getEventsJson($events_shown, $keyword_params);
    if (!empty($events_json)) {
      foreach ($events_json as $event_data) {
        if ($event_count <= $events_shown) {
          $date = date_create($event_data['event']['event_instances'][0]['event_instance']['start']);
          // if no end date set end date to $date +1 hour.
          if ($event_data['event']['event_instances'][0]['event_instance']['end'] !== null) {
            $end_date = date_create($event_data['event']['event_instances'][0]['event_instance']['end']);
          } else {
            $end_date = (clone $date)->modify('+1 hour');
          }
          // Custom fields null handlers.
          $contact_email = !empty($event_data['event']['custom_fields']['contact_email'])
            ? $event_data['event']['custom_fields']['contact_email']
            : 'NULL';
          $contact_name = !empty($event_data['event']['custom_fields']['contact_name'])
            ? $event_data['event']['custom_fields']['contact_name']
            : 'NULL';
          $contact_phone = !empty($event_data['event']['custom_fields']['contact_phone'])
            ? $event_data['event']['custom_fields']['contact_phone']
            : 'NULL';
          $speaker = !empty($event_data['event']['custom_fields']['speaker'])
            ? $event_data['event']['custom_fields']['speaker']
            : 'NULL';
          $speaker_affiliation = !empty($event_data['event']['custom_fields']['speaker_affiliation'])
            ? $event_data['event']['custom_fields']['speaker_affiliation']
            : 'NULL';
          $dept_web_site = !empty($event_data['event']['custom_fields']['dept_web_site'])
            ? $event_data['event']['custom_fields']['dept_web_site']
            : 'NULL';
          $open_to = !empty($event_data['event']['custom_fields']['open_to'])
            ? $event_data['event']['custom_fields']['open_to']
            : 'NULL';

          $event_record[] = [
            'title' => $event_data['event']['title'],
            'url' => $event_data['event']['localist_url'],
            'location' => $event_data['event']['location_name'],
            'room' => $event_data['event']['room_number'],
            'status' => $event_data['event']['status'],
            'description' => $event_data['event']['description'],
            'image' => $event_data['event']['photo_url'],
            'experience' => $event_data['event']['experience'],
            'stream_url' => $event_data['event']['stream_url'],
            'stream_info' => $event_data['event']['stream_info'],
            'iso_date' => date_format($date, "c"),
            'iso_end_date' => date_format($end_date, "c"),
            'month' => date_format($date, "M"),
            'date' => date_format($date, "d"),
            'time' => date_format($date, "h:i A"),
            'day' => date_format($date, "l"),
            'all_day' => $event_data['event']['event_instances'][0]['event_instance']['all_day'],
            'street' => $event_data['event']['geo']['street'],
            'state' => $event_data['event']['geo']['state'],
            'zip' => $event_data['event']['geo']['zip'],
            'contact_email' => $contact_email,
            'contact_name' => $contact_name,
            'contact_phone' => $contact_phone,
            'speaker' => $speaker,
            'speaker_affiliation' => $speaker_affiliation,
            'dept_web_site' => $dept_web_site,
            'open_to' => $open_to,
            'localist_ics_url' => $event_data['event']['localist_ics_url'],
          ];

          $event_count++;
        }
      }
    }
    return $event_record;
  }

}

