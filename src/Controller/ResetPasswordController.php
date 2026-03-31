<?php

namespace App\Controller;

use App\Entity\ResetPasswordToken;
use App\Repository\ResetPasswordTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ResetPasswordController extends AbstractController
{
    #[Route('/admin/mot-de-passe-oublie', name: 'app_forgot_password_request', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        UserRepository $userRepository,
        ResetPasswordTokenRepository $tokenRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): Response {
        $messageSent = false;

        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted('PUBLIC_ACCESS');

            $email = trim((string) $request->request->get('email', ''));

            // On traite toujours, même si l'email n'existe pas (sécurité : ne pas révéler les comptes)
            $user = $userRepository->findOneBy(['email' => $email]);
            if ($user !== null) {
                // Supprimer les anciens tokens de cet utilisateur
                foreach ($tokenRepository->findBy(['user' => $user]) as $oldToken) {
                    $em->remove($oldToken);
                }

                // Générer un token sécurisé
                $token = new ResetPasswordToken();
                $token->setUser($user);
                $token->setToken(bin2hex(random_bytes(32)));
                $token->setExpiresAt(new \DateTimeImmutable('+1 hour'));

                $em->persist($token);
                $em->flush();

                $resetUrl = $this->generateUrl(
                    'app_reset_password',
                    ['token' => $token->getToken()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $mail = (new TemplatedEmail())
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe — Les Délices de Chloé')
                    ->htmlTemplate('emails/reset_password.html.twig')
                    ->context([
                        'username' => $user->getUsername(),
                        'resetUrl' => $resetUrl,
                        'expiresIn' => '1 heure',
                    ]);

                $mailer->send($mail);
            }

            // Même message dans tous les cas
            $messageSent = true;
        }

        return $this->render('security/forgot_password.html.twig', [
            'message_sent' => $messageSent,
        ]);
    }

    #[Route('/admin/reinitialiser/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function reset(
        string $token,
        Request $request,
        ResetPasswordTokenRepository $tokenRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator,
    ): Response {
        $resetToken = $tokenRepository->findValidToken($token);

        if ($resetToken === null) {
            $this->addFlash('error', 'Ce lien est invalide ou a expiré. Veuillez faire une nouvelle demande.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $password = (string) $request->request->get('password', '');
            $confirm  = (string) $request->request->get('confirm_password', '');

            if (strlen($password) < 8) {
                $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
            } elseif ($password !== $confirm) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            } else {
                $user = $resetToken->getUser();
                $user->setPassword($hasher->hashPassword($user, $password));

                $em->remove($resetToken);
                $em->flush();

                $this->addFlash('success', 'Mot de passe modifié avec succès. Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token'  => $token,
            'errors' => $errors,
        ]);
    }
}
