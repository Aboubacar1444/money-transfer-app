<?php

namespace App\Form;

use App\Entity\Transfert;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransfertcType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('montant', NumberType::class,[
                'label'=>false,
                'attr'=>[
                    'placeholder'=>'Montant à envoyé',
                    'class'=>'md-form',
                ]
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
            ->add('destinataire', TextType::class,[
                'label'=>false,
                'attr'=>[
                    'placeholder'=>'Nom & prénom',
                    'class'=>'md-form',
                ]
            ])
            ->add('transagency', EntityType::class,[
                'class'=>"App\Entity\Agency",
                'required'=>true,
                'attr'=>[
                    'class'=>"form-select form-control md-form"
                ],
                'placeholder'=>"Choix Agence de réception",
                'label'=>false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Transfert::class,
        ]);
    }
}
