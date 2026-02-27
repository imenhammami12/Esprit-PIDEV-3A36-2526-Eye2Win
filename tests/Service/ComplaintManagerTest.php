<?php

namespace App\Tests\Service;

use App\Entity\Complaint;
use App\Entity\ComplaintCategory;
use App\Service\ComplaintManager;
use PHPUnit\Framework\TestCase;

class ComplaintManagerTest extends TestCase
{
    private ComplaintManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ComplaintManager();
    }

    public function testValidComplaint(): void
    {
        $c = (new Complaint())
            ->setSubject('Problème de connexion')
            ->setDescription('Je ne peux plus me connecter depuis hier.')
            ->setCategory(ComplaintCategory::TECHNICAL);

        $this->assertTrue($this->manager->validate($c));
    }

    public function testEmptySubject(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le sujet est obligatoire.');

        (new Complaint())
            ->setSubject('')
            ->setDescription('Description suffisamment longue.')
            ->setCategory(ComplaintCategory::BUG);

        $this->manager->validate((new Complaint())
            ->setSubject('')
            ->setDescription('Description suffisamment longue.')
            ->setCategory(ComplaintCategory::BUG));
    }

    public function testSubjectTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le sujet doit contenir au moins 5 caractères.');

        $this->manager->validate((new Complaint())
            ->setSubject('Bug')
            ->setDescription('Description suffisamment longue.')
            ->setCategory(ComplaintCategory::BUG));
    }

    public function testDescriptionTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description doit contenir au moins 10 caractères.');

        $this->manager->validate((new Complaint())
            ->setSubject('Sujet valide')
            ->setDescription('Court')
            ->setCategory(ComplaintCategory::ACCOUNT));
    }

    public function testNullCategory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La catégorie est obligatoire.');

        $c = new Complaint();
        $c->setSubject('Sujet valide ici');
        $c->setDescription('Description suffisamment longue pour passer.');
        // pas de setCategory → null

        $this->manager->validate($c);
    }
}