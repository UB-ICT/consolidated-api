<?php

namespace Modules\RequisitionSystem\Support;

final class BudgetLogAction
{
    public const CREATED = 'created';

    public const UPDATED = 'updated';

    public const SUBMITTED = 'submitted';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const COMMENT = 'comment';

    public const COST_CENTER_REVIEW = 'cost_center_review';

    public const ACTIVATED = 'activated';

    public const DEACTIVATED = 'deactivated';

    public const SUBMISSIONS_OPENED = 'submissions_opened';

    public const SUBMISSIONS_CLOSED = 'submissions_closed';

    public static function all(): array
    {
        return [
            self::CREATED,
            self::UPDATED,
            self::SUBMITTED,
            self::APPROVED,
            self::REJECTED,
            self::COMMENT,
            self::COST_CENTER_REVIEW,
            self::ACTIVATED,
            self::DEACTIVATED,
            self::SUBMISSIONS_OPENED,
            self::SUBMISSIONS_CLOSED,
        ];
    }
}
