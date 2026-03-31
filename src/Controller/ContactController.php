<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(
        Request $request,
        MailerInterface $mailer,
        ValidatorInterface $validator,
        string $contactEmail,
    ): Response {
        // Pré-remplissage si venant d'une fiche produit
        $productName = $request->query->getString('produit', '');
        $formData    = [];

        if ($request->isMethod('POST')) {
            $name        = trim($request->request->getString('name', ''));
            $email       = trim($request->request->getString('email', ''));
            $subject     = trim($request->request->getString('subject', ''));
            $message     = trim($request->request->getString('message', ''));
            $productName = trim($request->request->getString('product_name', $productName));

            // Conserver les valeurs pour ré-affichage en cas d'erreur
            $formData = compact('name', 'email', 'subject', 'message');

            // Validation CSRF
            if (!$this->isCsrfTokenValid('contact', $request->request->getString('_csrf_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            } else {
                $errors = [];

                foreach ([
                    [$name,    [new Assert\NotBlank(message: 'Votre nom est requis.'),     new Assert\Length(max: 100,  maxMessage: 'Nom trop long.')]],
                    [$email,   [new Assert\NotBlank(message: 'Votre email est requis.'),   new Assert\Email(message: 'Adresse email invalide.')]],
                    [$subject, [new Assert\NotBlank(message: 'Le sujet est requis.'),      new Assert\Length(max: 200,  maxMessage: 'Sujet trop long.')]],
                    [$message, [new Assert\NotBlank(message: 'Votre message est requis.'), new Assert\Length(max: 2000, maxMessage: 'Message trop long (2000 caractères max).')]],
                ] as [$value, $constraints]) {
                    foreach ($validator->validate($value, $constraints) as $violation) {
                        $errors[] = $violation->getMessage();
                    }
                }

                if (empty($errors)) {
                    $emailSubject = $productName
                        ? sprintf('Contact depuis le site — À propos de "%s"', $productName)
                        : $subject;

                    $mail = (new Email())
                        ->from($contactEmail)
                        ->replyTo($email)
                        ->to($contactEmail)
                        ->subject($emailSubject)
                        ->html(sprintf(
                            '<p><strong>Nom :</strong> %s</p>
                             <p><strong>Email :</strong> %s</p>
                             %s
                             <p><strong>Sujet :</strong> %s</p>
                             <p><strong>Message :</strong></p>
                             <p>%s</p>',
                            htmlspecialchars($name),
                            htmlspecialchars($email),
                            $productName ? sprintf('<p><strong>Produit :</strong> %s</p>', htmlspecialchars($productName)) : '',
                            htmlspecialchars($subject),
                            nl2br(htmlspecialchars($message))
                        ));

                    $mailer->send($mail);

                    $this->addFlash('success', 'Votre message a bien été envoyé ! Je vous répondrai dans les plus brefs délais.');

                    return $this->redirectToRoute('app_contact');
                }

                foreach ($errors as $err) {
                    $this->addFlash('error', $err);
                }
            }
        }

        return $this->render('public/contact.html.twig', [
            'productName' => $productName,
            'formData'    => $formData,
        ]);
    }
}
