<?php

namespace App\Controller;

use App\Entity\Agency;
use App\Entity\Transfert;
use App\Entity\User;
use App\Form\TransfertcType;
use App\Form\TransfertType;
use App\Repository\AgencyRepository;
use App\Repository\SocietyRepository;
use App\Repository\TransfertRepository;
use App\Repository\UserRepository;
use App\Service\WhatsAppService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER")
 */
#[Route(path: '/transfert')]
class TransfertController extends AbstractController
{
    private WhatsAppService $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService, private EntityManagerInterface $em)
    {
        $this->whatsAppService = $whatsAppService;
    }

    #[Route(path: '/new', name: 'transfert')]
    public function index(Request $request, AgencyRepository $agencyRepository, SocietyRepository $societyRepository, UserRepository $userRepository): Response
    {
        function generateCode($limit): string
        {
            $code = '';
            for($i = 0; $i < $limit; $i++) { $code .= mt_rand(1, 9); }
            return $code;
        }
        $society=$societyRepository->findAll();


        $transfert = new Transfert();
        if ($request->get('id') && $request->get('cl')) {
            $form = $this->createForm(TransfertcType::class, $transfert, [
                'userAgency' => $this->getUser()->getAgency(), // Passe l'agence de l'utilisateur
            ]);
            $client=$userRepository->findOneBy(['id'=>$request->get('id'), 'username'=>$request->get('cl')]);
            $clientcaisse=$client->getSolde();
        }

        elseif (!$request->get('id') && !$request->get('cl')) {
            $client=false;
            $clientcaisse=false;
            $form = $this->createForm(TransfertType::class, $transfert, [
                'userAgency' => $this->getUser()->getAgency(), // Passe l'agence de l'utilisateur
            ]);
        }
        else{
            $this->addFlash("error", "Attention ce client n'existe pas!");
            return $this->redirectToRoute('dashboard');
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $agencySetByAdmin = $request->request->get('agencySetByAdmin');

            $secretCodeId = generateCode(10);
            if ($this->getUser()->getAgency()) {
                $agencysender=$agencyRepository->findOneBy(['name'=>$this->getUser()->getAgency()->getName()]);
            }else{
                $agencysender = $agencyRepository->find($agencySetByAdmin) ?? false;
            }


            if($this->getUser()->getAgency() || $agencysender) {
                $agency=$this->getUser()->getAgency()?->getName() ?? $agencysender->getName();
                $transagency=$agencysender->getCaisse();
                $transagency+=$transfert->getMontant();
                $agencysender->setCaisse($transagency);
                $transfert->setAgency($agency);
                foreach ($society as $s) {
                    $caisse=$s->getCaisse();
                    $caisse+=$transfert->getMontant();
                    $s->setCaisse($caisse);
                }
            }else{
                foreach ($society as $s) {
                    $caisse=$s->getCaisse();
                    $caisse+=$transfert->getMontant();
                    $s->setCaisse($caisse);
                }
            }
            if ($transfert->getFrais() == null || $transfert->getFrais() == "") $transfert->setFrais(0);
            if($transfert->getFrais() !== null) {
                    $frais=$transfert->getFrais();
                    if ($frais !== 0 ) {
                        if($request->get('status')){
                          $status=$request->get('status');
                          $transfert->setPaid($status);
                        }else {
                            $status="NON";
                            $transfert->setPaid($status);
                        }
                        if ($status == "OUI") {
                            foreach ($society as $s) {
                                $caisse=$s->getCaisse();
                                $caisse+=$frais;
                                $s->setCaisse($caisse);
                            }
                        }
                    }
            }



            $transferDestination = $transfert->getTransagency()->getName();
            if ($transferDestination == "CHINE") {
                $amountToPaid = sprintf('%.3f', $transfert->getMontant() / 8.60);
                $device = "FCFA";
                $fraisDevice = "FCFA";
                $amountToPaidDevice ="YEN";
            }
            elseif ($transferDestination == "MALI") {
                $amountToPaid = sprintf('%.3f', $transfert->getMontant() * 8.60);
                $device = "YEN";
                $fraisDevice = "YEN";
                $amountToPaidDevice ="FCFA";
            }
            else {
                $amountToPaid = $transfert->getMontant();
                $device = "FCFA";
                $fraisDevice = "FCFA";
                $amountToPaidDevice ="FCFA";
            }
            $amountToPaid = (float) $amountToPaid;

            $bodyDestinateur = "Transfert d'argent -> `$transferDestination`. \n".
                "Total:". "`{$transfert->getMontant()}`"." $device. \n".
                "Frais: "."`{$transfert->getFrais()}`"." $fraisDevice. \n".
//                    "Frais payé: "."`{$transfert->getPaid()}`".". \n".
                "Montant à payer: "."`$amountToPaid`"." $amountToPaidDevice. \n".
                "A : `{$transfert->getDestinataire()}` \n".

                "Bien à vous "."`{$transfert->getDestinateur()}`".
                ".\nTRAORE - SERVICE";

            $bodyDestinataire = "Transfert d'argent -> `$transferDestination`. \n".
                "Total: "."`{$transfert->getMontant()}`"." $device. \n".
                "Frais: "."`{$transfert->getFrais()}`"." $fraisDevice. \n".
//                "Frais payé à l'envoi: "."`{$transfert->getPaid()}`".". \n".
                "Montant à payer: "."`$amountToPaid`"." $amountToPaidDevice. \n".
                "Code de retrait: `$secretCodeId`. \n".
                "Bien à vous "."`{$transfert->getDestinataire()}`".
                ".\nTRAORE - SERVICE";

            $this->whatsAppService->sendMessage($transfert->getTelsender(), $bodyDestinateur, "TRAORE SERVICE");
            $this->whatsAppService->sendMessage($transfert->getTel(), $bodyDestinataire, "TRAORE SERVICE");

                $transfert->setAgent($this->getUser()->getFullname());
                $transfert->setSecretid($secretCodeId);
                $transfert->setSentAt(new \DateTimeImmutable());
                $transfert->setTransagency($transfert->getTransagency());

                // dd($transfert);
                $this->em->persist($transfert);
                $this->em->flush();
                if ($client) {
                    $this->addFlash("success", "Le transfert d'argent a été effectué avec succès.");
                }
                return $this->redirectToRoute('sent',['id'=> $transfert->getId()],Response::HTTP_SEE_OTHER);

            }
        return $this->render('transfert/index.html.twig', [
            'society' => $societyRepository->findAll(),
            'agencies' => $agencyRepository->findAll(),
            'form' => $form->createView(),

        ]);
    }
    #[Route(path: '/sent/{id}', name: 'sent')]
    public function sent(Transfert $transfert, SocietyRepository $societyRepository): Response
    {
        $transferDestination = $transfert->getTransagency()->getName();
        if ( $transferDestination == "CHINE") {
            $amountToPaid = sprintf('%.3f', $transfert->getMontant() / 8.60);
            $device = "FCFA";
            $fraisDevice = "FCFA";
            $amountToPaidDevice ="YEN";
        }
        elseif ($transferDestination == "MALI" || $transferDestination == "CI") {
            $amountToPaid = sprintf('%.3f', $transfert->getMontant() * 8.60);
            $device = "YEN";
            $fraisDevice = "YEN";
            $amountToPaidDevice ="FCFA";
        }
        else {
            $amountToPaid = $transfert->getMontant();
            $device = "FCFA";
            $fraisDevice = "FCFA";
            $amountToPaidDevice ="FCFA";
        }
        return $this->render('transfert/sent.html.twig', [
            'society'=>$societyRepository->findAll(),
            'transfert'=>$transfert,
            'amountToPaid' => $amountToPaid,
            'device' => $device,
            'fraisDevice' => $fraisDevice,
            'amountToPaidDevice' => $amountToPaidDevice,
        ]);
    }

    #[Route(path: '/getagency/{id}', name: 'getagency')]
    public function getagency(Agency $agency): Response
    {
        $solde=$agency->getCaisse();
        return new JsonResponse($solde);
    }

    #[Route(path: '/receive/{secretid}/{id}', name: 'receive')]
    public function receive(Request $request, Transfert $transfert, SocietyRepository $societyRepository): Response
    {
        $society=$societyRepository->findAll();
        if ($request->get('confirm') == 'gotit') {
            $transfert->setTransagent($this->getUser()->getFullname());
            $transfert->setReceveAt(new \DateTimeImmutable());
            $transfert->setFacture("Ok");
            if ($this->getUser()->getAgency()) {
                $agencycaisse=$transfert->getTransagency()->getCaisse();
                $agencycaisse-=$transfert->getMontant();
                $transfert->getTransagency()->setCaisse($agencycaisse);
            }


            foreach ($society as $s) {
                $caisse=$s->getCaisse();
                $caisse-=$transfert->getMontant();
                $s->setCaisse($caisse);
            }


            if($transfert->getFrais() !== null) {
                $frais=$transfert->getFrais();
                if ($frais !== 0 ) {
                    if($transfert->getPaid()){
                      $status=$transfert->getPaid();
                    }
                    if ($status === "NON") {
                        $newmontant=$transfert->getMontant() - $transfert->getFrais();
                        $transfert->setAmountToPaid($newmontant);
                        foreach ($society as $s) {
                            $caisse=$s->getCaisse();
                            $caisse+=$frais;
                            $s->setCaisse($caisse);
                        }
                    }
                    else{
                        $newmontant=0;
                        $transfert->setAmountToPaid($transfert->getMontant());
                    }
                }
            }
            $transferDestination = $transfert->getTransagency()->getName();

            if ($transferDestination == "CHINE") {
                $amountToPaid = sprintf('%.3f', $transfert->getMontant() / 8.60);
                $device = "FCFA";
                $fraisDevice = "FCFA";
                $amountToPaidDevice ="YEN";
            }
            elseif ($transferDestination == "MALI" || $transferDestination == "CI") {
                $amountToPaid = sprintf('%.3f', $transfert->getMontant() * 8.60);
                $device = "YEN";
                $fraisDevice = "YEN";
                $amountToPaidDevice ="FCFA";
            }
            else {
                $amountToPaid = $transfert->getMontant();
                $device = "FCFA";
                $fraisDevice = "FCFA";
                $amountToPaidDevice ="FCFA";
            }
            $amountToPaid = (float) $amountToPaid;


            $bodyDestinateur = "Retrait d'argent -> `$transferDestination`. \n".
                "Total:". "`{$transfert->getMontant()}`"." $device. \n".
                "Frais de retrait: "."`{$transfert->getFrais()}`"." $fraisDevice. \n".
//                "Montant payé: "."`{$transfert->getAmountToPaid()}`"." FCFA. \n".
                "Montant payé: "."`{$amountToPaid}`"." $amountToPaidDevice. \n".
                "Bien à vous "."`{$transfert->getDestinateur()}`".
                ".\nTRAORE - SERVICE";

            $bodyDestinataire = "Retrait d'argent -> `$transferDestination`. \n".
                "Total: "."`{$transfert->getMontant()}`"." $device. \n".
                "Frais de retrait: "."`{$transfert->getFrais()}`"." $fraisDevice. \n".
//                "Montant payé: "."`{$transfert->getAmountToPaid()}`"." FCFA. \n".
                "Montant payé: "."`{$amountToPaid}`"." $amountToPaidDevice. \n".
                "Bien à vous "."`{$transfert->getDestinataire()}`".
                ".\nTRAORE - SERVICE";
            $this->whatsAppService->sendMessage($transfert->getTelsender(), $bodyDestinateur, "TRAORE SERVICE");
            $this->whatsAppService->sendMessage($transfert->getTel(), $bodyDestinataire, "TRAORE SERVICE");

            $this->em->flush();
            $this->addFlash("success", "Rétrait effectué avec succès.");
            return $this->redirectToRoute('receved',['id'=> $transfert->getId(), 'newamount'=>$newmontant],Response::HTTP_SEE_OTHER);
        }

        $transferDestination = $transfert->getTransagency()->getName();

        if ($transferDestination == "CHINE") {
            $amountToPaid = sprintf('%.3f', $transfert->getMontant() / 8.60);
            $device = "FCFA";
            $fraisDevice = "FCFA";
            $amountToPaidDevice ="YEN";
        }
        elseif ($transferDestination == "MALI" || $transferDestination == "CI") {
            $amountToPaid = sprintf('%.3f', $transfert->getMontant() * 8.60);
            $device = "YEN";
            $fraisDevice = "YEN";
            $amountToPaidDevice ="FCFA";
        }
        else {
            $amountToPaid = $transfert->getMontant();
            $device = "FCFA";
            $fraisDevice = "FCFA";
            $amountToPaidDevice ="FCFA";
        }

        return $this->render('transfert/receve.html.twig', [
            'society'=>$societyRepository->findAll(),
            'transfert'=>$transfert,
            'amountToPaid'=>$amountToPaid,
            'device'=>$device,
            'fraisDevice'=>$fraisDevice,
            'amountToPaidDevice'=>$amountToPaidDevice,
        ]);
    }

    #[Route(path: '/receved/{id}', name: 'receved')]
    public function receved(Request $request, Transfert $transfert, SocietyRepository $societyRepository): Response
    {
        $newamount = $request->get('newamount') ? $request->get('newamount') : false;
        $transferDestination = $transfert->getTransagency()->getName();
        if ( $transferDestination == "CHINE") {
            $amountToPaid = sprintf('%.3f', $transfert->getMontant() / 8.60);
            $device = "FCFA";
            $fraisDevice = "FCFA";
            $amountToPaidDevice ="YEN";
        }
        elseif ($transferDestination == "MALI" || $transferDestination == "CI") {
            $amountToPaid = sprintf('%.3f', $transfert->getMontant() * 8.60);
            $device = "YEN";
            $fraisDevice = "YEN";
            $amountToPaidDevice ="FCFA";
        }
        else {
            $amountToPaid = $transfert->getMontant();
            $device = "FCFA";
            $fraisDevice = "FCFA";
            $amountToPaidDevice ="FCFA";
        }

        return $this->render('transfert/receved.html.twig', [
            'society'=>$societyRepository->findAll(),
            'transfert'=>$transfert,
            'newamount'=>$newamount,
            'amountToPaid'=>$amountToPaid,
            'device'=>$device,
            'fraisDevice'=>$fraisDevice,
            'amountToPaidDevice'=>$amountToPaidDevice,
        ]);
    }

}
