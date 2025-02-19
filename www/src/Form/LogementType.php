<?php

namespace App\Form;

use App\Entity\Logement;
use App\Entity\Equipement;
use App\Entity\Tarif;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Query\Expr\Select;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\File;

class LogementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Nom du logement',
            ])
            ->add('surface', IntegerType::class, [
                'label' => 'Surface en m²',
            ])
            ->add('nbPersonne', IntegerType::class, [
                'label' => 'Capacité',
            ])
            ->add('emplacement', IntegerType::class, [
                'label' => 'Numéro d\'emplacement',
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
            ])
            ->add('image', FileType::class, [
                'label' => 'Image du logement',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Merci d\'uploader une image valide (JPEG, PNG, WEBP).',
                    ])
                ],
            ])
            ->add('tarifs', EntityType::class, [
                'class' => Tarif::class,
                'choice_label' => 'price',
                'multiple' => true,
                'expanded' => false, // Affiche sous forme de menu déroulant avec sélection multiple
                'label' => 'Tarifs',
                'attr' => ['class' => 'select-multiple'] // Ajoute une classe CSS pour styliser si nécessaire
            ])
            ->add('equipements', EntityType::class, [
                'class' => Equipement::class,
                'choice_label' => 'label',
                'multiple' => true,
                'expanded' => false, // Affiche sous forme de menu déroulant avec sélection multiple
                'label' => 'Équipements',
                'attr' => ['class' => 'select-multiple'] // Ajoute une classe CSS pour styliser si nécessaire
            ]);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Logement::class,
        ]);
    }
}
