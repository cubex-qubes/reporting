<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting\Enums;

use Cubex\Type\Enum;

/**
 * Types of reports
 * @method static TIMESERIES
 * @method static CUSTOM
 */
class ReportType extends Enum
{
  const __default  = 'timeseries';
  const TIMESERIES = 'timeseries';
  const CUSTOM     = 'custom';
}
