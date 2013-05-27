<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Tools;

use Cubex\Cli\CliCommand;

class ReactTest extends CliCommand
{
  public function execute()
  {
    $values = [2, 4, 6, 8, 10, 13, 14, 15, 18, 20, 30, 14, 15, 10, 10, 9, 3];

    $window = new \React\EEP\Window\Periodic(
      new \React\EEP\Stats\Sum,
      50
    );

    // Register callback
    $window->on(
      'emit',
      function ($value)
      {
        echo "Window Sum:\t", $value, "\n";
      }
    );

    // Pump data into the tumbling windows
    foreach($values as $v)
    {
      msleep(20);
      $window->enqueue($v);
      $window->tick();
    }
  }
}
