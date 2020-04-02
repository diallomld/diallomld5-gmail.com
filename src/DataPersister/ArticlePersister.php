<?php

namespace App\DataPersiter;

use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use App\Entity\Article;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class ArticlePersister implements DataPersisterInterface
{

    protected $em;
    protected $category;
    public function __construct(EntityManagerInterface $em, CategoryRepository $category)
    {
        $this->em = $em;
        $this->category = $category;
    }

    public function supports($data): bool
    {
        return $data instanceof Article;
    }

    public function persist($data)
    {
        
        $data->setImage('http://placehold.it/350x150');
        $data->setCreatedAt(new \DateTime());
        $data->setCategory($this->category->find(8));
        
        $this->em->persist($data);
        $this->em->flush();
        
    }
    public function remove($data)
    {
        $this->em->remove($data);
        $this->em->flush();
    }

}