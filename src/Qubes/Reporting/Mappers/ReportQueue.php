<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Mappers;

use Cubex\Data\Attribute;
use Cubex\Mapper\Database\RecordMapper;

class ReportQueue extends RecordMapper
{
  public $reportId;
  public $name;
  /**
   * Queue Provider Class
   */
  public $queueProvider;
  public $configuration;

  protected function _configure()
  {
    $this->_setSerializer("configuration");
  }
}
