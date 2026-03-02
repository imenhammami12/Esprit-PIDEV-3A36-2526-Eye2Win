<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TempAdminController extends AbstractController
{
    #[Route('/setup-admin-x7k9q2', name: 'temp_admin_setup')]
    public function setup(EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'soniaalaya1481@gmail.com']);
        
        if (!$user) {
            return new Response('User not found', 404);
        }
        
        $user->setRoles(['ROLE_ADMIN']);
        $em->flush();
        
        return new Response('Done! ' . $user->getEmail() . ' is now ROLE_ADMIN. DELETE THIS FILE NOW!');
    }

    #[Route('/setup-migration-x7k9q2', name: 'temp_migration')]
    public function migration(EntityManagerInterface $em): Response
    {
        try {
            $em->getConnection()->executeStatement('ALTER TABLE "user" ALTER COLUMN is_totp_enabled DROP DEFAULT');
            $em->getConnection()->executeStatement('ALTER TABLE "user" ALTER COLUMN is_totp_enabled TYPE BOOLEAN USING (is_totp_enabled::int != 0)');
            $em->getConnection()->executeStatement('ALTER TABLE "user" ALTER COLUMN is_totp_enabled SET DEFAULT FALSE');
            return new Response('Migration OK! La colonne is_totp_enabled est maintenant BOOLEAN.');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage());
        }
    }
}