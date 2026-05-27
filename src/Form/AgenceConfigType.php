<?php

namespace App\Form;

use App\Entity\AgenceConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgenceConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['label' => 'Nom de l\'Agence'])
            ->add('adresse', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, ['label' => 'Adresse complète', 'required' => false])
            ->add('siret', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['label' => 'SIRET / NINEA', 'required' => false])
            ->add('telephone', \Symfony\Component\Form\Extension\Core\Type\TelType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('email', \Symfony\Component\Form\Extension\Core\Type\EmailType::class, ['label' => 'Email de contact', 'required' => false])
            ->add('iban', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['label' => 'IBAN / RIB', 'required' => false])
            ->add('logo', \Symfony\Component\Form\Extension\Core\Type\FileType::class, [
                'label' => 'Logo de l\'agence (JPG, PNG)',
                'mapped' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AgenceConfig::class,
        ]);
    }
}
