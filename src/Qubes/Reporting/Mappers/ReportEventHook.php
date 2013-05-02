<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Mappers;

use Cubex\Mapper\Database\PivotMapper;

class ReportEventHook extends PivotMapper
{
  /**
   * @datatype varchar
   * @length 50
   */
  public $rawEventId;

  public $reportId;

  protected function _configure()
  {
    $this->pivotOn(new RawEvent(), new Report());
  }
}
