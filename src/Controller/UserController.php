<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager,UserPasswordHasherInterface $uph): Response //UserPasswordHasherInterface pour hacher les mot de passe
    {
        $myRoles=$entityManager->getRepository(Role::class)->findAll(); //recuperation de tout les roles dans la table Role de la bdd

        $roles=[];// initialisation de tableau pour stoké les roles

        foreach($myRoles as $myRole){// iteration a travers chaque role
            $libelle=$myRole->getLibelle();// recuperation du libelle de chaque role
            $roles[$libelle]=$libelle;// Ajout du libellé du rôle dans le tableau $roles avec la clé et la valeur étant le libellé lui-même.
        }

        $form = $this->createForm(UserType::class, $user);//creation d'un formulaire de type 'UserType' pour l'utilisateur $user
        
       $form
        ->add('roles',ChoiceType::class,[//ajout d'un champ de selection au formulaire
            'label'=>'ROLES:',
            'label_attr'=>['class'=>'lab30'],
            'choices'=>$roles,// definit les option disponible pour le champ
            //[
            //    'ROLE_ADMIN'=>'ROLE_ADMIN',
           //     'ROLE_ASSISTANT'=>'ROLE_ASSISTANT'
           // ],
            'multiple'=>true,//permet d'afficher plusieurs option
            'expanded'=>true,// passer en checkbox
            
        ]);

        $form->handleRequest($request);//gestion de la requete par le formulaire pour traiter les données soumises

        if ($form->isSubmitted() && $form->isValid()) {

            $plainPassword=$form->get('plainPassword')->getData();//recuperation du mot de passe en claire a partir des données soumis ($plainPassword=$_POST['plainPassword'])

            if($plainPassword){//si $plainPassword est soumis

                $password=$uph->hashPassword($user,$plainPassword);//hachage du mot de passe avec l'objet UserPasswordHasherInterface
                $user->setPassword($password);//mettre a jour le mot de passe

            }

            $entityManager->persist($user); //persistance des modification apporter a l'utilisateur
            $entityManager->flush();// enregistrement des modification apporter a la base de donnée

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER); // redirection vers la liste des utilisateur apres l'édition reussi
        }

        return $this->render('user/edit.html.twig', [//Rendu du modèle edit.html.twig avec les données de l'utilisateur et le formulaire.
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
