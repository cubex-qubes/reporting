<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting;

use Cubex\Events\StdEvent;

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
    return [
      'name'   => $this->name(),
      'source' => $this->source(),
      'time'   => $this->eventTime(),
      'data'   => $this->_data,
      'uuid'   => $this->_uuid,
    ];
  }
}
