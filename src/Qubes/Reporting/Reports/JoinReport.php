<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Reports;

class JoinReport extends TimeSeriesReport
{

  public $joins;
  public $sales;

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
    return ['payment_type', 'tracked'];
  }

  public function processEvent()
  {
    $this->setDrillPointData(rand(0, 20), rand(0, 10), rand(0, 15));
    $this->setFilterPointData(rand(0, 1), rand(0, 1));

    if($this->_event->getStr("type") === 'sale')
    {
      $this->incrementCounters("sales", 1);
    }
    else if($this->_event->getStr("type") === 'join')
    {
      $this->incrementCounters("joins", 1);
    }
  }
}
