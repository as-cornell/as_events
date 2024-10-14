<?php

namespace Drupal\as_events;

/**
 * extend Drupal's Twig_Extension class
 */
class parseEventsJson extends \Twig\Extension\AbstractExtension
{

  /**
   * {@inheritdoc}
   * Let Drupal know the name of custom extension
   */
  public function getName()
  {
    return 'as_events.parse.json';
  }


  /**
   * {@inheritdoc}
   * Return custom twig function to Drupal
   */
  public function getFunctions()
  {
    return [
      new \Twig\TwigFunction('parse_events_json', [$this, 'parse_events_json']),
    ];
  }

  /**
   * Parses events JSON data into array for theming
   *
   *
   * @return array $event_record
   *   data in array for theming
   */
  public function parse_events_json($events_shown,$keyword_params)
  {
    $event_record = [];
    $event_count = 0;
    $events_json = as_events_get_events_json($events_shown,$keyword_params);
    if (!empty($events_json)) {

      foreach ($events_json as $event_data) {
        //convert from real number to 0 base
        //$events_shown = $events_shown - 1;
        if ($event_count <= $events_shown) {
          //dump($event_data);
          $date = date_create($event_data['event']['event_instances'][0]['event_instance']['start']);
          // custom fields null handlers
          if (!empty($event_data['event']['custom_fields']['contact_email'])) {
            $contact_email = $event_data['event']['custom_fields']['contact_email'];
          } else {
            $contact_email = 'NULL';
          }
          if (!empty($event_data['event']['custom_fields']['contact_name'])) {
            $contact_name = $event_data['event']['custom_fields']['contact_name'];
          } else {
            $contact_name = 'NULL';
          }
          if (!empty($event_data['event']['custom_fields']['contact_phone'])) {
            $contact_phone = $event_data['event']['custom_fields']['contact_phone'];
          } else {
            $contact_phone = 'NULL';
          }
          if (!empty($event_data['event']['custom_fields']['speaker'])) {
            $speaker = $event_data['event']['custom_fields']['speaker'];
          } else {
            $speaker = 'NULL';
          }
          if (!empty($event_data['event']['custom_fields']['speaker_affiliation'])) {
            $speaker_affiliation = $event_data['event']['custom_fields']['speaker_affiliation'];
          } else {
            $speaker_affiliation = 'NULL';
          }
          if (!empty($event_data['event']['custom_fields']['dept_web_site'])) {
            $dept_web_site = $event_data['event']['custom_fields']['dept_web_site'];
          } else {
            $dept_web_site = 'NULL';
          }
          if (!empty($event_data['event']['custom_fields']['open_to'])) {
            $open_to = $event_data['event']['custom_fields']['open_to'];
          } else {
            $open_to = 'NULL';
          }
          
          $event_record[] = array(
            'title' => $event_data['event']['title'], 
            'url' => $event_data['event']['localist_url'], 
            'location' => $event_data['event']['location_name'], 
            'room' => $event_data['event']['room_number'],
            'status' => $event_data['event']['status'],  
            // 'description' => strip_tags($event_data['event']['description']),
            'description' => $event_data['event']['description'],
            'image' => $event_data['event']['photo_url'], 
            'experience' => $event_data['event']['experience'], 
            'stream_url' => $event_data['event']['stream_url'],  
            'stream_info' => $event_data['event']['stream_info'], 
            'month' => date_format($date,"M"), 
            'date' => date_format($date,"d"),
            'time' => date_format($date,"h:i A"),
            'day' => date_format($date,"l"),
            'all_day' => $event_data['event']['event_instances'][0]['event_instance']['all_day'],
            'street' => $event_data['event']['geo']['street'],
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
            'localist_ics_url' => $event_data['event']['localist_ics_url']
          );

          $event_count++;
        }
      }
    }
    return $event_record;
  }
}
