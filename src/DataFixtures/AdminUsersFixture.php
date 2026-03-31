<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixture de création des deux comptes administrateurs.
 *
 * UTILISATION :
 *   php bin/console doctrine:fixtures:load --append
 *
 * L'option --append évite de vider toute la base avant insertion.
 *
 * AVANT DE LANCER :
 *   Modifiez les valeurs USERNAME, EMAIL et PASSWORD ci-dessous
 *   selon vos besoins.
 *
 * ATTENTION :
 *   Si les comptes existent déjà (même username ou email), la commande
 *   lèvera une erreur. Lancez cette fixture une seule fois.
 */
class AdminUsersFixture extends Fixture
{
    // =========================================================
    // MODIFIEZ CES VALEURS AVANT DE LANCER LA FIXTURE
    // =========================================================

    private const USERS = [
        [
            'username' => 'chloe',                  // Identifiant de connexion de Chloé
            'email'    => 'chloe@example.com',       // Son adresse email (pour reset mdp)
            'password' => 'MotDePasseChloe123!',     // Son mot de passe initial
        ],
        [
            'username' => 'micheline',                   // Ton identifiant
            'email'    => 'gaelschenck@hotmail.fr',        // Ton adresse email
            'password' => 'micheline123!',      // Ton mot de passe initial
        ],
    ];

    // =========================================================

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        foreach (self::USERS as $data) {
            $user = new User();
            $user->setUsername($data['username']);
            $user->setEmail($data['email']);
            $user->setRoles(['ROLE_ADMIN']);
            $user->setPassword($this->hasher->hashPassword($user, $data['password']));

            $manager->persist($user);

            echo sprintf("  Compte admin créé : %s (%s)\n", $data['username'], $data['email']);
        }

        $manager->flush();

        echo "\n  2 comptes admin créés avec succès.\n";
        echo "  PENSEZ à changer les mots de passe après la première connexion !\n";
    }
}
