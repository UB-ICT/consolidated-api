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

    public const COST_CENTER_REVIEW = 'cost_center_review';

    public const FORWARDED_FOR_REVIEW = 'forwarded_for_review';

    public const DELEGATED_REVIEW_SUBMITTED = 'delegated_review_submitted';

    public const CANCELLED = 'cancelled';

    public const CLOSED = 'closed';

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
            self::FORWARDED_FOR_REVIEW,
            self::DELEGATED_REVIEW_SUBMITTED,
            self::CANCELLED,
            self::CLOSED,
        ];
    }
}
