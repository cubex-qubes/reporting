<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting;

interface IReport
{
  public function processEvent(IReportEvent $event);
}
