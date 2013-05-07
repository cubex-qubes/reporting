<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Tools;

use Cubex\Cli\CliCommand;
use Qubes\Reporting\Helpers\PointCounterHelper;

class DataPoints extends CliCommand
{
  /**
   * @valuerequired
   */
  public $drillPoint;

  /**
   * @valuerequired
   */
  public $filterPoint;

  /**
   * @valuerequired
   * @required
   */
  public $reportCf;

  public function execute()
  {
    if($this->drillPoint)
    {
      echo "Drill Points: \n";
      $drillPoints = PointCounterHelper::getDrillPoints(
        $this->reportCf,
        $this->drillPoint
      );
      echo implode(", ", $drillPoints);
      echo "\n";
    }

    if($this->filterPoint)
    {
      echo "Filter Points: \n";
      $drillPoints = PointCounterHelper::getFilterPoints(
        $this->reportCf,
        $this->filterPoint
      );
      echo implode(", ", $drillPoints);
      echo "\n";
    }
  }
}
