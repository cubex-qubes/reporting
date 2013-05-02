<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Worker;

use Cubex\Cli\CliCommand;
use Cubex\Container\Container;
use Cubex\Events\EventManager;
use Cubex\Events\IEvent;
use Cubex\Log\Log;
use Cubex\Text\TextTable;
use Qubes\Reporting\Enums\ReportType;
use Qubes\Reporting\Mappers\RawEvent;
use Qubes\Reporting\Mappers\Report;
use Qubes\Reporting\Mappers\ReportEventHook;
use Qubes\Reporting\Mappers\ReportQueue;

class Test extends CliCommand
{
  protected $_echoLevel = 'debug';

  public function execute()
  {
    $this->_help();
  }

  public function initiate()
  {
    EventManager::listen(
      EventManager::CUBEX_QUERY,
      function (IEvent $e)
      {
        var_dump($e->getStr("query"));
      }
    );

    Log::debug("Creating raw event for user.join");
    $event              = new RawEvent('user.join');
    $event->name        = "User Signup";
    $event->description = "When a user joins the system";
    $event->reference   = 'user.join';
    $event->saveChanges();

    Log::debug("Creating user join report");
    $report              = new Report(1);
    $report->name        = "User Join Report";
    $report->description = "User Joins";
    $report->type        = ReportType::TIMESERIES();
    $report->class       = '\Qubes\Reporting\Reports\JoinReport';
    $report->saveChanges();

    Log::debug("Creating user join event/report relation");
    ReportEventHook::create($event, $report);

    $qbexQueues = '\Cubex\Queue\Provider';

    Log::debug("Creating report queue for joins");
    $reportQueue                = new ReportQueue(1);
    $reportQueue->name          = 'Join DB Queue';
    $reportQueue->reportId      = $report->id();
    $reportQueue->queueProvider = $qbexQueues . '\Database\DatabaseQueue';
    $reportQueue->configuration = ['queue_name' => 'joinqueue'];
    $reportQueue->saveChanges();

    Log::debug("Creating failover report queue for joins");
    $reportQueue                = new ReportQueue(2);
    $reportQueue->name          = 'Join DB Failover Queue';
    $reportQueue->reportId      = $report->id();
    $reportQueue->queueProvider = $qbexQueues . '\Database\DatabaseQueue';
    $reportQueue->configuration = ['queue_name' => 'failover'];
    $reportQueue->saveChanges();

    Log::info("Added all test data");
  }

  public function testQueue()
  {
    $report = new Report(1);
    echo TextTable::fromArray($report->queues());
  }
}
