<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Reports\Mappers;

use Cubex\Mapper\Cassandra\CassandraMapper;

class CassandraReportMapper extends CassandraMapper
{
  public function setColumnFamilyName($cfName)
  {
    $this->_tableName = $cfName;
    return $this;
  }
}
