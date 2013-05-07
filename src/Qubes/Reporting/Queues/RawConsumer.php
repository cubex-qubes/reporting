<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Queues;

use Cubex\Data\Validator\Validator;
use Cubex\Log\Log;
use Cubex\Queue\IQueue;
use Cubex\Queue\IQueueConsumer;
use Cubex\Queue\StdQueue;
use Cubex\Text\TextTable;
use Qubes\Reporting\Helpers\ReportQueueHelper;
use Qubes\Reporting\Helpers\Uuid;
use Qubes\Reporting\IReportEvent;
use Qubes\Reporting\Mappers\RawEvent;
use Qubes\Reporting\Mappers\RawEventHistory;
use Qubes\Reporting\Mappers\RawEventHistoryCounter;
use Qubes\Reporting\Mappers\ReportEventHook;
use Qubes\Reporting\Mappers\ReportQueue;
use Qubes\Reporting\StdReportEvent;

class RawConsumer implements IQueueConsumer
{
  /**
   * @param $queue
   * @param $data
   *
   * @return bool
   */
  public function process(IQueue $queue, $data)
  {
    $validator = new EventValidator();
    if(!$validator->isValid($data))
    {
      Log::error(
        "Invalid Event Received: " . implode(', ', $validator->errorMessages())
      );
    }
    else
    {
      $event = $this->buildReportEvent((array)$data);
      $uuid  = Uuid::namedTimeUuid($event->name(), $event->eventTime());
      $event->setUuid($uuid);

      Log::debug("Event is valid, assuming uuid '" . $uuid . "'");

      $this->storeRawEvent($uuid, $event);

      $reports = ReportEventHook::collectionOn(new RawEvent($event->name()));
      if($reports->count() > 0)
      {
        foreach($reports as $reportHook)
        {
          /**
           * @var $reportHook ReportEventHook
           */
          $queues = ReportQueue::collection()->loadWhere(
            ['report_id' => $reportHook->reportId]
          );

          if($queues->count() > 0)
          {
            foreach($queues as $pushQueue)
            {
              $this->pushEvent($pushQueue, $event);
            }
          }
        }
      }
    }

    return true;
  }

  public function buildReportEvent(array $data, $uuid = null)
  {
    $event = new StdReportEvent(
      $data['name'], (array)$data['data'], $data['source']
    );
    $event->setEventTime($data['time']);
    if($uuid !== null)
    {
      $event->setUuid($uuid);
    }
    return $event;
  }

  public function storeRawEvent($uuid, IReportEvent $event)
  {
    //Long Term Storage
    $storage = new RawEventHistory();
    Log::debug(
      "Writing raw event to row key " . $storage->generateRowKey($event)
    );
    $storage->setId($storage->generateRowKey($event));
    $storage->setData($uuid, json_encode($event));
    $storage->saveChanges();
    RawEventHistoryCounter::cf()->increment(
      date("Ym-") . strtolower($event->name()),
      date("YmdHi")
    );
  }

  public function pushEvent(ReportQueue $pushQueue, IReportEvent $event)
  {
    try
    {
      ReportQueueHelper::buildQueueProvider($pushQueue)->push(
        new StdQueue($pushQueue->queueName),
        ['uuid' => $event->getUuid(), 'data' => $event]
      );
    }
    catch(\Exception $e)
    {
      Log::error($e->getMessage());
    }
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
    return 0;
  }

  public function shutdown()
  {
    return true;
  }
}
