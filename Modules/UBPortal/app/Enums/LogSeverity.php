<?php

namespace Modules\UBPortal\Enums;

enum LogSeverity: string
{
   case low = 'low';
   case medium = 'medium';
   case high = 'high'; 
   case critical = 'critical';
}
