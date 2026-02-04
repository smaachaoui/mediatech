<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Collection;
use App\Entity\CollectionBook;
use App\Entity\CollectionMovie;
use App\Entity\Comment;
use App\Entity\Genre;
use App\Entity\Movie;
use App\Entity\Rating;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $genres = $this->createGenres($manager);
        $users = $this->createUsers($manager, $genres);
        $media = $this->createMedia($manager);

        $this->createCollectionsAndContent($manager, $users, $media);

        $manager->flush();
    }

    /**
     * @return array<string, Genre>
     */
    private function createGenres(ObjectManager $manager): array
    {
        $items = [];

        $bookGenres = [
            'Science-fiction',
            'Fantasy',
            'Thriller',
            'Histoire',
            'Développement personnel',
        ];

        $movieGenres = [
            'Action',
            'Comédie',
            'Drame',
            'Science-fiction',
            'Animation',
        ];

        foreach ($bookGenres as $name) {
            $g = (new Genre())
                ->setName($name)
                ->setType('book');

            $manager->persist($g);
            $items['book:' . $name] = $g;
        }

        foreach ($movieGenres as $name) {
            $g = (new Genre())
                ->setName($name)
                ->setType('movie');

            $manager->persist($g);
            $items['movie:' . $name] = $g;
        }

        return $items;
    }

    /**
     * @param array<string, Genre> $genres
     * @return array<string, User>
     */
    private function createUsers(ObjectManager $manager, array $genres): array
    {
        $users = [];

        $admin = (new User())
            ->setEmail('admin@mediatech.local')
            ->setPseudo('admin')
            ->setRoles(['ROLE_ADMIN'])
            ->setBiography("Je suis l'administrateur de démonstration.");

        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin1234!'));
        $manager->persist($admin);
        $users['admin'] = $admin;

        $alice = (new User())
            ->setEmail('alice@mediatech.local')
            ->setPseudo('alice')
            ->setBiography("Je crée des collections publiques et je teste les commentaires.");

        $alice->setPassword($this->passwordHasher->hashPassword($alice, 'User1234!'));
        $alice->addFavoriteGenre($genres['book:Fantasy']);
        $alice->addFavoriteGenre($genres['movie:Animation']);
        $manager->persist($alice);
        $users['alice'] = $alice;

        $bob = (new User())
            ->setEmail('bob@mediatech.local')
            ->setPseudo('bob')
            ->setBiography("Je teste les notes et les listes d'envie.");

        $bob->setPassword($this->passwordHasher->hashPassword($bob, 'User1234!'));
        $bob->addFavoriteGenre($genres['book:Thriller']);
        $bob->addFavoriteGenre($genres['movie:Action']);
        $manager->persist($bob);
        $users['bob'] = $bob;

        $charlie = (new User())
            ->setEmail('charlie@mediatech.local')
            ->setPseudo('charlie')
            ->setBiography("Je suis un utilisateur de démo avec peu de contenu.");

        $charlie->setPassword($this->passwordHasher->hashPassword($charlie, 'User1234!'));
        $manager->persist($charlie);
        $users['charlie'] = $charlie;

        return $users;
    }

    /**
     * @return array{books: Book[], movies: Movie[]}
     */
    private function createMedia(ObjectManager $manager): array
    {
        $books = [];
        $movies = [];

        $b1 = (new Book())
            ->setTitle('Dune')
            ->setAuthor('Frank Herbert')
            ->setGenre('Science-fiction')
            ->setIsbn('978-2266320481')
            ->setSynopsis("Une grande fresque de science-fiction politique et mystique.")
            ->setGoogleBooksId('dune-fixture-1');

        $b2 = (new Book())
            ->setTitle('Le Seigneur des Anneaux')
            ->setAuthor('J.R.R. Tolkien')
            ->setGenre('Fantasy')
            ->setIsbn('978-2266286268')
            ->setSynopsis("Un voyage épique pour détruire l'Anneau Unique.")
            ->setGoogleBooksId('lotr-fixture-1');

        $b3 = (new Book())
            ->setTitle('Sapiens')
            ->setAuthor('Yuval Noah Harari')
            ->setGenre('Histoire')
            ->setIsbn('978-2226257017')
            ->setSynopsis("Une histoire globale de l'humanité.")
            ->setGoogleBooksId('sapiens-fixture-1');

        foreach ([$b1, $b2, $b3] as $b) {
            $manager->persist($b);
            $books[] = $b;
        }

        $m1 = (new Movie())
            ->setTitle('Interstellar')
            ->setGenre('Science-fiction')
            ->setSynopsis("Une mission spatiale pour sauver l'humanité.")
            ->setTmdbId(100001);

        $m2 = (new Movie())
            ->setTitle('Le Voyage de Chihiro')
            ->setGenre('Animation')
            ->setSynopsis("Une jeune fille se retrouve piégée dans un monde d'esprits.")
            ->setTmdbId(100002);

        $m3 = (new Movie())
            ->setTitle('Mad Max: Fury Road')
            ->setGenre('Action')
            ->setSynopsis("Une course-poursuite dans un désert post-apocalyptique.")
            ->setTmdbId(100003);

        foreach ([$m1, $m2, $m3] as $m) {
            $manager->persist($m);
            $movies[] = $m;
        }

        return ['books' => $books, 'movies' => $movies];
    }

    /**
     * @param array<string, User> $users
     * @param array{books: Book[], movies: Movie[]} $media
     */
    private function createCollectionsAndContent(ObjectManager $manager, array $users, array $media): void
    {
        foreach (['alice', 'bob', 'charlie'] as $key) {
            $user = $users[$key];

            $default = (new Collection())
                ->setName('Non répertorié')
                ->setScope(Collection::SCOPE_SYSTEM)
                ->setMediaType(Collection::MEDIA_ALL)
                ->setVisibility('private')
                ->setIsPublished(false)
                ->setUser($user);

            $wishlist = (new Collection())
                ->setName("Liste d'envie")
                ->setScope(Collection::SCOPE_SYSTEM)
                ->setMediaType(Collection::MEDIA_ALL)
                ->setVisibility('private')
                ->setIsPublished(false)
                ->setUser($user);

            $manager->persist($default);
            $manager->persist($wishlist);

            $public = (new Collection())
                ->setName('Mes incontournables')
                ->setScope(Collection::SCOPE_USER)
                ->setMediaType(Collection::MEDIA_ALL)
                ->setVisibility('public')
                ->setIsPublished(true)
                ->setPublishedAt(new \DateTimeImmutable('-10 days'))
                ->setDescription("Une collection publique de démonstration.")
                ->setUser($user);

            $manager->persist($public);

            $this->attachMediaToCollection($manager, $public, $media);
            $this->attachMediaToCollection($manager, $wishlist, $media, true);

            $this->createRatings($manager, $users, $public);
            $this->createComments($manager, $users, $public);
        }
    }

    /**
     * @param array{books: Book[], movies: Movie[]} $media
     */
    private function attachMediaToCollection(
        ObjectManager $manager,
        Collection $collection,
        array $media,
        bool $light = false
    ): void {
        $books = $media['books'];
        $movies = $media['movies'];

        $bookCount = $light ? 1 : 2;
        $movieCount = $light ? 1 : 2;

        for ($i = 0; $i < $bookCount; $i++) {
            $cb = (new CollectionBook())
                ->setCollection($collection)
                ->setBook($books[$i]);

            $manager->persist($cb);
        }

        for ($i = 0; $i < $movieCount; $i++) {
            $cm = (new CollectionMovie())
                ->setCollection($collection)
                ->setMovie($movies[$i]);

            $manager->persist($cm);
        }
    }

    /**
     * @param array<string, User> $users
     */
    private function createRatings(ObjectManager $manager, array $users, Collection $collection): void
    {
        $r1 = (new Rating())
            ->setCollection($collection)
            ->setUser($users['alice'])
            ->setValue(5);

        $r2 = (new Rating())
            ->setCollection($collection)
            ->setUser($users['bob'])
            ->setValue(4);

        $manager->persist($r1);
        $manager->persist($r2);
    }

    /**
     * @param array<string, User> $users
     */
    private function createComments(ObjectManager $manager, array $users, Collection $collection): void
    {
        $c1 = (new Comment())
            ->setCollection($collection)
            ->setUser($users['alice'])
            ->setContent("Très bonne collection, j'aime la sélection.");

        $c2 = (new Comment())
            ->setCollection($collection)
            ->setUser($users['bob'])
            ->setContent("Je valide, surtout pour les films. Ajoute-en d'autres.");

        $c3 = (new Comment())
            ->setCollection($collection)
            ->setGuestName('Invité Demo')
            ->setGuestEmail('invite@exemple.local')
            ->setContent("Je découvre le site, c'est propre et lisible.");

        $manager->persist($c1);
        $manager->persist($c2);
        $manager->persist($c3);
    }
}
