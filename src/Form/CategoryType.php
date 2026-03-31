<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr'  => ['maxlength' => 100],
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
                'attr'  => ['min' => 0],
            ])
            ->add('isVisible', CheckboxType::class, [
                'label'    => 'Visible sur le site',
                'required' => false,
            ])
            ->add('metaDescription', TextareaType::class, [
                'label'    => 'Description courte (SEO)',
                'required' => false,
                'attr'     => ['maxlength' => 160, 'rows' => 2],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Category::class]);
    }
}
