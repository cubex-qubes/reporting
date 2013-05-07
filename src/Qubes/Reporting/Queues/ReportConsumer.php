<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Queues;

use Cubex\Log\Log;
use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;
use Qubes\Reporting\IReport;
use Qubes\Reporting\StdReportEvent;

class ReportConsumer implements IQueueConsumer
{
  protected $_report;
  protected $_waitTime;

  public function setWaitTime($waitTime)
  {
    $this->_waitTime = $waitTime;
    return $this;
  }

  public function __construct(IReport $report)
  {
    $this->_report = $report;
  }

  /**
   * @param $queue
   * @param $data
   *
   * @return bool
   */
  public function process(IQueue $queue, $data)
  {
    $event = StdReportEvent::rebuildFromQueueData($data);
    Log::debug("Received Event: " . $event->getUuid());
    $this->_report->setEvent($event);
    $this->_report->processEvent();
    return true;
  }

  /**
   * Seconds to wait before re-attempting, false to exit
   *
   * @param int $waits amount of times script has waited
   *
   * @return mixed
   */
  public function waitTime($waits = 0)
  {
    return (int)$this->_waitTime;
  }

  public function shutdown()
  {
    return true;
  }
}
