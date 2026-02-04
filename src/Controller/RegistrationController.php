<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
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
     * Apres une inscription reussie, je connecte automatiquement l'utilisateur.
     */
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        /*
         * Je redirige vers l'accueil si l'utilisateur est deja connecte.
         */
        if ($this->getUser()) {
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
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            /*
             * Je connecte automatiquement l'utilisateur apres son inscription.
             */
            $security->login($user, 'form_login', 'main');

            $this->addFlash('success', 'Bienvenue sur MediaTech ! Votre compte a bien été créé.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}