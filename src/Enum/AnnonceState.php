<?php

namespace App\Enum;

enum AnnonceState: string
{
    case DRAFT = 'DRAFT';
    case PENDING_REVIEW = 'PENDING_REVIEW';
    case PUBLISHED = 'PUBLISHED';
    case REJECTED = 'REJECTED';
    case COMPLETED = 'COMPLETED';
    case ARCHIVED = 'ARCHIVED';
}
