<?php

namespace App\Form;

use App\Entity\Rental;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RentalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateStart', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,  
                'label' => 'Date de début'
            ])
            ->add('dateEnd', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,  
                'label' => 'Date de fin'
            ])
            ->add('nbAdulte', IntegerType::class, [
                'label' => 'Nombre d\'adultes'
            ])
            ->add('nbChild', IntegerType::class, [
                'label' => 'Nombre d\'enfants'
            ]);
          
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rental::class,
        ]);
    }
}
