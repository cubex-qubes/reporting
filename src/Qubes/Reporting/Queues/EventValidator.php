<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Queues;

use Cubex\Data\Validator\IValidator;
use Cubex\Data\Validator\Validator;

class EventValidator implements IValidator
{
  protected $_errors = [];

  /**
   * Set options
   *
   * @param array $options
   */
  public function setOptions(array $options = array())
  {
  }

  /**
   * @param $event
   *
   * @return bool
   */
  public function isValid($event)
  {
    if(!is_object($event) && !is_array($event))
    {
      $this->_errors[] = "invalid data type, must be either array or object";
    }

    $event = (array)$event;
    if(!isset($event['name']))
    {
      $this->_errors[] = "missing name parameter";
    }

    if(!isset($event['time']))
    {
      $this->_errors[] = "missing time parameter";
    }
    else
    {
      try
      {
        Validator::timestamp((int)$event['time']);
      }
      catch(\Exception $e)
      {
        $this->_errors[] = $e->getMessage();
      }
    }

    if(!isset($event['data']))
    {
      $this->_errors[] = "missing data parameter";
    }

    if(!isset($event['source']))
    {
      $this->_errors[] = "missing source parameter";
    }

    return empty($this->_errors);
  }

  /**
   * @return array
   */
  public function errorMessages()
  {
    return $this->_errors;
  }
}
