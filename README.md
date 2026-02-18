[![Latest Stable Version](https://poser.pugx.org/as-cornell/as_events/v)](https://packagist.org/packages/as-cornell/as_events)

# AS Events (as_events)

Provides events integration with Cornell Localist API for Drupal 10 sites.

## Table of Contents

- [Introduction](#introduction)
- [Architecture](#architecture)
- [Services](#services)
- [Usage](#usage)
- [Twig Functions](#twig-functions)
- [Blocks](#blocks)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Maintainers](#maintainers)

## Introduction

The AS Events module fetches and displays upcoming events from the Cornell Localist API. It provides:

- **Service-based architecture** with dependency injection
- **Automatic caching** (2-hour cache TTL)
- **Twig function** for parsing event data in templates
- **Block plugin** for displaying event lists
- **Controller** for custom event pages
- **Flexible formatting** utilities for event data

## Architecture

The module uses an **object-oriented architecture** with separate service classes:

```
as_events/
├── src/
│   ├── Service/
│   │   ├── EventsApiService.php       # API calls and caching
│   │   └── EventsFormatterService.php # Data formatting utilities
│   ├── Controller/
│   │   └── EventsController.php       # Route controller
│   ├── Plugin/Block/
│   │   └── ASEvents.php               # Events block
│   └── parseEventsJson.php            # Twig extension
├── as_events.services.yml             # Service definitions
└── as_events.module                   # Hooks only
```

**Key Benefits:**
- ✅ Testable service classes
- ✅ Reusable via dependency injection
- ✅ Clear separation of concerns
- ✅ Follows Drupal 10 best practices

## Services

### EventsApiService

Handles API communication with Cornell Localist and manages caching.

**Service ID:** `as_events.api`

**Dependencies:**
- `@cache.data` - Cache backend
- `@logger.channel.as_events` - Logger channel

**Methods:**

#### `getEventsJson($events_shown, $keyword_params)`

Fetches events from Cornell Localist API with automatic caching.

**Parameters:**
- `$events_shown` (int) - Number of events to retrieve
- `$keyword_params` (string) - Keyword filter for events (e.g., "casfeatured")

**Returns:** `array|null` - Array of event data or NULL if no events found

**Example:**
```php
$events_api = \Drupal::service('as_events.api');
$events = $events_api->getEventsJson(5, 'casfeatured');
```

**Caching:**
- Cache ID: `as_events:{count}:{keyword}`
- Cache Duration: 2 hours (7200 seconds)
- Cache Bin: `cache.data`

### EventsFormatterService

Provides utilities for formatting event data.

**Service ID:** `as_events.formatter`

**Methods:**

#### `nameize($str, array $a_char = ["'", "-", " "])`

Converts a string to name format with proper capitalization.

**Parameters:**
- `$str` (string) - String to format
- `$a_char` (array) - Characters to use as separators for capitalization

**Returns:** `string` - Formatted string

**Example:**
```php
$formatter = \Drupal::service('as_events.formatter');
$name = $formatter->nameize("o'connor"); // Returns: "O'Connor"
```

#### `formatEventDates($startdate, $enddate)`

Formats event start and end dates into components.

**Parameters:**
- `$startdate` (\DateTime) - Event start date
- `$enddate` (\DateTime|null) - Event end date (optional)

**Returns:** `array` - Array with formatted date components:
```php
[
  'start_month' => 'Jan',
  'start_date' => '15',
  'start_time' => '2:30',
  'end_month' => 'Jan',    // If enddate provided
  'end_date' => '15',      // If enddate provided
  'end_time' => '4:00',    // If enddate provided
]
```

**Example:**
```php
$formatter = \Drupal::service('as_events.formatter');
$start = date_create('2025-01-15 14:30:00');
$end = date_create('2025-01-15 16:00:00');
$formatted = $formatter->formatEventDates($start, $end);
```

#### `generateEventItemMarkup(array $event_data)`

Generates HTML markup for a single event item.

**Parameters:**
- `$event_data` (array) - Event data from Localist API

**Returns:** `string` - HTML markup with schema.org microdata

**Example:**
```php
$formatter = \Drupal::service('as_events.formatter');
$markup = $formatter->generateEventItemMarkup($event_data);
```

**Generated Markup Includes:**
- Event title with link
- Date and time
- Location and room
- Schema.org Event markup

## Usage

### Using Services in Custom Code

**In a Controller:**
```php
namespace Drupal\my_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\as_events\Service\EventsApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MyController extends ControllerBase {

  protected $eventsApi;

  public function __construct(EventsApiService $events_api) {
    $this->eventsApi = $events_api;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('as_events.api')
    );
  }

  public function myPage() {
    $events = $this->eventsApi->getEventsJson(10, 'chemistry');
    // Process events...
  }
}
```

**In a Service:**
```php
# my_module.services.yml
services:
  my_module.my_service:
    class: Drupal\my_module\MyService
    arguments: ['@as_events.api', '@as_events.formatter']
```

```php
namespace Drupal\my_module;

use Drupal\as_events\Service\EventsApiService;
use Drupal\as_events\Service\EventsFormatterService;

class MyService {

  public function __construct(
    EventsApiService $events_api,
    EventsFormatterService $events_formatter
  ) {
    $this->eventsApi = $events_api;
    $this->eventsFormatter = $events_formatter;
  }
}
```

## Twig Functions

### `parse_events_json()`

Parses events JSON into a structured array for theming.

**Syntax:**
```twig
{% set events = parse_events_json(events_shown, keyword_params) %}
```

**Parameters:**
- `events_shown` (int) - Number of events to display
- `keyword_params` (string) - Keyword filter (e.g., "casfeatured", "chemistry")

**Returns:** `array` - Array of event records with the following structure:
```php
[
  [
    'title' => 'Event Title',
    'url' => 'https://cornell.localist.com/event/...',
    'location' => 'Rockefeller Hall',
    'room' => 'Room 101',
    'status' => 'published',
    'description' => 'Event description HTML',
    'image' => 'https://...',
    'experience' => 'in_person',
    'stream_url' => 'https://...',
    'stream_info' => 'Zoom link info',
    'month' => 'Jan',
    'date' => '15',
    'time' => '02:30 PM',
    'day' => 'Monday',
    'all_day' => false,
    'street' => '123 Main St',
    'state' => 'NY',
    'zip' => '14850',
    'contact_email' => 'contact@cornell.edu',
    'contact_name' => 'John Doe',
    'contact_phone' => '555-1234',
    'speaker' => 'Dr. Jane Smith',
    'speaker_affiliation' => 'Cornell University',
    'dept_web_site' => 'https://...',
    'open_to' => 'Public',
    'localist_ics_url' => 'https://...',
  ],
  // ... more events
]
```

**Example Usage in Twig:**
```twig
{# Get 5 featured events #}
{% set events = parse_events_json(5, 'casfeatured') %}

{# Loop through events #}
{% if events %}
  <div class="events-list">
    {% for event in events %}
      <article class="event">
        <h3><a href="{{ event.url }}">{{ event.title }}</a></h3>
        <time datetime="{{ event.date }}">
          {{ event.month }} {{ event.date }}, {{ event.time }}
        </time>
        {% if event.location %}
          <div class="location">{{ event.location }}</div>
        {% endif %}
        <div class="description">{{ event.description|raw }}</div>
      </article>
    {% endfor %}
  </div>
{% else %}
  <p>No upcoming events.</p>
{% endif %}
```

## Blocks

### Events Block

**Block ID:** `events_block`
**Admin Label:** "Events Block"
**Category:** "Upcoming Events"

Displays a configurable list of upcoming events from Localist.

**Configuration:**
- `events_shown` - Number of events to display (default: 0)
- `keyword_params` - Keyword filter (default: "casfeatured")

**Usage:**
1. Navigate to `/admin/structure/block`
2. Click "Place block"
3. Search for "Events Block"
4. Configure number of events and keywords
5. Save

## Requirements

- Drupal: >= 10.0
- PHP: >= 8.1
- Access to Cornell Localist API (http://cornell.localist.com)

**Drupal Modules:**
- No additional module dependencies

## Installation

### Via Composer (Recommended)

```bash
composer require as-cornell/as_events
drush en as_events -y
drush cr
```

### Manual Installation

1. Download the module to `/modules/custom/as_events`
2. Enable the module: `drush en as_events -y`
3. Clear cache: `drush cr`

## Configuration

### API Endpoint

The module connects to: `http://cornell.localist.com/api/2/events`

**Default Query Parameters:**
- `days=364` - Events within the next year
- `pp={count}` - Number of events per page
- `keyword={keyword}` - Keyword filter

### Cache Settings

Events are cached for **2 hours** in the `cache.data` bin.

**To clear events cache:**
```bash
# Clear all cache
drush cr

# Or manually clear specific cache IDs
drush php-eval "\Drupal::cache('data')->deleteAll();"
```

### Logging

The module provides its own logger channel: `logger.channel.as_events`

**View logs:**
```bash
drush watchdog:show --type=as_events
```

## API Reference

### Cornell Localist API

**Documentation:** https://developer.localist.com/doc/api

**Event Data Structure:**
```json
{
  "events": [
    {
      "event": {
        "title": "Event Title",
        "localist_url": "https://...",
        "location_name": "Building Name",
        "room_number": "Room 101",
        "description": "<p>Event description</p>",
        "photo_url": "https://...",
        "event_instances": [
          {
            "event_instance": {
              "start": "2025-01-15T14:30:00Z",
              "end": "2025-01-15T16:00:00Z",
              "all_day": false
            }
          }
        ],
        "custom_fields": {
          "contact_email": "email@cornell.edu",
          "speaker": "Dr. Jane Doe",
          ...
        }
      }
    }
  ]
}
```

## Troubleshooting

### Events Not Displaying

1. **Check API connection:**
   ```bash
   curl "http://cornell.localist.com/api/2/events?days=364&pp=5&keyword=casfeatured"
   ```

2. **Clear cache:**
   ```bash
   drush cr
   ```

3. **Check logs:**
   ```bash
   drush watchdog:show --type=as_events --severity=Warning
   ```

### Performance Issues

- Events are cached for 2 hours by default
- Reduce `events_shown` count if pages load slowly
- Check network latency to Localist API

### Debugging

**Enable verbose logging:**
```php
// In settings.local.php
$config['system.logging']['error_level'] = 'verbose';
```

**Test API service directly:**
```bash
drush php-eval "
\$api = \Drupal::service('as_events.api');
\$events = \$api->getEventsJson(5, 'casfeatured');
print_r(\$events);
"
```

## Development

### Running Tests

```bash
# PHPUnit tests (if implemented)
vendor/bin/phpunit modules/custom/as_events

# Code standards
vendor/bin/phpcs --standard=Drupal modules/custom/as_events
```

### Contributing

1. Follow Drupal coding standards
2. Add PHPUnit tests for new functionality
3. Update this README for new features
4. Use semantic versioning for releases

## Maintainers

Current maintainers for Drupal 10:

- Mark Wilson (markewilson)

## License

GPL-2.0-or-later

## Changelog

### 2.0.0 (2025-02-18)
- Refactored to OOP architecture with service classes
- Added dependency injection throughout
- Improved caching and logging
- Enhanced documentation

### 1.x
- Initial procedural implementation
- Basic Localist API integration
