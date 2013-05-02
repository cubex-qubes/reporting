<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Mappers;

use Cubex\Data\Attribute;
use Cubex\Mapper\Database\RecordMapper;

class Report extends RecordMapper
{
  public $description;
  public $name;
  /**
   * Report class to use
   */
  public $class;
  /**
   * @enumclass \Qubes\Reporting\Enums\ReportType
   */
  public $type;
  public $configuration;

  protected function _configure()
  {
    $this->_setSerializer("configuration");
  }

  public function queues()
  {
    return $this->hasMany(new ReportQueue());
  }
}
