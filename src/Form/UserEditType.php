<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire d'édition des informations d'un compte admin.
 * Ne touche PAS au mot de passe (géré via le formulaire de reset).
 */
class UserEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label'       => 'Nom d\'utilisateur',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 3, max: 50),
                    new Assert\Regex(
                        pattern: '/^[a-zA-Z0-9_\-\.]+$/',
                        message: 'Lettres, chiffres, tirets et underscores uniquement.'
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label'       => 'Adresse e-mail',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
