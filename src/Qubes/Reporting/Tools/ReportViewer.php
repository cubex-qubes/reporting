<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Tools;

use Cubex\Cli\CliCommand;
use Cubex\Helpers\Strings;
use Cubex\Text\TextTable;
use Qubes\Reporting\Builders\TimeSeriesBuilder;
use Qubes\Reporting\IReport;
use Qubes\Reporting\Reports\TimeSeriesReport;

class ReportViewer extends CliCommand
{
  /**
   * @valuerequired
   * @required
   */
  public $report;

  /**
   * @valuerequired
   */
  public $filters;
  /**
   * @valuerequired
   */
  public $drills;

  /**
   * @var \Qubes\Reporting\Reports\Mappers\ReportCounter
   */
  protected $_report;

  public function execute()
  {
    $reportClass   = '\Qubes\Reporting\Reports\\' . $this->report;
    $this->_report = new $reportClass();
    if(!($this->_report instanceof IReport))
    {
      throw new \Exception(
        "The report class you specified could not be loaded, or is not valid"
      );
    }

    if($this->_report instanceof TimeSeriesReport)
    {
      $builder = new TimeSeriesBuilder($this->_report);
      $builder->setDateRange(strtotime('today'), time());
      $builder->setInterval(TimeSeriesReport::INTERVAL_5MIN);
      echo TextTable::fromArray($builder->toArray());
    }
  }
}
