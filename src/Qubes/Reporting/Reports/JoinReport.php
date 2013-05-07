<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Reports;

class JoinReport extends TimeSeriesReport
{

  public $sales;
  public $joins;

  public function getColumnFamilyName()
  {
    return "ExampleReport";
  }

  public function getDrillPoints()
  {
    return ['affiliateId', 'hopId', 'tid'];
  }

  public function getFilterPoints()
  {
    return ['direct_or_affiliate', 'free_or_trial'];
  }

  public function processEvent()
  {
    $this->setDrillPointData(1, 2, 3);
    $this->setFilterPointData(4, 5);

    if($this->_event->getStr("type") === 'sale')
    {
      $this->incrementCounters("sales", 1);
    }
    else if($this->_event->getStr("type") === 'join')
    {
      $this->incrementCounters("join", 1);
    }
  }
}
