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

    // Lister TOUTES les colonnes SMALLINT sans filtre sur le nom
    $sql = "
        SELECT table_name, column_name, column_default
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND data_type = 'smallint'
        ORDER BY table_name, column_name
    ";

    $columns = $conn->executeQuery($sql)->fetchAllAssociative();

    if (empty($columns)) {
        return new Response('✅ Aucune colonne SMALLINT trouvée.');
    }

    // Afficher la liste sans convertir pour diagnostiquer
    $list = [];
    foreach ($columns as $item) {
        $list[] = "📋 {$item['table_name']}.{$item['column_name']} (default: {$item['column_default']})";
    }

    return new Response(
        '<h2>Colonnes SMALLINT restantes</h2>' . implode('<br>', $list),
        200,
        ['Content-Type' => 'text/html']
    );
}
}