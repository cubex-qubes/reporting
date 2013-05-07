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
    $this->_drillPoints = func_get_args();
    return $this;
  }

  public function setFilterData($point1 /*,$pointX*/)
  {
    $this->_filterPoints = func_get_args();
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

    return $return;
  }

  protected function _getDrillReportArray()
  {
    $return = [];
    $rows   = PointCounterHelper::getDrillPoints(
      $this->_report->getColumnFamilyName(),
      (count($this->_drillPoints) + 1),
      date(PointCounterHelper::DATEFORM_DAY, $this->_startTime)
    );

    $columns = [];

    $rowKey = date("Ymd");

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
          $return[] = $this->_parseRow(
            $row,
            $column,
            $this->_report->getDrillPoints()[count($this->_drillPoints)]
          );
        }
      }
    }
    return $return;
  }

  protected function _parseRow($row, $keyColumn = null, $keyName = null)
  {
    $reportRow = [];
    if($keyColumn !== null)
    {
      $reportRow[$keyName] = $keyColumn;
    }

    foreach($this->_reportColumns as $column)
    {
      if($row && isset($slice[$this->_columnLookup[$column]]))
      {
        $reportRow[$column] = $slice[$this->_columnLookup[$column]];
      }
      else
      {
        $reportRow[$column] = 0;
      }
    }
    return $reportRow;
  }
}
