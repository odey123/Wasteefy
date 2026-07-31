<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Resolved = 'resolved';
    case Rejected = 'rejected';
}
