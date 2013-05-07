<?php
/**
 * @author  brooke.bryan
 */

namespace Qubes\Reporting;

interface IReport
{
  public function processEvent();

  public function setEvent(IReportEvent $event);

  public function getReportColumns();
}
