<?php

namespace App\Controller;

use App\Entity\Agency;
use App\Entity\User;
use App\Form\Agency1Type;
use App\Form\User1Type;
use App\Repository\AgencyRepository;
use App\Repository\SocietyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/agency')]
class AgencyController extends AbstractController
{
    public function __construct (private EntityManagerInterface $em){}
    #[Route(path: '/', name: 'agency_index', methods: ['GET', 'POST', 'PUT', 'DELETE'])]
    public function index(Request $request, UserPasswordHasherInterface $passwordEncoder, SocietyRepository $societyRepository, AgencyRepository $agencyRepository): Response
    {
        $user = new User();
        $form = $this->createForm(User1Type::class, $user);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $user=$form->getData();
            $password = $passwordEncoder->hashPassword($user,$user->getPassword());
            $user->setPassword($password);
            $access=$request->get('access');
            if($access=="Admin"){
                $user->setRoles(['ROLE_SOUS_ADMIN']);
                $user->setType("Administrateur");
            }
            elseif ($access=="Checker") {
                $user->setRoles(['ROLE_CHECKER']);
                $user->setType("Checker");
            }

            else{
                $user->setRoles(['ROLE_AGENT']);
                $user->setType("Agent");
            }


            $this->em->persist($user);
            $this->em->flush();
            $this->addFlash("success", "Compte crée avec succès!");
            return $this->redirectToRoute('dashboard');
        }
        return $this->render('agency/index.html.twig', [
            'form' => $form->createView(),
            'agencies' => $agencyRepository->findAll(),
            'society'=> $societyRepository->findAll(),
        ]);
    }

    #[Route(path: '/new', name: 'agency_new', methods: ['GET', 'POST'])]
    public function new(SocietyRepository $societyRepository,Request $request): Response
    {
        $agency = new Agency();
        $form = $this->createForm(Agency1Type::class, $agency);
        $form->handleRequest($request);
        $society=$societyRepository->findAll();
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->em;
            foreach ($society as $s) {
               $caisseg=$s->getCaisse();
               $agencycaisse=$agency->getCaisse();
               $caisseg+=$agencycaisse;
               $s->setCaisse($caisseg);
            }
            $entityManager->persist($agency);
            $entityManager->flush();
            $this->addFlash("success", "Agence crée avec succès!");
            return $this->redirectToRoute('agency_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('agency/new.html.twig', [
            'agency' => $agency,
            'form' => $form,
            'society'=>$societyRepository->findAll(),
        ]);
    }

    #[Route(path: '/{id}', name: 'agency_show', methods: ['GET'])]
    public function show(SocietyRepository $societyRepository, Agency $agency): Response
    {
        return $this->render('agency/show.html.twig', [
            'agency' => $agency,
            'society'=>$societyRepository->findAll(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'agency_edit', methods: ['GET', 'POST'])]
    public function edit(SocietyRepository $societyRepository, Request $request, Agency $agency): Response
    {
        $agencyy=$agency;
        $agencycaisse=$agencyy->getCaisse();
        $form = $this->createForm(Agency1Type::class, $agency);
        $form->handleRequest($request);
        $society=$societyRepository->findAll();
        if ($form->isSubmitted() && $form->isValid()) {
            // if ($form['caisse']->getData()!='') {
            //     $upcaisse=$form['caisse']->getData();

            //     foreach ($society as $s) {
            //         $caisseg=$s->getCaisse();
            //         $caisseg+=$upcaisse;
            //         $agencycaisse+=$upcaisse;
            //         $s->setCaisse($caisseg);
            //         $agency->setCaisse($agencycaisse);
            //     }

            // }
            $this->em->flush();
            $this->addFlash("success", "Agence modifiée avec succès!");
            return $this->redirectToRoute('agency_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('agency/edit.html.twig', [
            'agency' => $agency,
            'form' => $form,
            'society'=>$societyRepository->findAll(),
        ]);
    }

    #[Route(path: '/{id}', name: 'agency_delete', methods: ['POST'])]
    public function delete(SocietyRepository $societyRepository, Request $request, Agency $agency): Response
    {
        if ($this->isCsrfTokenValid('delete'.$agency->getId(), $request->request->get('_token'))) {
            $society=$societyRepository->findAll();
             foreach ($society as $s) {
                 $caisseg=$s->getCaisse();
                 $agencycaisse=$agency->getCaisse();
                 $caisseg-=$agencycaisse;
                 $s->setCaisse($caisseg);
             }

            $entityManager = $this->em;
            $entityManager->remove($agency);
            foreach ($agency->getUsers() as $u){
                $u->setAgency(null);
            }
            $entityManager->flush();
        }
        $this->addFlash("success", "Agence supprimée avec succès!");
        return $this->redirectToRoute('agency_index', [], Response::HTTP_SEE_OTHER);
    }
}
