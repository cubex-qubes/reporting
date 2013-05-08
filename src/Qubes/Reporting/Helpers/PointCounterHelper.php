<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Helpers;

use Qubes\Reporting\Reports\Mappers\ReportPointCounter;

class PointCounterHelper
{
  const TYPE_FILTER = 'FILTER';
  const TYPE_DRIL   = 'DRILL';
  const CF_SUFFIX   = '_PointCounter';

  const DATEFORM_DAY   = 'Ymd';
  const DATEFORM_MONTH = 'Ym';

  public static function getPointSplitter()
  {
    return chr(16);
  }

  public static function getFilterPoints(
    $report, $dataPoint, $date = null, $preKeys = []
  )
  {
    return self::_getPointOptions(
      $report,
      self::TYPE_FILTER,
      $dataPoint,
      $date,
      true,
      $preKeys
    );
  }

  public static function getDrillPoints(
    $report, $dataPoint, $date = null, $preKeys = []
  )
  {
    return self::_getPointOptions(
      $report,
      self::TYPE_DRIL,
      $dataPoint,
      $date,
      true,
      $preKeys
    );
  }

  protected static function _getPointOptions(
    $report, $type, $index, $date = null, $sorted = true, $preKeys = []
  )
  {

    $preKey = '';
    if(!empty($preKeys))
    {
      $preKey = implode(self::getPointSplitter(), $preKeys);
      $preKey .= self::getPointSplitter();
    }
    $start = strlen($preKey);

    if($date === null)
    {
      $date = date(self::DATEFORM_MONTH);
    }

    $rowKey = $date . '-' . $type . '-' . $index;

    $pointCounter = new ReportPointCounter();
    $pointCounter->setColumnFamilyName($report . self::CF_SUFFIX);
    $keys = $pointCounter->getCf()->getSlice(
      $rowKey,
      $preKey,
      $preKey . '~',
      false,
      1000
    );

    $filteredKeys = [];
    foreach($keys as $key => $value)
    {
      $key                = substr($key, $start);
      $filteredKeys[$key] = $value;
    }

    if($sorted)
    {
      arsort($filteredKeys);
    }

    return array_keys($filteredKeys);
  }
}
