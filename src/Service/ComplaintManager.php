<?php

namespace App\Service;

use App\Entity\Complaint;

class ComplaintManager
{
    public function validate(Complaint $complaint): bool
    {
        // Rule 1: subject is required and minimum 5 characters
        if (empty(trim($complaint->getSubject() ?? ''))) {
            throw new \InvalidArgumentException('Subject is required.');
        }

        if (strlen(trim($complaint->getSubject() ?? '')) < 5) {
            throw new \InvalidArgumentException('Subject must contain at least 5 characters.');
        }

        // Rule 2: description minimum 10 characters
        if (strlen(trim($complaint->getDescription() ?? '')) < 10) {
            throw new \InvalidArgumentException('Description must contain at least 10 characters.');
        }

        // Rule 3: category is required
        if ($complaint->getCategory() === null) {
            throw new \InvalidArgumentException('Category is required.');
        }

        return true;
    }
}