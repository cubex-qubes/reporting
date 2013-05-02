<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Worker;

use Cubex\Cli\CliCommand;
use Cubex\Cli\Shell;
use Cubex\Facade\Queue;
use Cubex\Figlet\Figlet;
use Cubex\Log\Log;
use Cubex\Queue\StdQueue;
use Qubes\Reporting\Queues\RawConsumer;

/**
 * Collect and distribute raw events to report queues
 */
class Distribute extends CliCommand
{
  /**
   * Queue Provider Service to read raw events from
   * @valuerequired
   */
  public $queueService = 'queue';

  /**
   * Queue Name to pull raw events from
   * @valuerequired
   */
  public $queueName = 'reporting';

  protected $_echoLevel = 'debug';

  public function execute()
  {
    echo Shell::colourText(
      (new Figlet("speed"))->render("Distribute"),
      Shell::COLOUR_FOREGROUND_GREEN
    );
    echo "\n";

    Log::info("Starting Distribute");

    Log::debug("Setting Default Queue Provider to " . $this->queueService);
    Queue::setDefaultQueueProvider($this->queueService);

    Log::info("Starting to consume queue " . $this->queueName);
    Queue::consume(new StdQueue($this->queueName), new RawConsumer());

    Log::info("Exiting Distribute");
  }
}
