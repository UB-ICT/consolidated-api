<?php

namespace Modules\UBPortal\Enums;

enum PostStatus: string
{
    case DRAFT = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case PUBLISHED = 'published';
    case REJECTED = 'rejected';
}
