<?php

namespace Drupal\as_events\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for fetching events from Localist API.
 *
 * @package Drupal\as_events\Service
 */
class EventsApiService {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs an EventsApiService object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(CacheBackendInterface $cache, LoggerInterface $logger) {
    $this->cache = $cache;
    $this->logger = $logger;
  }

  /**
   * Gets events JSON data from Localist API with caching.
   *
   * @param int $events_shown
   *   Number of events to retrieve.
   * @param string $keyword_params
   *   Keyword parameters for filtering events.
   *
   * @return array|null
   *   Array of event data or NULL if no events found.
   */
  public function getEventsJson($events_shown, $keyword_params) {
    // Set cache id (verify parameters are safe values to use as cache id).
    $cid = "as_events:" . $events_shown . ":" . $keyword_params;
    $event_json = NULL;
    $json = NULL;

    // Check cache.
    if ($cache = $this->cache->get($cid)) {
      // Fetch cache data.
      $event_json = $cache->data;
    }
    // If no cache data fetch remote data.
    else {
      $events_shown = $events_shown + 1;
      $url = "http://cornell.localist.com/api/2/events?days=364&pp=" . $events_shown . "&keyword=" . $keyword_params;

      // Create the stream context.
      $context = stream_context_create([
        'http' => [
          'timeout' => 2,   // Timeout in seconds.
        ],
      ]);

      // Get file contents.
      if (($data = @file_get_contents($url, 0, $context)) === FALSE) {
        $this->logger->warning('No events found on http://cornell.localist.com for events shown: @events_shown and keyword: @keyword_params', [
          '@events_shown' => $events_shown,
          '@keyword_params' => $keyword_params,
        ]);
        return NULL;
      }
      else {
        $data = @file_get_contents($url, 0, $context);
      }

      if (!empty($data)) {
        $json = json_decode($data, TRUE);
      }

      if (!empty($json)) {
        $event_json = $json['events'];
        // Set cache, invalidate after 2 hours.
        $this->cache->set($cid, $event_json, time() + 7200);
      }
    }

    // Return data.
    return $event_json;
  }

}
