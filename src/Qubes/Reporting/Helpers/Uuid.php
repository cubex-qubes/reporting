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
    $uuid = strtoupper($eventType) . '-' . $time . '-';
    $uuid .= FileSystem::readRandomCharacters(20);
    return $uuid;
  }
}
