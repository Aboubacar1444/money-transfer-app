<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\User1Type;
use App\Form\User2Type;
use App\Repository\SocietyRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/user')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class UserController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em){}
    #[Route(path: '/', name: 'user_index', methods: ['GET'])]
    public function index(SocietyRepository $societyRepository,UserRepository $userRepository): Response
    {
        //redirectRoute to dashboard
        return $this->redirectToRoute('dashboard');


        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
            'society'=>$societyRepository->findAll(),
        ]);
    }

    #[Route(path: '/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function new(SocietyRepository $societyRepository,Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(User1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->em;
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
            'society'=>$societyRepository->findAll(),
        ]);
    }

    #[Route(path: '/{id}', name: 'user_show', methods: ['GET'])]
    public function show(SocietyRepository $societyRepository,User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
            'society'=>$societyRepository->findAll(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function edit(SocietyRepository $societyRepository, Request $request, User $user): Response
    {
        $form = $this->createForm(User2Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            return $this->redirectToRoute('dashboard', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
            'society'=>$societyRepository->findAll(),
        ]);
    }

    #[Route(path: '/{id}', name: 'user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->em;
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('dashboard', [], Response::HTTP_SEE_OTHER);
    }
}
