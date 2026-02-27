<?php

namespace App\Service;

use App\Entity\Complaint;

class ComplaintManager
{
    public function validate(Complaint $complaint): bool
    {
        // Règle 1 : sujet obligatoire et min 5 caractères
        if (empty(trim($complaint->getSubject() ?? ''))) {
            throw new \InvalidArgumentException('Le sujet est obligatoire.');
        }

        if (strlen(trim($complaint->getSubject() ?? '')) < 5) {
            throw new \InvalidArgumentException('Le sujet doit contenir au moins 5 caractères.');
        }

        // Règle 2 : description min 10 caractères
        if (strlen(trim($complaint->getDescription() ?? '')) < 10) {
            throw new \InvalidArgumentException('La description doit contenir au moins 10 caractères.');
        }

        // Règle 3 : catégorie obligatoire
        if ($complaint->getCategory() === null) {
            throw new \InvalidArgumentException('La catégorie est obligatoire.');
        }

        return true;
    }
}