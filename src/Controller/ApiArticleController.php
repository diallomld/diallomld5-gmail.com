<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiArticleController extends AbstractController
{
    /**
     * @Route("/api/article", name="api_article_index", methods={"GET"})
     */
    public function index(ArticleRepository $articleRepository)
    {
        return $this->json($articleRepository->findAll(), 200, [], ['groups' => 'article:read']); 
    }
    /**
     * @Route("/api/article/{id}", name="api_article_show", methods={"GET"})
     */
    public function getById(Article $article, ValidatorInterface $validator)
    {
        try {
            $erros = $validator->validate($article);
            if (count($erros) > 0) {
                return $this->json($erros, 400);
            }
            return $this->json($article, 200, [], ['groups' => 'article:read']);

        } catch (NotFoundHttpException $e) {

            return $this->json([
                'satus' => 404,
                'message' => "$e->getMessage()"
            ], 404);
        } 
    }
    /**
     * @Route("/api/article", name="api_article_store", methods={"POST"})
     */
    public function store(Request $request, SerializerInterface $serializer,
     EntityManagerInterface $em, 
    CategoryRepository $categoryRepository, ValidatorInterface $validator)
    {
        $category = $categoryRepository->find(8);
        $jsonRecu = $request->getContent();

        try {
            $article = $serializer->deserialize($jsonRecu, Article::class, 'json')
            ->setImage('http://placehold.it/350x150')
            ->setCreatedAt(new \DateTime())
            ->setCategory($category);

            // je valide l'objet
            $erros = $validator->validate($article);
            if (count($erros) > 0) {
                return $this->json($erros, 400);
            }
            $em->persist($article);
            $em->flush();

            return $this->json($article, 201, [], ['groups' => 'article:read']);
    
        } catch (NotEncodableValueException $e) {
            return $this->json([
                'status' => 400,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    /**
     * @Route("/api/article/{id}", name="api_article_edit", methods={"PUT"})
     */
    public function editArticle(? Article $article, Request $request, SerializerInterface $serializer, EntityManagerInterface $em, CategoryRepository $categoryRepository, ValidatorInterface $validator)
    {
        $category = $categoryRepository->find(8);
        $donnees = json_decode($request->getContent());

        try {
            $code = 200;
            if (!$article) {
                $article = new Article();
                $code = 201;
            }
            $article->setImage('http://placehold.it/350x150')
            ->setCreatedAt(new \DateTime())
            ->setCategory($category)
            ->setTitle($donnees->title)
            ->setContent($donnees->content);

            // je valide l'objet
            $erros = $validator->validate($article);
            if (count($erros) > 0) {
                return $this->json($erros, 400);
            }
            $em->persist($article);
            $em->flush();

            return $this->json($article, $code, [], ['groups' => 'article:read']);
    
        } catch (NotEncodableValueException $e) {
            return $this->json([
                'status' => 400,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @Route("api/article/delete/{id}", name="api_article_delete", methods={"DELETE"})
     */
    public function deleteArticle(Article $article, EntityManagerInterface $em)
    {
        $em->remove($article);
        $em->flush();

        return $this->json([
            'status' => 200,
            'message' => "L'article à été supprimer"
        ], 200);
    }
}
