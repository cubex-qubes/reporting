<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Helpers;

use Cubex\FileSystem\FileSystem;

class Uuid
{
  public static function namedTimeUuid($eventType, $time = null)
  {
    if($time === null)
    {
      $time = microtime(true);
    }
    $uuid = $eventType . '-' . $time . '-';
    $uuid .= FileSystem::readRandomCharacters(20);
    return $uuid;
  }

  public static function timeFromUuid($uuid)
  {
    list(, $time,) = explode('-', $uuid, 3);
    return $time;
  }

  public static function eventTypeFromUuid($uuid)
  {
    list($type,) = explode('-', $uuid, 2);
    return $type;
  }
}
