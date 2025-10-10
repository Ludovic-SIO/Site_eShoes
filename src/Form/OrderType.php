<?php

namespace App\Form;

use App\Entity\City;
use Doctrine\ORM\EntityRepository;
use App\Entity\Order;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('phoneNumber')
            ->add('adress')
            //->add('createdAt', null, [
                //'widget' => 'single_text',
            //])
            //->add('createdAt',null,[
                //'widget' => 'single_text',
        
    

            ->add('city', EntityType::class, [
                'class' => City::class,
                //'choice_label' => 'id',
                'choice_label' => function($city){
                    return $city->getZipCode()."-". $city->getName();
                },
                'query_builder' => function (EntityRepository $er){
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.zipCode','ASC')
                        ->addOrderBy('c.name','ASC');
                },

            ])
            
            ->add('shippingPrice',HiddenType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
