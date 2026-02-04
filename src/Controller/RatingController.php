<?php

namespace App\Controller;

use App\Entity\Collection;
use App\Entity\Rating;
use App\Entity\User;
use App\Repository\CollectionRepository;
use App\Repository\RatingRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class RatingController extends AbstractController
{
    #[Route('/collections/{id}/rate', name: 'app_collection_rate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function rate(
        int $id,
        Request $request,
        CollectionRepository $collectionRepository,
        RatingRepository $ratingRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $collection = $collectionRepository->find($id);
        if (!$collection) {
            throw $this->createNotFoundException('Collection introuvable.');
        }

        if (
            !$collection->isPublished()
            || $collection->getVisibility() !== Collection::VISIBILITY_PUBLIC
            || $collection->getScope() !== Collection::SCOPE_USER
        ) {
            throw $this->createNotFoundException('Collection introuvable.');
        }

        if (
            !$this->isCsrfTokenValid(
                'rate_collection_' . $collection->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
        }

        $value = (int) $request->request->get('rating_value', 0);
        if ($value < 1 || $value > 5) {
            $this->addFlash('danger', 'La note doit être comprise entre 1 et 5.');
            return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('danger', 'Vous devez être connecté pour noter.');
            return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
        }

        $owner = $collection->getUser();
        if ($owner && $owner->getId() === $user->getId()) {
            $this->addFlash('danger', 'Vous ne pouvez pas noter votre propre collection.');
            return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
        }

        $rating = $ratingRepository->findOneBy([
            'collection' => $collection,
            'user' => $user,
        ]);

        if (!$rating) {
            $rating = (new Rating())
                ->setCollection($collection)
                ->setUser($user);

            $em->persist($rating);
        }

        $rating->setValue($value);

        try {
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            $em->clear();

            $existing = $ratingRepository->findOneBy([
                'collection' => $collection,
                'user' => $user,
            ]);

            if ($existing instanceof Rating) {
                $existing->setValue($value);
                $em->flush();
            }
        }

        $this->addFlash('success', 'Votre note a été enregistrée.');
        return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
    }
}
