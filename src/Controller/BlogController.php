<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Contact;
use App\Form\ArticleType;
use App\Form\CommentType;
use App\Form\ContactType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class BlogController extends AbstractController 
{
    /**
     * @Route("/blog", name="blog")
     */
    public function index(ArticleRepository $repo, Request $request, PaginatorInterface $paginator)
    {
        $data = $repo->findAll();

        $articles = $paginator->paginate(
            $data, // Requête contenant les données à paginer (ici nos articles)
            $request->query->getInt('page', 1), // Numéro de la page en cours, passé dans l'URL, 1 si aucune page
            5 // Nombre de résultats par page
        );

        return $this->render('blog/index.html.twig', [
            'controller_name' => 'BlogController',
            'articles' => $articles
        ]);
    }

    /**
     * @Route("/", name="home")
     */
    public function home()
    {
        return $this->render('blog/home.html.twig', [
            'title' => 'Bienvenue sur mon blog !'
        ]);
    }


    /**
     * @Route("/blog/new", name="blog_new")
     * @Route("/blog/{id}/edit", name="blog_edit")
     */
    public function create(Article $article = null, Request $request, EntityManagerInterface $manager)
    {
        if (!$article) {
         $article = new Article();   
        }
        /*
        $form = $this->createFormBuilder($article)
                     ->add('title')
                     ->add('content')
                     ->add('image')
                     ->getForm();
        */

        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {

            if (!$article->getId()) {
                $article->setCreatedAt(new \DateTime());   
            }
            $manager->persist($article);
            $manager->flush();
            return $this->redirectToRoute('blog_show',[
                'id' => $article->getId()
            ]);
        }


        /*
        dump($request);
        
        if ($request->request->count() > 0) {
            
            $article = new Article();
            $article->setTitle($request->request->get('title'))
                    ->setContent($request->request->get('content'))
                    ->setImage($request->request->get('image'))
                    ->setCreatedAt(new \Datetime());
            $manager->persist($article);
            $manager->flush();
        }*/

        return $this->render("blog/create.html.twig", [
            'form' => $form->createView(),
            'editMode' => $article->getId() !== null
        ]);
    }
    /**
     * @Route("/blog/{id}", name="blog_show")
     */
    public function show(Article $article ,$id, EntityManagerInterface $manager, Request $request ){

        $comment = new Comment();

        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new \DateTime())
                    ->setArticle($article);

            $manager->persist($comment);
            $manager->flush();

            return $this->redirectToRoute('blog_show', [
                'id' => $article->getId()
            ]);
        }

        return $this->render('blog/show.html.twig',[
            'article' => $article,
            'commentFom' => $form->createView()
        ]);

    }

    /**
     * @Route("/contact", name="blog_contact")
     */
    public function contact(Request $request, EntityManagerInterface $manager, \Swift_Mailer $mailer)
    {
        $contact = new Contact();

        $formContact = $this->createForm(ContactType::class, $contact);
        $formContact->handleRequest($request);

        if ($formContact->isSubmitted() && $formContact->isValid()) {
            
            // on insere dans la base de données
            $contact->setCreatedAt(new \DateTime());            
            $manager->persist($contact);
            $manager->flush();

            // on cree le mail
            $sendContact = $formContact->getData();
            $message = (new \Swift_Message('Nouveau contact'))
                     ->setFrom($contact->getEmail(), $contact->getName())
                     ->setTo('mld5@gmail.com', 'mld')
                     ->setBody(
                         $this->renderView(
                             'emails/contact.html.twig', compact('contact')
                         ),
                         'text/html'
                        );
            // on envoie le message
            $mailer->send($message);                    
                         
            $this->addFlash('message', "Votre message a bien été envoyé");
        }

        return $this->render('blog/contact.html.twig', [
            'formContact' => $formContact->createView()
        ]);
        
    }
}
