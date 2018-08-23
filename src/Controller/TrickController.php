<?php
/**
 * This file is part of the 6th Project.
 *
 * Philippe Traon <ptraon@gmail.com>
 */

namespace App\Controller;

//use App\Entity\Category;
use App\Entity\Category;
use App\Entity\Comment;
//use App\Entity\Media;
use App\Entity\User;
use App\Entity\Trick;
use App\Form\CommentType;
use App\Form\ModifyTrickType;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\MediaRepository;
use App\Repository\TrickRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Symfony\Component\Form\Extension\Core\Type\DateType;
//use Symfony\Component\Form\Extension\Core\Type\SubmitType;
//use Symfony\Component\Form\Extension\Core\Type\TextareaType;
//use Symfony\Component\Form\Extension\Core\Type\TextType;
//use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Persistence\ObjectManager;
use App\Form\AddTrickType;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 *  This controller manages all tricks : show, add, modify and delete
 *
 * Class TrickController
 * @package App\Controller
 *
 * @author Philippe Traon <ptraon@gmail.com>
 */
class TrickController extends AbstractController
{
    /**
     * Show one trick, its details and its comments
     *
     * @Route("/trick/{id}", name="trickpage")
     */
    function trickPage($id,  TrickRepository $repoTrick, CommentRepository $repoComment, MediaRepository $repoMedia, UserRepository $repoUser, Request $request, ObjectManager $manager)
    {
        // Trouver un trick grâce à son id
        $trick = $repoTrick->find(['id' => $id]);
        // Trouver tous les commentaires
        $comments = $repoComment->findAll();
                //$catRepo = $em->getRepository(Category::class);
                // Je ne passe plus par entitymanager mais directement par le repository....
        //$categories = $repoCategory->findOneBy(['name' => $name]);
                //$mediaRepo = $em->getRepository(Media::class);
                // Je ne passe plus par entitymanager mais directement par le repository....
        $media = $repoMedia->findAll();
        $medium = $repoMedia->findOneBy(['url' => 'https://image.redbull.com/rbcom/010/2015-04-15/1331717228402_2/0010/1/1500/1000/1/billy-morgan-lands-first-ever-snowboard-quad-cork.jpg']);
                //$repo= $em->getRepository(User::class);
                // Je ne passe plus par entitymanager mais directement par le repository....
        $author = $repoUser->findOneBy(['username' => 'Philippe Traon']);

        $comment = new Comment();
        $comment->setAuthor($this->getUser());

        //$comment->setTrick($trick);
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
                // Analyse de la requête envoyée via le formulaire CommentType basé sur l'objet $comment créé juste avant

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setCreatedAt(new \DateTime())
                ->setTrick($trick);
            //$trick->addComment($comment);

            $manager->persist($comment);
            $manager->flush();
            // Flash messages are used to notify the user about the result of the
            // actions. They are deleted automatically from the session as soon
            // as they are accessed.
            // See https://symfony.com/doc/current/book/controller.html#flash-messages
            //$this->addFlash('success', 'post.created_successfully');

            return $this->redirectToRoute('trickpage', ['id' => $trick->getId()]);
        }

        return $this->render('Trick/trick.html.twig', [
            'trick' => $trick,
            'comments' => $comments,
            //'category' => $categories,
            'media' => $media,
            'medium' => $medium,
            'author' => $author,
            'commentForm' => $form->createView(),

        ]);
        // Est-ce-que j'ai besoin de tout ? Je peux utiliser un paramconverter ?
    }

    /**
     * Add a trick
     *
     * @Route("/add/trick", name="createtrickpage")
     *
     */
    /*@Route("/addtrick", name="addtrickpage")*/
    public function add(Trick $trick = null, Request $request, ObjectManager $manager, CategoryRepository $repoCategory)
    {
        if (!$trick) {
        $trick = new Trick();
        $trick->setAuthor($this->getUser());
        $trick->setCreatedAt(new \DateTime());
        $trick->setSlug('test');
        }
        //$categories = $repoCategory->findOneBy(['name' => $name]);

        $form = $this->createForm(AddTrickType::class, $trick);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $manager->persist($trick);
            $manager->flush();

            //if (!$trick->getId()) {
                $this->addFlash('success', 'Le trick a bien été ajouté!');
            //}
            //else
                //$this->addFlash('success', 'Le trick a bien été modifié!');
            return $this->redirectToRoute('trickpage', ['name' => $trick->getCategory(), 'id' => $trick->getId()]);
        }

        return $this->render(
            'Trick/createtrick.html.twig', [
                'formAddTrick' => $form->createView()
                //'editMode' => $trick->getId() !== null
            ]);
    }

    /**
     * Modify a trick
     *
     * @Route("/modifytrick/{id}", name="modifytrickpage")
     */
    function modifyTrickPage(int $id, Trick $trick, Request $request, ObjectManager $manager)
    {
        $trick = $this->getDoctrine()->getRepository(Trick::class)->find($id);
        $form = $this->createForm(ModifyTrickType::class, $trick);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $manager->persist($trick);
            $manager->flush();

            //if (!$trick->getId()) {
            $this->addFlash('success', 'Le trick a bien été modifié!');
            //}
            //else
            //$this->addFlash('success', 'Le trick a bien été modifié!');
            return $this->redirectToRoute('trickpage', ['name' => $trick->getCategory(), 'id' => $trick->getId()]);
        }
        return $this->render('Trick/modifytrick.html.twig', [
            'trick' => $trick,
            'formModifyTrick' => $form->createView()
        ]);
        // Idem ! Ne serait-ce pas mieux d'utiliser un paramconverter ?
    }

    /**
     * Delete a trick
     *
     * @Route("/delete/{id}", name="deletetrick")
     *
     * @return Response
     */
    public function deleteTrick($id, EntityManagerInterface $em)
    {
        $repository = $em->getRepository(Trick::class);
        $trick = $repository->find($id);

        //if(!$trick)
            //throw $this->createNotFoundException('No trick found for id'.$id);

        //$em = $this->getDoctrine()->getManager();
        $em->remove($trick);
        $em->flush();

        $this->addFlash('message', 'Le trick a bien été supprimé');

        return $this->redirectToRoute('homepage');
        // Idem : paramconverter ?
    }
}
