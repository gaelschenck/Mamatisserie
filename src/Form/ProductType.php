<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\SubCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'attr'  => ['maxlength' => 150],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['rows' => 4, 'maxlength' => 1000],
            ])
            ->add('photoFile', VichImageType::class, [
                'label'             => 'Photo',
                'required'          => false,
                'allow_delete'      => true,
                'delete_label'      => 'Supprimer la photo',
                'download_uri'      => false,
                'image_uri'         => false,
                'asset_helper'      => false,
            ])
            ->add('category', EntityType::class, [
                'class'        => Category::class,
                'choice_label' => 'name',
                'label'        => 'Catégorie',
            ])
            ->add('subCategory', EntityType::class, [
                'class'        => SubCategory::class,
                'choice_label' => 'name',
                'label'        => 'Sous-catégorie',
                'required'     => false,
                'placeholder'  => '— Aucune —',
            ])
            ->add('isVisible', CheckboxType::class, [
                'label'    => 'Visible sur le site',
                'required' => false,
            ])
            ->add('isFeatured', CheckboxType::class, [
                'label'    => 'Coup de cœur (mis en avant)',
                'required' => false,
            ])
            ->add('displayOrder', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
                'attr'  => ['min' => 0],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Product::class]);
    }
}
