<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ClientController extends AbstractController
{
    #[Route('/client', name: 'app_client')]
    public function index(ClientRepository $cr): Response//ClientRepository = ClientManager
    {
        $clients=$cr->findAll();
       // dd($clients); // dd = dump et die

        return $this->render('client/index.html.twig', [// render = generatePage
            'clients' => $clients,
            'nbre'=>count($clients),
        ]);
    }


    #[Route('/client/delete/{id}', name: 'app_client_delete')]
    public function delete(EntityManagerInterface $em,$id)
    {

        $client=$em->getRepository(Client::class)->find($id);
        $em->remove($client);
        $em->flush();
        return $this->redirectToRoute("app_client");
    }

    #[Route('/client/edit/{id}', name:"app_client_edit", methods:["POST","GET"])]
    public function edit(EntityManagerInterface $em, ClientRepository $cr, $id,Request $request){

        $id=(int) $id;

        if($id){// $id est différent de 0 ou null

            $client=$cr->find($id);
            // ou $client=$em->getRepository(Client::class)->find($id);
        }
        else{
            $client=new Client();
        }
        //---------creation du form à partir de ClientType sur l'entity Client = $client
        $form=$this->createForm(ClientType::class,$client);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            $em->persist($client); // hidrater l'entité $client
            $em->flush(); // enregistrer les données saisie dans la bdd

            return $this->redirectToRoute("app_client");
        }

        return $this->render("client/form.html.twig",[
            'form'=>$form->createView(),
        ]);
    }
}
