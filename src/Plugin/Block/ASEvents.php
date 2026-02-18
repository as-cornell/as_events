<?php

namespace Drupal\as_events\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\as_events\Service\EventsApiService;
use Drupal\as_events\Service\EventsFormatterService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Current Events Block.
 *
 * @Block(
 *   id = "events_block",
 *   admin_label = @Translation("Events Block"),
 *   category = @Translation("Upcoming Events"),
 * )
 */
class ASEvents extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

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
   * Constructs an ASEvents block object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\as_events\Service\EventsApiService $events_api
   *   The events API service.
   * @param \Drupal\as_events\Service\EventsFormatterService $events_formatter
   *   The events formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventsApiService $events_api, EventsFormatterService $events_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eventsApi = $events_api;
    $this->eventsFormatter = $events_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('as_events.api'),
      $container->get('as_events.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    if (!empty($config['events_shown'])) {
      // 1 shows 2, 2 shows 3 etc. so subtract 1.
      $events_shown = $config['events_shown'] - 1;
    }
    else {
      $events_shown = 0;
    }

    if (!empty($config['keyword_params'])) {
      $keyword_params = $config['keyword_params'];
    }
    else {
      $keyword_params = "casfeatured";
    }

    $build = [];
    $build['events_block']['#markup'] = "";
    $event_count = 0;
    $event_json = $this->eventsApi->getEventsJson($events_shown, $keyword_params);

    if (!empty($event_json)) {
      foreach ($event_json as $event_data) {
        if ($event_count <= $events_shown) {
          $build['events_block']['#markup'] .= $this->eventsFormatter->generateEventItemMarkup($event_data);
          $event_count++;
        }
      }
    }
    // There were no events.
    else {
      $build['events_block']['#markup'] = "<main>
                <h1>Events Calendar</h1>
                <p>There are no upcoming events</p>
                </main>";
    }

    return $build;
  }

}

