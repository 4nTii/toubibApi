<?php

namespace App\Repository;

use App\Entity\AppConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppConfig>
 *
 * @method AppConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method AppConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method AppConfig[]    findAll()
 * @method AppConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppConfig::class);
    }

    public function add(AppConfig $config, bool $flush = true): void
    {
        $this->getEntityManager()->persist($config);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AppConfig $config, bool $flush = true): void
    {
        $this->getEntityManager()->remove($config);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Retourne tous les paramètres de l'application sous forme de tableau clé => valeur
     */
    public function getConfig(): array
    {
        $config = $this->findAll();
        $result = [];

        foreach ($config as $item) {
            $result['appName'] = $item->getAppName();
            $result['version'] = $item->getVersion();
            $result['logo'] = $item->getLogo();
            $result['maintenance'] = $item->isMaintenance();
            $result['updateAt'] = $item->getUpdateAt();
        }

        return $result;
    }
}
