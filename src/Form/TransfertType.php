<?php

namespace App\Form;

use App\Entity\Agency;
use App\Entity\Transfert;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransfertType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $userAgency = $options['userAgency']; // L'agence de l'utilisateur connecté
        $builder
            ->add('montant', NumberType::class,[
                'label'=>false,
                'attr'=>[
                    'placeholder'=>'Montant à envoyé',
                    'class'=>'',
                ],
                
            ])
            ->add('frais', NumberType::class,[
                'label'=>false,
                'attr'=>[
                    'placeholder'=>"Frais d'envoi",
                    'class'=>'md-form',
                ],
                'required'=>false,
            ])
            ->add('tel', TextType::class,[
                'label'=>false,
                'attr'=>[
                    'placeholder'=>'Numéro de téléphone',
                    'class'=>'md-form',
                ]
            ])
            ->add('telsender', TextType::class,[
                'label'=>false,
                'attr'=>[
                    'placeholder'=>'Numéro de téléphone',
                    'class'=>'md-form',
                ],
                'required'=>false,
            ])
            ->add('destinataire', TextType::class,[
                'label'=>false,
                'attr'=>[
                    'placeholder'=>'Nom & prénom',
                    'class'=>'md-form',
                ]
            ])
            ->add('destinateur', TextType::class,[
                'label'=>false,
                'attr'=>[
                    'placeholder'=>'Nom & prénom',
                    'class'=>'md-form',
                ]
            ])
            ->add('transagency', EntityType::class,[
                'class' => Agency::class,
                'query_builder' => function (EntityRepository $er) use ($userAgency) {
                    $qb = $er->createQueryBuilder('a');
                    if ($userAgency) {
                        $qb->where('a.id != :userAgency')
                            ->setParameter('userAgency', $userAgency->getId());
                    }
                    return $qb;
                },
                'required'=>true,
                'attr' => [
                    'class' => "custom-select form-select",
                    'data-live-search' => "true",
                    'data-size' => "sm",
                    'data-width' => "80%",
                    'data-show-label' => "false"
                ],
                'placeholder'=>"Choisissez l'agence de réception",
                'label'=>false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Transfert::class,
            'userAgency' => null, // Nouvelle option pour filtrer l'agence de l'utilisateur connecté
        ]);
    }
}
