<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Reports;

use Qubes\Reporting\IReport;
use Qubes\Reporting\IReportEvent;
use Qubes\Reporting\Mappers\RawEvent;
use Qubes\Reporting\Reports\Mappers\ReportCounter;

abstract class TimeSeriesReport implements IReport
{
  protected $_counterCF;
  /**
   * @var IReportEvent
   */
  protected $_event;

  protected $_drillPointData = [];
  protected $_filterPointData = [];

  protected $_pointSpacer;
  protected $_pointEmpty;

  public function __construct()
  {
    $this->_counterCF = new ReportCounter();
    $this->_counterCF->setColumnFamilyName($this->getColumnFamilyName());

    $this->_pointEmpty  = chr(0);
    $this->_pointSpacer = chr(16);
  }

  abstract public function getColumnFamilyName();

  public function getIntervals()
  {
    return [
      ["Ym", 0],
      ["Ymd", 0],
      ["YmdH", 0],
      ["YmdH", 360],
      ["YmdH", 180],
      ["YmdHi", 15],
      ["YmdHi", 5],
    ];
  }

  public function getIntervalKeys($time = null)
  {
    if($time === null)
    {
      $time = $this->_event->eventTime();
    }

    $keys = [];
    foreach($this->getIntervals() as $interval)
    {
      list($format, $intervalMins) = $interval;
      if($intervalMins <= 1)
      {
        $keys[] = date($format, $time);
      }
      else
      {
        $keys[] = "i" . $intervalMins . ":" .
        date($format, $this->makeIntervalTime($time, $intervalMins));
      }
    }
    return $keys;
  }

  public function makeIntervalTime($time, $intervalMinutes)
  {
    return floor($time / ($intervalMinutes * 60)) * ($intervalMinutes * 60);
  }

  public function getDrillPoints()
  {
    return [];
  }

  public function getDrillPointData()
  {
    return $this->_drillPointData;
  }

  public function setDrillPointData($drillData)
  {
    if(!(is_array($drillData) && func_get_args() === 1))
    {
      $drillData = func_get_args();
    }
    $this->_drillPointData = $drillData;
    return $this;
  }

  public function getDrillPointKeys()
  {
    $drillPoints = $this->getDrillPoints();
    $drillData   = $this->getDrillPointData();
    $argNums     = count($drillData);

    if($argNums < 1)
    {
      return [''];
    }

    if(count($drillPoints) !== $argNums)
    {
      throw new \Exception(
        "You must specify enough drill points to match {" .
        implode(",", $drillPoints) . "}"
      );
    }
    $keys = [];
    for($i = 0; $i <= $argNums; $i++)
    {
      $key = implode($this->_pointSpacer, array_slice($drillData, 0, $i));
      $key .= str_repeat(
        $this->_pointSpacer . $this->_pointEmpty,
        ($argNums - $i)
      );
      $key = ltrim($key, $this->_pointSpacer);
      $key .= $this->_pointSpacer;
      $keys[] = $key;
    }
    return $keys;
  }

  public function getFilterPoints()
  {
    return [];
  }

  public function getFilterPointData()
  {
    return $this->_filterPointData;
  }

  public function setFilterPointData($filterData)
  {
    if(!(is_array($filterData) && func_get_args() === 1))
    {
      $filterData = func_get_args();
    }
    $this->_filterPointData = $filterData;
    return $this;
  }

  public function getFilterPointKeys()
  {
    $filterPoints = $this->getFilterPoints();
    $filterData   = $this->getFilterPointData();
    $argNums      = count($filterData);

    if($argNums < 1)
    {
      return [''];
    }

    if(count($filterPoints) !== $argNums)
    {
      throw new \Exception(
        "You must specify enough filter points to match {" .
        implode(",", $filterPoints) . "}"
      );
    }
    $keys = [];
    //The total number of possible combinations
    $total = pow(2, $argNums);

    for($i = 0; $i < $total; $i++)
    {
      $key = '';
      for($j = 0; $j < $argNums; $j++)
      {
        //Is bit $j set in $i?
        if(pow(2, $j) & $i)
        {
          $key .= $filterData[$j];
        }
        else
        {
          $key .= $this->_pointEmpty;
        }
        $key .= $this->_pointSpacer;
      }
      $keys[] = $key;
    }
    return $keys;
  }

  public function setEvent(IReportEvent $event)
  {
    $this->_event = $event;
    return $this;
  }

  abstract public function processEvent();

  public function incrementCounters($column, $incr)
  {
    $drillKeys  = $this->getDrillPointKeys();
    $filterKeys = $this->getFilterPointKeys();
    $keys       = $this->getIntervalKeys();
    $this->_counterCF->getCf()->openBatch();
    foreach($keys as $rowKey)
    {
      foreach($drillKeys as $drillKey)
      {
        foreach($filterKeys as $filterKey)
        {
          $this->_counterCF->getCf()->increment(
            $rowKey,
            ($drillKey . $filterKey . $column),
            $incr
          );
        }
      }
    }
    $this->_counterCF->getCf()->closeBatch();
    return $this;
  }
}
