<?php

namespace App\Controller;

use App\Entity\AlimentationCaisse;
use App\Entity\Society;
use App\Entity\User;
use App\Form\CaisseType;
use App\Form\ClientType;
use App\Form\PassType;
use App\Form\User1Type;
use App\Form\UserType;
use App\Repository\AlimentationCaisseRepository;
use App\Repository\OperationsRepository;
use App\Repository\SocietyRepository;
use App\Repository\TransfertRepository;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @IsGranted("ROLE_USER")
 */
#[Route(path: '/dashboard')]
class DashboardController extends AbstractController
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    #[Route(path: '/', name: 'dashboard')]
    public function index(Request $request, TransfertRepository $transfertRepository,  TokenStorageInterface $tokenStorageInterface, UserPasswordHasherInterface $passwordEncoder, SocietyRepository $societyRepository, UserRepository $userRepository): Response
    {
        $em= $this->managerRegistry->getManager();
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


            $em->persist($user);
            $em->flush();
            $this->addFlash("success", "Compte crée avec succès!");
            return $this->redirectToRoute('dashboard');
        }

        $formclient = $this->createForm(ClientType::class, $user);
        $formclient->handleRequest($request);
        if ($formclient->isSubmitted() && $formclient->isValid()) {
            $user=$formclient->getData();
            $password = $passwordEncoder->hashPassword($user,'123456');
            $username=substr($user->getFullname(),0,9).random_int(12,1000);
            $username=preg_replace('/\s+/', '', $username);
            $user->setUsername($username);
            $user->setPassword($password);
            $user->setRoles(['ROLE_CLIENT']);
            $user->setType("Client");

            $em->persist($user);
            $em->flush();
            $this->addFlash("success", "Client ajouté avec succès!");
            return $this->redirectToRoute('dashboard');
        }

        $formpass = $this->createForm(PassType::class, $user);
        $formpass->handleRequest($request);
        if ($formpass->isSubmitted() && $formpass->isValid()) {
            $agent = $this->getUser();
            $password = $passwordEncoder->hashPassword($user, $formpass['password']->getData());
            $agent->setPassword($password);
            $em->flush();
            $this->addFlash("success", "Mot de passe modifié avec succès!");
            return $this->redirectToRoute('dashboard');
        }
        $Company= new Society();
        $society=$societyRepository->findAll();

        $formcaisse = $this->createForm(CaisseType::class, $Company);
        $formcaisse->handleRequest($request);

        if ($formcaisse->isSubmitted() && $formcaisse->isValid()) {
            foreach ($society as $k) {
                $new=$formcaisse['caisse']->getData();

                $alimcaisse = new AlimentationCaisse();
                if($new){
                    $caisse=$k->getCaisse();
                    $caisse+=$new;
                    $k->setCaisse($caisse);
                    $alimcaisse->setMontant($new);
                    $alimcaisse->setType("FCFA");
                    if ($request->get('date')) {
                        $alimcaisse->setCreatedAt(new \DateTimeImmutable(date($request->get('date'))));
                    }else {
                        $alimcaisse->setCreatedAt(new \DateTimeImmutable(date('Y-m-d H:i:s')));
                    }

                }

            }
            $em->persist($alimcaisse);
            $em->flush();
                $this->addFlash("success", "Mise à jour de la caisse avec succès!");
                return $this->redirectToRoute('dashboard');
        }

        $agent=$userRepository->findAll();
        if ($this->isGranted("ROLE_ADMIN")) {
            $agents=$userRepository->findBy(
                [
                    'type'=>["Checker", "Agent", "Administrateur", null],
                ]
            );

            $transfert=$transfertRepository->findAll();
        }
        elseif ($this->isGranted("ROLE_SOUS_ADMIN")) {
            $agents=$userRepository->findBy(
                [
                    'type'=>["Checker",  "Agent"],
                ]
            );

            $transfert=$transfertRepository->findAll();
        }
        elseif ($this->isGranted("ROLE_CHECKER")) {
            $agents=$userRepository->findBy(
                [
                    'type'=> "Agent" ,
                ]
            );

            $transfert=$transfertRepository->findAll();
        }
        else{
            $agents=false;
        }
        if ($this->isGranted("ROLE_AGENT")) {

            $transfert=$transfertRepository->findBy(
                ['agency'=>$this->getUser()->getAgency()->getName()],
                ['id'=>'DESC'],
            );
            if (!$transfert) {
                $transfert=$transfertRepository->findBy(
                    ['transagency'=>$this->getUser()->getAgency()],
                    ['id'=>'DESC'],
                );
            }
        }

        if ($request->get('secretid')) {
            $secretid=$request->get('secretid');
            if ($transfertRepository->findOneBy(['secretid'=>$secretid])) {
                $transfert=$transfertRepository->findOneBy(['secretid'=>$secretid]);
                if ($transfert->getFacture()) {
                    $this->addFlash("error", "Le rétrait d'argent a déjà été effectué.");
                    return $this->redirectToRoute('dashboard');
                }else{
                    $this->addFlash("success", "Le secret ID est valide. Veuillez confirmer le rétrait.");
                    return $this->redirectToRoute('receive',['secretid'=> $secretid,'id'=>$transfert->getId()],Response::HTTP_SEE_OTHER);
                }
            }
            else {
                $this->addFlash("error", "Le secret ID est invalide. Veuillez le vérifier.");
                return $this->redirectToRoute('dashboard');
            }
        }
        /*if($request->get('id') && $request->get('type')){
            $client = $userRepository->findOneBy(['id'=>$request->get('client')]);
            if ($client) {
                $solde_client = $client->getSolde();
            }

            $agencycaisse = $this->getUser()->getAgency() ? $this->getUser()->getAgency()->getCaisse() : false;

            foreach ($society as $s) {
                $caisse = $s->getCaisse();
                if ($delop->getType() == "Dépôt") {
                   $caisse -= $delop->getMontant();
                   if($client){
                       $solde_client -= $delop->getMontant();
                       $client->setSolde($solde_client);
                   }
                   $s->setCaisse($caisse);
                   if ($agencycaisse) {
                      $agencycaisse -= $delop->getMontant();
                      $this->getUser()->getAgency()->setCaisse($agencycaisse);
                   }
                }
                if ($delop->getType() == "Rétrait") {
                    $caisse += $delop->getMontant();
                    if ($client) {
                        $solde_client += $delop->getMontant();
                        $client->setSolde($solde_client);
                    }
                    $s->setCaisse($caisse);
                    if ($agencycaisse) {
                       $agencycaisse += $delop->getMontant();
                       $this->getUser()->getAgency()->setCaisse($agencycaisse);
                    }
                }
            }
            $em->remove($delop);
            $em->flush();
            $this->addFlash('success','Opération supprimée avec succès.');
            return $this->redirectToRoute('dashboard');
        }*/

        // $today= new \DateTime(date('Y-m-d 00:00:00'));
        // $expAt= new \DateTime(date('2021-10-25 00:00:00'));
        $useragent=$_SERVER['HTTP_USER_AGENT'];
        // if ($today >= $expAt) {
        //     return new JsonResponse('Oops something its wrong please make sure all is good.');
        // }
//        if(preg_match('/(tablet|ipad|playbook)|(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))){
//            return new JsonResponse ('Erreur 403: Accèss non configurés.');
//        }



        return $this->render('dashboard/index.html.twig', [
                'form' => $form->createView(),
                'formclient' => $formclient->createView(),
                'formpass' => $formpass->createView(),
                'society' => $society,
                'agents' => $agents,
                'formcaisse' => $formcaisse->createView(),
                'transferts' => $transfert,

            ]);

    }



    #[Route(path: '/daysoperations', name: 'findetat')]
    public function FindTransfertEtat(Request $request, TransfertRepository $transfertRepository, AlimentationCaisseRepository $alimcaisse, SocietyRepository $societyRepository, UserRepository $userRepository): Response
    {
        $defaultDate =new \DateTimeImmutable();
        $date = $request->request->get('date') ?? $defaultDate->format('Y-m-d');

        $society=$societyRepository->findAll();
        $operations=$transfertRepository->findByDate($date);
        $dayTotal = 0;
        $dayTotalChina = 0;
        $dayTotalMali = 0;
        $dayTotalRetrait = 0;
        $dayTotalRetraitChina = 0;
        $dayTotalRetraitMali = 0;
//        $dayTotalRetraitFrais = 0;

        foreach ($operations as $transfert) {
            $dayTotal += $transfert->getMontant();
            if ($transfert->getReceveAt()) {
                $dayTotalRetrait += $transfert->getMontant();
            }
            if ($transfert->getTransagency()->getName() == "CHINE"){
                $dayTotalChina = $dayTotal / 8.60;
                $dayTotalRetraitChina = $dayTotalRetrait / 8.60;
            }else{
                $dayTotalMali=$dayTotal * 8.60;
                $dayTotalRetraitMali = $dayTotalRetrait * 8.60;
            }

        }

        return $this->render('dashboard/etat.html.twig',[
            'operations'=>$operations,
            'society'=>$society,
            'date' => $date,
            'dayTotalChina' => $dayTotalChina,
            'dayTotalMali' => $dayTotalMali,
            'dayTotalRetraitChina' => $dayTotalRetraitChina,
            'dayTotalRetraitMali' => $dayTotalRetraitMali,
        ]);
    }

    #[Route(path: '/caisse-operations', name: 'app_dashboard_findcaisseetat')]
    public function FindCaisseEtat(Request $request, TransfertRepository $transfertRepository, AlimentationCaisseRepository $alimcaisse, SocietyRepository $societyRepository, UserRepository $userRepository): Response
    {
        $defaultDate =new \DateTimeImmutable();
        $date = $request->request->get('date') ?? $defaultDate->format('Y-m-d');
        $society=$societyRepository->findAll();



        return $this->render('dashboard/etatcaisse.html.twig',[
            'society'=>$society,
            'caisseEtat'=>$alimcaisse->findBy(['type'=>['FCFA', ]],['id'=>'DESC']),
        ]);
    }

    #[Route(path: '/getoperations', name: 'getoperations')]
    public function FindOperations(OperationsRepository $operationsRepository): Response
    {
       $agency = $this->getUser()->getAgency() ? $this->getUser()->getAgency()->getId() : null;
        return new JsonResponse($operationsRepository->getOperations($agency));
    }
    #[Route(path: '/getstatus', name: 'getstatus')]
    public function FindStatus(UserRepository $userRepository): Response
    {
        $status=$userRepository->getStatus();
        return new JsonResponse($status);
    }



}
