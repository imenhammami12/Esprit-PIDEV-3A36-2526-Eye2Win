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

        // Détecter automatiquement toutes les colonnes SMALLINT qui s'appellent
        // is_*, has_*, can_* (conventions boolean)
        $sql = "
            SELECT table_name, column_name, column_default
            FROM information_schema.columns
            WHERE table_schema = 'public'
            AND data_type = 'smallint'
            AND (
                column_name LIKE 'is_%'
                OR column_name LIKE 'has_%'
                OR column_name LIKE 'can_%'
                OR column_name LIKE 'enable%'
                OR column_name LIKE '%_enabled'
                OR column_name LIKE '%_verified'
                OR column_name LIKE '%_active'
                OR column_name LIKE '%_read'
                OR column_name LIKE '%_seen'
                OR column_name LIKE '%_deleted'
            )
            ORDER BY table_name, column_name
        ";

        $columns = $conn->executeQuery($sql)->fetchAllAssociative();

        if (empty($columns)) {
            return new Response('✅ Aucune colonne SMALLINT boolean trouvée. Tout est déjà OK!');
        }

        foreach ($columns as $item) {
            $table = $item['table_name'];
            $col   = $item['column_name'];
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
            '<h2>Résultats migration</h2>' . implode('<br>', $results),
            200,
            ['Content-Type' => 'text/html']
        );
    }
}