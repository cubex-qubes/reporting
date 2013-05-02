<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Worker;

use Cubex\Cli\CliCommand;
use Cubex\Events\StdEvent;
use Cubex\Facade\Queue;
use Cubex\Log\Log;
use Cubex\Queue\StdQueue;

class MockEvent extends CliCommand
{
  /**
   * @valuerequired
   */
  public $queue = 'reporting';

  /**
   * @valuerequired
   */
  public $eventName = 'user.join';

  /**
   * @valuerequired
   * @required
   */
  public $message;

  protected $_echoLevel = 'debug';

  /**
   * @return int
   */
  public function execute()
  {
    Log::debug("Got Message '" . $this->message . "'");
    Queue::push(
      new StdQueue($this->queue),
      new StdEvent($this->eventName, ['message' => $this->message], $this)
    );

    Log::info("Message Pushed");
  }
}
