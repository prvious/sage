<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Queued = 'queued';
    case InProgress = 'in_progress';
    case WaitingReview = 'waiting_review';
    case Done = 'done';
}
