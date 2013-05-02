<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting;

use Cubex\Events\IEvent;

interface IReportEvent extends IEvent
{
  public function getUuid();

  public function setUuid($uuid);

  public function setEventTime($time);
}
