<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:list',
    description: 'Liste tous les utilisateurs avec leurs rôles et statut de reconnaissance faciale',
)]
class ListUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->em->getRepository(User::class)->findAll();

        if (empty($users)) {
            $io->warning('Aucun utilisateur trouvé dans la base de données.');
            return Command::SUCCESS;
        }

        $io->title('Liste des utilisateurs');

        $tableData = [];
        foreach ($users as $user) {
            $roles   = $user->getRoles();
            $isAdmin = in_array('ROLE_ADMIN', $roles) || in_array('ROLE_SUPER_ADMIN', $roles);
            $hasFace = $user->getFaceDescriptor() ? '✓ Oui' : '✗ Non';

            $tableData[] = [
                $user->getId(),
                $user->getEmail(),
                $user->getUsername(),
                implode(', ', $roles),
                $isAdmin ? '✓ Oui' : '✗ Non',
                $hasFace,
            ];
        }

        $io->table(
            ['ID', 'Email', 'Username', 'Roles', 'Est Admin?', 'Face?'],
            $tableData
        );

        // ✅ Fix PHPStan : typage explicite du callback
        $adminCount = count(array_filter($users, function (User $u): bool {
            $roles = $u->getRoles();
            return in_array('ROLE_ADMIN', $roles) || in_array('ROLE_SUPER_ADMIN', $roles);
        }));

        $faceCount = count(array_filter($users, fn(User $u): bool => $u->getFaceDescriptor() !== null));

        $io->section('Statistiques :');
        $io->text([
            'Total utilisateurs : ' . count($users),
            'Administrateurs (ADMIN ou SUPER_ADMIN) : ' . $adminCount,
            'Avec reconnaissance faciale : ' . $faceCount,
        ]);

        return Command::SUCCESS;
    }
}