<?php

namespace Drupal\as_events\Service;

/**
 * Service for formatting event data.
 *
 * @package Drupal\as_events\Service
 */
class EventsFormatterService {

  /**
   * Converts a string to name format with proper capitalization.
   *
   * @param string $str
   *   The string to format.
   * @param array $a_char
   *   Array of characters to use as separators for capitalization.
   *
   * @return string
   *   The formatted string.
   */
  public function nameize($str, array $a_char = ["'", "-", " "]) {
    // Adapted from http://php.net/manual/en/function.ucfirst.php
    // $str contains the complete raw name string.
    // $a_char is an array containing the characters we use as separators for
    // capitalization. If you don't pass anything, there are three in there
    // as default.
    $string = strtolower($str);
    foreach ($a_char as $temp) {
      $pos = strpos($string, $temp);
      if ($pos) {
        // We are in the loop because we found one of the special characters
        // in the array, so lets split it up into chunks and capitalize each
        // one.
        $mend = '';
        $a_split = explode($temp, $string);
        foreach ($a_split as $temp2) {
          // Capitalize each portion of the string which was separated at a
          // special character.
          $mend .= ucfirst($temp2) . $temp;
        }
        $string = substr($mend, 0, -1);
      }
    }
    return ucfirst($string);
  }

  /**
   * Formats event start and end dates.
   *
   * @param \DateTime $startdate
   *   The event start date.
   * @param \DateTime|null $enddate
   *   The event end date (optional).
   *
   * @return array
   *   Array containing formatted date components.
   */
  public function formatEventDates($startdate, $enddate) {
    $event_formatted_date['start_month'] = date_format($startdate, "M");
    $event_formatted_date['start_date'] = date_format($startdate, "d");
    $event_formatted_date['start_time'] = date_format($startdate, "g:i A");

    if ($enddate) {
      $event_formatted_date['end_month'] = date_format($enddate, "M");
      $event_formatted_date['end_date'] = date_format($enddate, "d");
      $event_formatted_date['end_time'] = date_format($enddate, "g:i A");
    }

    return $event_formatted_date;
  }

  /**
   * Generates HTML markup for a single event item.
   *
   * @param array $event_data
   *   The event data array from Localist.
   *
   * @return string
   *   HTML markup for the event.
   */
  public function generateEventItemMarkup(array $event_data) {
    // Deal with localist's funky json structure.
    $event = $event_data['event'];
    $event_title = $event['title'];
    $event_url = $event['localist_url'];
    $event_location = $event['location_name'];
    $event_room = $event['room_number'];

    // Dates and times.
    $event_start_date = date_create($event['event_instances'][0]['event_instance']['start']);
    if (!empty($event['event_instances'][0]['event_instance']['end'])) {
      $event_end_date = date_create($event['event_instances'][0]['event_instance']['end']);
    }
    else {
      $event_end_date = NULL;
    }

    $event_formatted_date = $this->formatEventDates($event_start_date, $event_end_date);
    $event_description = strip_tags($event['description']);
    $event_image = $event['photo_url'];

    // Create the markup.
    $markup = "<span class='eventList__event event'  itemscope itemtype='http://schema.org/Event'>
                <span class='event__dateTime'  itemprop='startDate'>
                  <span class='event__date'><span class='event__month'>" . $event_formatted_date['start_month'] . "</span> <span class='event__day'> " . $event_formatted_date['start_date'] . "</span></span>
                  <span class='event__time'>" . $event_formatted_date['start_time'] . "</span>
                </span>
                <span class='event__content'>
                <span class='event__title'>
                  <a href='" . $event_url . "' itemprop='url'>" . $event_title . "</a>
                </span>";

    if (!empty($event_location)) {
      $markup .= "<span class='event__location'  itemprop='location'>" . $event_location;
      if (!empty($event_room)) {
        $markup .= ": " . $event_room;
      }
      $markup .= "</span>\n";
    }

    $markup .= "</span></span>";

    return $markup;
  }

}
