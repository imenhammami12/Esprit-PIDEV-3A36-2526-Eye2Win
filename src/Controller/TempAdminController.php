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
        
        return new Response('Done! ' . $user->getEmail() . ' is now ROLE_ADMIN.');
    }

#[Route('/setup-migration-x7k9q2', name: 'temp_migration')]
public function migration(EntityManagerInterface $em): Response
{
    $conn = $em->getConnection();
    $results = [];

    $columns = [
        ['table' => 'notification',          'column' => 'read'],
        ['table' => 'password_reset_tokens', 'column' => 'used'],
        ['table' => 'planning',              'column' => 'need_partner'],
    ];

    foreach ($columns as $item) {
        $table = $item['table'];
        $col   = $item['column'];
        try {
            $conn->executeStatement("ALTER TABLE \"$table\" ALTER COLUMN \"$col\" DROP DEFAULT");
            $conn->executeStatement("ALTER TABLE \"$table\" ALTER COLUMN \"$col\" TYPE BOOLEAN USING (\"$col\"::int != 0)");
            $conn->executeStatement("ALTER TABLE \"$table\" ALTER COLUMN \"$col\" SET DEFAULT FALSE");
            $results[] = "✅ $table.$col converti en BOOLEAN";
        } catch (\Exception $e) {
            $results[] = "⚠️ $table.$col ERREUR : " . $e->getMessage();
        }
    }

    return new Response(
        '<h2>Résultats</h2>' . implode('<br>', $results),
        200,
        ['Content-Type' => 'text/html']
    );
}
}