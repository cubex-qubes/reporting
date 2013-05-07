<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Worker;

use Cubex\Cli\CliCommand;
use Cubex\Cli\Shell;
use Cubex\Facade\Queue;
use Cubex\Figlet\Figlet;
use Cubex\Foundation\Config\Config;
use Cubex\Log\Log;
use Cubex\Queue\IQueueProvider;
use Cubex\Queue\StdQueue;
use Qubes\Reporting\Helpers\ReportQueueHelper;
use Qubes\Reporting\Mappers\Report;
use Qubes\Reporting\Mappers\ReportQueue;
use Qubes\Reporting\Queues\RawConsumer;
use Qubes\Reporting\Queues\ReportConsumer;

/**
 * Read report queue and process report
 */
class ReportWorker extends CliCommand
{
  /**
   * Report Queue ID
   * @required
   * @valuerequired
   */
  public $reportQueueId;

  /**
   * Amount of time to wait before reading from queue after empty queue hit
   * @valuerequired
   */
  public $queueDelay = 0;

  protected $_echoLevel = 'debug';

  public function execute()
  {
    echo Shell::colourText(
      (new Figlet("speed"))->render("Report Builder"),
      Shell::COLOUR_FOREGROUND_GREEN
    );
    echo "\n";

    $reportQueue = new ReportQueue($this->reportQueueId);
    if(!$reportQueue->exists())
    {
      throw new \Exception(
        "The report queue you are trying to load does not exist"
      );
    }

    echo Shell::colourText(
      $reportQueue->name,
      Shell::COLOUR_FOREGROUND_PURPLE
    );
    echo "\n\n\n";

    Log::info("Starting Worker");

    try
    {
      $report   = new Report($reportQueue->reportId);
      $consumer = new ReportConsumer($report->buildReportClass());
      $consumer->setWaitTime($this->queueDelay);

      $queueProvider = ReportQueueHelper::buildQueueProvider($reportQueue);

      Log::info("Starting to consume queue " . $reportQueue->queueName);

      $queueProvider->consume(
        new StdQueue($reportQueue->queueName),
        $consumer
      );
    }
    catch(\Exception $e)
    {
      Log::error($e->getMessage());
    }

    Log::info("Exiting Report Worker");
  }
}
