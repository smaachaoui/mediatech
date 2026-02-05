<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Je gere le processus d'inscription des nouveaux utilisateurs.
 */
final class RegistrationController extends AbstractController
{
    /**
     * J'affiche le formulaire d'inscription et je traite la soumission.
     */
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /*
         * Je redirige vers l'accueil si l'utilisateur est deja connecte.
         */
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*
             * Je hash le mot de passe avant de le stocker en base.
             */
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    (string) $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            /*
             * J'affiche un message de confirmation.
             */
            $this->addFlash(
                'success',
                'Bienvenue sur MediaTech ! Votre compte a bien été créé. Vous pouvez maintenant vous connecter.'
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
