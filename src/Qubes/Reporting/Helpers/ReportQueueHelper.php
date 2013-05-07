<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Helpers;

use Cubex\Foundation\Config\Config;
use Cubex\Queue\IQueueProvider;
use Cubex\ServiceManager\ServiceConfig;
use Qubes\Reporting\Mappers\ReportQueue;

class ReportQueueHelper
{
  public static function buildQueueProvider(ReportQueue $reportQueue)
  {
    if(class_exists($reportQueue->queueProvider))
    {
      $queue = new $reportQueue->queueProvider();
      if($queue instanceof IQueueProvider)
      {
        $conf = new Config();
        if($reportQueue->configuration !== null)
        {
          $conf->hydrate($reportQueue->configuration);
        }
        $configuration = new ServiceConfig();
        $configuration->fromConfig($conf);
        $queue->configure($configuration);
        return $queue;
      }
      else
      {
        throw new \Exception(
          "The class " . $reportQueue->queueProvider . " is not a valid " .
          "IQueueProvider, ReportQueue[" . $reportQueue->id() . "]"
        );
      }
    }
    else
    {
      throw new \Exception(
        "The class " . $reportQueue->queueProvider . " could not be " .
        "loaded, but is required by " .
        "ReportQueue[" . $reportQueue->id() . "]"
      );
    }
  }
}
