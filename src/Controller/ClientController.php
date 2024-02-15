<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[ROUTE('/client')]

class ClientController extends AbstractController
{
    #[Route('/', name: 'app_client')]
    public function index(ClientRepository $cr): Response//ClientRepository = ClientManager
    {
        $clients=$cr->findAll();
       // dd($clients); // dd = dump et die

        return $this->render('client/index.html.twig', [// render = generatePage
            'clients' => $clients,
            'nbre'=>count($clients),
        ]);
    }




    #[Route("/export/excel", name:"app_client_export_excel")]
    public function exportExcel(EntityManagerInterface $em):Response
    {

        $file="../public/modele-document/modele-fichier-client.xlsx";
        $spreadsheet=IOFactory::load($file);

        $sheet=$spreadsheet->getActiveSheet();
        $clients=$em->getRepository(Client::class)->findAll();

        $row=4;

        foreach($clients as $client){

            $sheet->insertNewRowBefore($row);
            $sheet->setCellValue("A$row",$client->getNumClient());
            $sheet->setCellValue("B$row",$client->getNomClient());
            $sheet->setCellValue("C$row",$client->getAdresseClient());

            $row++; //$row+=
        } 
        
        
        $row--;
        $nbre=count($clients);
        $sheet->setCellValue("A$row","Nombre de clients :$nbre");

        //--------------------------sauvegarder des données dans le fichier list_clients.xlsx

        $target="../public/partage-document/liste_clients.xlsx";
        $writer=new Xlsx($spreadsheet);
        $writer->save($target);

        //return $this->redirectToRoute("app_client");
        
        echo "Exportation termiiné";

        exit;

    }



    #[ROUTE('/show/{id}', name: 'app_client_show')]
    public function show(ClientRepository $cr,$id)
    {
        $client=$cr->find($id);
        return $this->render("client/show.html.twig",[
            'client'=>$client,
        ]);
    }


    #[Route('/delete/{id}', name: 'app_client_delete')]
    public function delete(EntityManagerInterface $em,$id)
    {

        $client=$em->getRepository(Client::class)->find($id);
        $em->remove($client);
        $em->flush();
        return $this->redirectToRoute("app_client");
    }


    

    #[Route('/edit/{id}', name:"app_client_edit", methods:["POST","GET"])]
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

            $em->persist($client); // preparation de l'entité $client
            $em->flush(); // enregistrer les données saisie dans la bdd

            return $this->redirectToRoute("app_client");
        }

        return $this->render("client/form.html.twig",[
            'form'=>$form->createView(),
        ]);
    }
}
