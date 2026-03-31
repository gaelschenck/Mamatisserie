<?php

namespace App\Form;

use App\Entity\AboutContent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;

class AboutContentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr'  => ['maxlength' => 150],
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Texte de présentation',
                'attr'  => ['rows' => 10],
            ])
            ->add('photoFile', VichImageType::class, [
                'label'        => 'Photo',
                'required'     => false,
                'allow_delete' => true,
                'delete_label' => 'Supprimer la photo',
                'download_uri' => false,
                'image_uri'    => false,
                'asset_helper' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AboutContent::class]);
    }
}
