<?php

namespace Modules\RequisitionSystem\Support;

final class RequisitionLogAction
{
    public const CREATED = 'created';

    public const UPDATED = 'updated';

    public const SUBMITTED = 'submitted';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const COMMENT = 'comment';

    public static function all(): array
    {
        return [
            self::CREATED,
            self::UPDATED,
            self::SUBMITTED,
            self::APPROVED,
            self::REJECTED,
            self::COMMENT,
        ];
    }
}
