<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\CollectionRepository;
use App\Repository\CommentRepository;
use App\Repository\FriendshipRepository;

class ProfileService
{
    public function __construct(
        private CollectionRepository $collectionRepository,
        private CommentRepository $commentRepository,
        private FriendshipRepository $friendshipRepository,
    ) {}

    public function getProfileData(User $user, string $section): array
    {
        return match ($section) {
            'collections' => [
                'collections' => $this->collectionRepository->findForUser($user),
            ],
            'comments' => [
                'comments' => $this->commentRepository->findLatestByAuthor($user),
            ],
            'friends' => [
                'friends' => $this->friendshipRepository->findAcceptedFriends($user),
                'pending' => $this->friendshipRepository->findPendingRequests($user),
            ],
            default => [],
        };
    }
}
