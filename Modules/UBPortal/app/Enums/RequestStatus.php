<?php

namespace Modules\UBPortal\Enums;

enum RequestStatus: string
{
    case pending = 'pending';
    case approved = 'approved';
    case rejected = 'rejected';
}
