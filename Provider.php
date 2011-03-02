<?php

namespace FOQ\AlbumBundle;

use FOS\UserBundle\Model\UserManagerInterface;
use FOS\UserBundle\Model\User;
use FOQ\AlbumBundle\Document\AlbumRepository;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use NotFoundException;
use Zend\Paginator\Paginator;
use ZendPaginatorAdapter\DoctrineMongoDBAdapter;

/**
 * High level object finder that uses the route parameters as method arguments
 */
class Provider
{
    protected $albumRepository;
    protected $userManager;
    protected $securityContext;
    protected $request;

    public function __construct(AlbumRepository $albumRepository, UserManagerInterface $userManager, SecurityContext $securityContext, Request $request = null)
    {
        $this->albumRepository = $albumRepository;
        $this->userManager     = $userManager;
        $this->securityContext = $securityContext;
        $this->request         = $request;
    }

    /**
     * Find an album by username and album slug
     *
     * @throws NotFoundException if album does not exist
     * @return AlbumInterface
     */
    public function getAlbum($username, $slug)
    {
        $user = $this->userManager->findUserByUsername($username);

        if (empty($user)) {
            throw new NotFoundHttpException(sprintf('The user "%s" does not exist', $username));
        }

        $album = $this->albumRepository->findOneByUserAndSlugForUser($user, $slug, $this->getAuthenticatedUser());

        if (empty($album)) {
            throw new NotFoundHttpException(sprintf('The album with user "%s" and slug "%s" does not exist or is not published', $username, $slug));
        }

        return $album;
    }

    /**
     * Return a paginator of albums
     *
     * @return Paginator
     **/
    public function getPaginatedAlbums()
    {
        return $this->paginate($this->albumRepository->createPublicSortedQuery($this->getAuthenticatedUser()));
    }

    /**
     * Return a paginator of albums of a user
     *
     * @return Paginator
     **/
    public function getUserPaginatedAlbums(User $user)
    {
        $this->paginate($this->albumRepository->createPublicUserSortedQuery($user, $this->getAuthenticatedUser()));
    }

    /**
     * Find a photo
     *
     * @return Photo
     **/
    public function getPhoto($username, $slug, $number)
    {
        return $this->findAlbum($username, $slug)->getPhotoByNumber($number);
    }

    protected function getAuthenticatedUser()
    {
        if ($token = $this->securityContext->getToken()) {
            if ($user = $token->getUser()) {
                if ($user instanceof User) {
                    return $user;
                }
            }
        }
    }

    protected function paginate(Builder $query)
    {
        $pager = new Paginator(new DoctrineMongoDBAdapter($query));

        if ($request) {
            $pager->setCurrentPageNumber($this->request->query->get('page'));
        }
        $pager->setItemCountPerPage(10);
        $pager->setPageRange(5);

        return $pager;
    }
}