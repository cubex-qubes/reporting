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

  public static function getFilterPoints($report, $dataPoint, $date = null)
  {
    return self::_getPointOptions(
      $report,
      self::TYPE_FILTER,
      $dataPoint,
      $date
    );
  }

  public static function getDrillPoints($report, $dataPoint, $date = null)
  {
    return self::_getPointOptions(
      $report,
      self::TYPE_DRIL,
      $dataPoint,
      $date
    );
  }

  protected static function _getPointOptions(
    $report, $type, $index, $date = null, $sorted = true
  )
  {
    if($date === null)
    {
      $date = date(self::DATEFORM_MONTH);
    }

    $rowKey = $date . '-' . $type . '-' . $index;

    $pointCounter = new ReportPointCounter();
    $pointCounter->setColumnFamilyName($report . self::CF_SUFFIX);
    $keys = $pointCounter->getCf()->getSlice($rowKey, '', '', false, 1000);
    if($sorted)
    {
      arsort($keys);
    }

    return array_keys($keys);
  }
}
