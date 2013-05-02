<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Mappers;

use Cubex\Mapper\Cassandra\CassandraMapper;
use Qubes\Reporting\IReportEvent;

class RawEventHistory extends CassandraMapper
{
  public function generateRowKey(IReportEvent $event)
  {
    return date("YmdHi") . ':' . strtolower($event->name());
  }
}
