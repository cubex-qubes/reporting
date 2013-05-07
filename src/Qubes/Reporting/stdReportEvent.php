<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting;

use Cubex\Events\StdEvent;
use Qubes\Reporting\Helpers\Uuid;

class StdReportEvent extends StdEvent implements IReportEvent
{
  protected $_uuid;

  public function setEventTime($time)
  {
    $this->_eventTime = $time;
    return $this;
  }

  public function getUuid()
  {
    return $this->_uuid;
  }

  public function setUuid($uuid)
  {
    $this->_uuid = $uuid;
    return $this;
  }

  public function jsonSerialize()
  {
    return $this->_data;
  }

  public static function rebuildFromQueueData($data)
  {
    if(is_scalar($data))
    {
      $data = json_decode($data);
    }
    return self::rebuildEvent($data->uuid, $data->data);
  }

  public static function rebuildEvent($uuid, $data)
  {
    $name  = Uuid::eventTypeFromUuid($uuid);
    $time  = Uuid::timeFromUuid($uuid);
    $event = new StdReportEvent($name, (array)$data);
    $event->setEventTime($time);
    $event->setUuid($uuid);
    return $event;
  }
}
