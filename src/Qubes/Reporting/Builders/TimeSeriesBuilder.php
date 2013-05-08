<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Builders;

use Qubes\Reporting\Helpers\PointCounterHelper;
use Qubes\Reporting\Reports\TimeSeriesReport;

class TimeSeriesBuilder
{
  protected $_report;
  protected $_reportColumns = [];
  protected $_columnLookup = [];
  protected $_totals = [];
  protected $_startTime;
  protected $_endTime;
  protected $_interval;
  protected $_drillPoints = [];
  protected $_filterPoints = [];
  protected $_rowType = self::ROWTYPE_DRILLPOINT;

  const ROWTYPE_DATE       = 'row:date';
  const ROWTYPE_DRILLPOINT = 'row:drill';

  public function __construct(TimeSeriesReport $report)
  {
    $this->_report = $report;

    $this->_reportColumns = $this->_report->getReportColumns();
  }

  public function setRowType($type = self::ROWTYPE_DATE)
  {
    $this->_rowType = $type;
    return $this;
  }

  public function setInterval($interval = TimeSeriesReport::INTERVAL_5MIN)
  {
    $this->_interval = $interval;
    return $this;
  }

  public function setDateRange($startTime, $endTime)
  {
    $this->_startTime = $startTime;
    $this->_endTime   = $endTime;
    return $this;
  }

  public function setDrillData($point1 /*,$pointX*/)
  {
    if(is_array($point1))
    {
      $this->_drillPoints = $point1;
    }
    else
    {
      $this->_drillPoints = func_get_args();
    }
    return $this;
  }

  public function setFilterData($point1 /*,$pointX*/)
  {
    if(is_array($point1))
    {
      $this->_filterPoints = $point1;
    }
    else
    {
      $this->_filterPoints = func_get_args();
    }
    return $this;
  }

  protected function _rowKeyToTime($rowKey)
  {
    if(starts_with($rowKey, 'i'))
    {
      list(, $rowKey) = explode(';', $rowKey);
    }

    list($y, $m, $d, $h, $i) = sscanf($rowKey, "%4s%2s%2s%2s%2s");
    return mktime($h, $i, 0, $m, $d, $y);
  }

  protected function _buildColumnLookup()
  {
    $this->_columnLookup = [];
    foreach($this->_reportColumns as $col)
    {
      $this->_columnLookup[$col] = $this->_report->buildColumnName(
        $col,
        $this->_drillPoints,
        $this->_filterPoints
      );
    }
  }

  public function toArray()
  {
    $this->_buildColumnLookup();

    if($this->_rowType === self::ROWTYPE_DRILLPOINT)
    {
      $return = $this->_getDrillReportArray();
    }
    else
    {
      $return = [];
    }

    $this->_totals[] = 'Total';
    foreach($return as $result)
    {
      $i = 0;
      foreach($result as $k => $v)
      {
        if(++$i > 1)
        {
          $this->_totals[$k] += $v;
        }
      }
    }

    return $return;
  }

  public function getTotalRow()
  {
    return (array)$this->_totals;
  }

  protected function _getDrillReportArray()
  {
    $return     = [];
    $drillCount = count($this->_drillPoints);
    $rows       = PointCounterHelper::getDrillPoints(
      $this->_report->getColumnFamilyName(),
      ($drillCount + 1),
      date(PointCounterHelper::DATEFORM_DAY, $this->_startTime),
      $this->_drillPoints
    );

    $columns = [];

    $rowKey = $this->_report->generateRowKey(
      time(),
      TimeSeriesReport::INTERVAL_DAY
    );

    foreach($rows as $row)
    {
      $dps       = array_merge((array)$this->_drillPoints, [$row]);
      $columns[] = $this->_report->buildColumnName(null, $dps);
    }

    if(!empty($columns))
    {
      $cf = $this->_report->reportCf()->getCf();
      foreach($columns as $column)
      {
        $row = $cf->getSlice($rowKey, $column, $column . '~');
        if($row)
        {
          $col      = explode(
            PointCounterHelper::getPointSplitter(),
            $column
          )[$drillCount];
          $return[] = $this->_parseRow(
            $row,
            $col,
            $this->_report->getDrillPoints()[$drillCount]
          );
        }
      }
    }
    return $return;
  }

  protected function _parseRow($row, $keyColumn = null, $keyName = null)
  {
    $reportRow = array_fill_keys($this->_reportColumns, 0);
    if($keyColumn !== null)
    {
      //Push key to the start of the array, maintaining key
      $reportRow           = array_reverse($reportRow, true);
      $reportRow[$keyName] = $keyColumn;
      $reportRow           = array_reverse($reportRow, true);
    }

    foreach($row as $column => $value)
    {
      $column = end(explode($this->_report->spacer(), $column));
      if(isset($reportRow[$column]))
      {
        $reportRow[$column] = $value;
      }
    }

    return $reportRow;
  }
}
