<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Reports\Mappers;

use Cubex\Cassandra\CassandraMapper;

class CassandraReportMapper extends CassandraMapper
{
  public function setColumnFamilyName($cfName)
  {
    $this->_tableName = $cfName;
    return $this;
  }
}
