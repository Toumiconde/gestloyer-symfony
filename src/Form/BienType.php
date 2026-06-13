<?php

namespace App\Form;

use App\Entity\Bien;
use App\Entity\Proprietaire;
use App\Enum\StatutBien;
use App\Enum\TypeBien;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BienType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du Bien',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse Complète',
                'required' => false,
                'attr' => ['rows' => 3, 'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('type', EnumType::class, [
                'class' => TypeBien::class,
                'label' => 'Type de Bien',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('statut', EnumType::class, [
                'class' => StatutBien::class,
                'label' => 'Statut Actuel',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('nbChambres', IntegerType::class, [
                'label' => 'Nombre de chambres',
                'required' => false,
                'attr' => ['min' => 0, 'class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('doucheInterne', CheckboxType::class, [
                'label' => 'Douche interne',
                'required' => false,
                'attr' => ['class' => 'h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded']
            ])
            ->add('doucheExterne', CheckboxType::class, [
                'label' => 'Douche externe',
                'required' => false,
                'attr' => ['class' => 'h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded']
            ])
            ->add('hasTerrasse', CheckboxType::class, [
                'label' => 'Possède une terrasse',
                'required' => false,
                'attr' => ['class' => 'h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded']
            ])
            ->add('emplacement', TextType::class, [
                'label' => 'Emplacement / Quartier',
                'required' => false,
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('proprietaire', EntityType::class, [
                'class' => Proprietaire::class,
                'choice_label' => function(Proprietaire $proprietaire) {
                    return $proprietaire->getNom() . ' ' . $proprietaire->getPrenom();
                },
                'label' => 'Propriétaire',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bien::class,
        ]);
    }
}
