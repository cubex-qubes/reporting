<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Tools;

use Cubex\Chronos\Stopwatch;
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
    $stopwatch = new Stopwatch("reporttime");
    $stopwatch->start();
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
      $drillPoints = Strings::stringToRange($this->drills);
      if($drillPoints)
      {
        $builder->setDrillData($drillPoints);
      }
      $filterPoints = Strings::stringToRange($this->filters);
      if($drillPoints)
      {
        $builder->setFilterData($filterPoints);
      }
      $table = TextTable::fromArray($builder->toArray());
      $table->appendSpacer();
      $table->appendRow($builder->getTotalRow());
      echo $table;
    }
    $stopwatch->stop();
    echo "\nReport Generated in: ";
    echo round($stopwatch->totalTime(), 5) . " seconds \n";
  }
}
