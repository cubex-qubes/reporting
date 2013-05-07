<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Reports;

use Qubes\Reporting\IReport;
use Qubes\Reporting\IReportEvent;

abstract class BaseReport implements IReport
{
  /**
   * @var IReportEvent
   */
  protected $_event;

  abstract public function processEvent();

  public function setEvent(IReportEvent $event)
  {
    $this->_event = $event;
    return $this;
  }

  public function getReportColumns()
  {
    $columns = [];
    $class   = new \ReflectionClass(get_class($this));
    foreach($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $p)
    {
      $columns[] = $p->getName();
    }
    return $columns;
  }
}
