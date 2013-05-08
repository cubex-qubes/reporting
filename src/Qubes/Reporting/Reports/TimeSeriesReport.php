<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Reports;

use Cubex\Log\Log;
use Qubes\Reporting\Helpers\PointCounterHelper;
use Qubes\Reporting\Mappers\Inaccuracy;
use Qubes\Reporting\Mappers\RawEvent;
use Qubes\Reporting\Reports\Mappers\ReportCounter;
use Qubes\Reporting\Reports\Mappers\ReportPointCounter;

abstract class TimeSeriesReport extends BaseReport
{
  protected $_counterCF;
  protected $_pointCounterCF;

  protected $_drillPointData = [];
  protected $_filterPointData = [];

  protected $_pointSpacer;
  protected $_pointEmpty;

  const INTERVAL_MONTH = "0;Ym";
  const INTERVAL_DAY   = "0;Ymd";
  const INTERVAL_HOUR  = "0;YmdH";
  const INTERVAL_6HOUR = "360;YmdH";
  const INTERVAL_3HOUR = "180;YmdH";
  const INTERVAL_15MIN = "15;YmdHi";
  const INTERVAL_5MIN  = "5;YmdHi";

  public function __construct()
  {
    $this->_counterCF = new ReportCounter();
    $this->_counterCF->setColumnFamilyName($this->getColumnFamilyName());
    $this->_pointCounterCF = new ReportPointCounter();
    $this->_pointCounterCF->setColumnFamilyName(
      $this->getColumnFamilyName() . PointCounterHelper::CF_SUFFIX
    );

    $this->_pointEmpty  = chr(0);
    $this->_pointSpacer = chr(16);

    /*$this->_pointEmpty  = "NA";
    $this->_pointSpacer = "|";*/
  }

  public function reportCf()
  {
    return $this->_counterCF;
  }

  abstract public function getColumnFamilyName();

  public function getIntervals()
  {
    return [
      self::INTERVAL_MONTH,
      self::INTERVAL_DAY,
      self::INTERVAL_HOUR,
      self::INTERVAL_6HOUR,
      self::INTERVAL_3HOUR,
      self::INTERVAL_15MIN,
      self::INTERVAL_5MIN,
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
      $keys[] = $this->generateRowKey($time, $interval);
    }
    return $keys;
  }

  public function generateRowKey($date, $interval = self::INTERVAL_DAY)
  {
    list($intervalMins, $format) = explode(';', $interval, 2);
    return $this->_makeRowKey($date, $format, $intervalMins);
  }

  protected function _makeRowKey($date, $format, $interval)
  {
    if($interval <= 1)
    {
      $keys = date($format, $date);
    }
    else
    {
      $keys = "i" . $interval . ":" .
        date($format, $this->makeIntervalTime($date, $interval));
    }
    return $keys;
  }

  public function getDateRowKeys(
    $startTime, $endTime, $step = 1440, $format = 'Ymd', $interval = 0
  )
  {
    $rowKeys = [];
    $keys    = range($startTime, $endTime, $step * 60);
    foreach($keys as $key)
    {
      $rowKeys[] = $this->_makeRowKey($key, $format, $interval);
    }
    return $rowKeys;
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
        ("You must specify enough drill points to match {" .
          implode(",", $drillPoints) . "}")
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
        ("You must specify enough filter points to match {" .
          implode(",", $filterPoints) . "}")
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

    try
    {
      $this->_counterCF->getCf()->closeBatch();
    }
    catch(\Exception $e)
    {
      //Track data inaccuracy for future rebuilding of data
      Inaccuracy::cf()->increment(
        date("YmdHi", $this->_event->eventTime()),
        $this->getColumnFamilyName(),
        1
      );
      Log::error("Report Update Error: " . $e->getMessage());
    }

    $this->_incrementPointCounters();

    return $this;
  }

  protected function _incrementPointCounters()
  {
    $this->_pointCounterCF->getCf()->openBatch();
    $drillPoints = $this->getDrillPointData();
    if(!empty($drillPoints))
    {
      $this->_writePointCounters(PointCounterHelper::TYPE_DRIL, $drillPoints);
    }
    $filterPoints = $this->getFilterPointData();
    if(!empty($filterPoints))
    {
      $this->_writePointCounters(
        PointCounterHelper::TYPE_FILTER,
        $filterPoints
      );
    }

    try
    {
      $this->_pointCounterCF->getCf()->closeBatch();
    }
    catch(\Exception $e)
    {
      //Track data inaccuracy for future rebuilding of data
      Inaccuracy::cf()->increment(
        date("YmdHi", $this->_event->eventTime()),
        ($this->getColumnFamilyName() . '-datapoints'),
        1
      );
      Log::error("Report Data Points Error: " . $e->getMessage());
    }
  }

  protected function _writePointCounters($type, array $values)
  {
    $dayPrefix   = date(
      PointCounterHelper::DATEFORM_DAY,
      $this->_event->eventTime()
    );
    $monthPrefix = date(
      PointCounterHelper::DATEFORM_MONTH,
      $this->_event->eventTime()
    );

    $i               = 0;
    $processedValues = [];
    foreach($values as $value)
    {
      $i++;
      if($type === PointCounterHelper::TYPE_DRIL)
      {
        $useValue = implode(
          PointCounterHelper::getPointSplitter(),
          array_merge($processedValues, [$value])
        );
      }
      else
      {
        $useValue = $value;
      }

      $key = $dayPrefix . '-' . $type . '-' . $i;
      $this->_pointCounterCF->getCf()->increment($key, $useValue, 1);
      $key = $monthPrefix . '-' . $type . '-' . $i;
      $this->_pointCounterCF->getCf()->increment($key, $useValue, 1);
      $processedValues[] = $value;
    }
  }

  public function spacer()
  {
    return $this->_pointSpacer;
  }

  public function emptyValue()
  {
    return $this->_pointEmpty;
  }

  public function buildColumnName(
    $column = null, $drillPointData = null, $filterPointData = null
  )
  {
    $keyArray = [];

    $drillPoints      = $this->getDrillPoints();
    $drillPointsCount = count($drillPoints);
    if($drillPointsCount > 0)
    {
      for($i = 0; $i < $drillPointsCount; $i++)
      {
        $keyArray[] = isset($drillPointData[$i]) ?
          $drillPointData[$i] : $this->_pointEmpty;
      }
    }

    $filterPoints      = $this->getFilterPoints();
    $filterPointsCount = count($filterPoints);
    if($filterPointsCount > 0)
    {
      for($i = 0; $i < $filterPointsCount; $i++)
      {
        $keyArray[] = isset($filterPointData[$i]) ?
          $filterPointData[$i] : $this->_pointEmpty;
      }
    }

    if($column !== null)
    {
      $keyArray[] = $column;
    }

    return implode($this->_pointSpacer, $keyArray);
  }
}
