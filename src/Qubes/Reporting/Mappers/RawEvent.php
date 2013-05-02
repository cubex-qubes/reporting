<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Mappers;

use Cubex\Mapper\Database\RecordMapper;

class RawEvent extends RecordMapper
{
  /**
   * @datatype varchar
   * @length 50
   */
  public $reference;
  public $name;
  public $description;

  protected $_idType = self::ID_MANUAL;

  public function getIdKey()
  {
    return 'reference';
  }
}
