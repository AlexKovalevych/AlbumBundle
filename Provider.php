<?php

namespace FOQ\AlbumBundle;

use FOS\UserBundle\Model\UserManagerInterface;
use FOQ\AlbumBundle\Document\AlbumRepository;
use FOQ\AlbumBundle\Document\PhotoRepository;
use FOQ\AlbumBundle\Model\AlbumInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Zend\Paginator\Paginator;
use ZendPaginatorAdapter\DoctrineMongoDBAdapter;
use Zend\Paginator\Adapter\ArrayAdapter;

/**
 * High level object finder that uses the route parameters as method arguments
 */
class Provider
{
    protected $albumRepository;
    protected $photoRepository;
    protected $userManager;
    protected $securityHelper;
    protected $documentManager;
    protected $request;

    public function __construct(AlbumRepository $albumRepository, PhotoRepository $photoRepository, UserManagerInterface $userManager, SecurityHelper $securityHelper, DocumentManager $documentManager, Request $request = null)
    {
        $this->albumRepository = $albumRepository;
        $this->photoRepository = $photoRepository;
        $this->userManager     = $userManager;
        $this->securityHelper  = $securityHelper;
        $this->documentManager = $documentManager;
        $this->request         = $request;
    }

    /**
     * Find an album by username and album slug
     *
     * @throws NotFoundException if album does not exist
     * @return AlbumInterface
     */
    public function getAlbum($username, $slug, $incrementImpressions = false)
    {
        $album = $this->albumRepository->findOneByUserAndSlugForUser($this->getUser($username), $slug, $this->securityHelper->getUser());

        if (empty($album)) {
            throw new NotFoundHttpException(sprintf('The album with user "%s" and slug "%s" does not exist or is not published', $username, $slug));
        }

        if ($incrementImpressions) {
            $this->incrementImpressions($album);
        }

        return $album;
    }

    /**
     * Return a paginator of albums
     *
     * @return Paginator
     **/
    public function getAlbums()
    {
        return $this->paginate($this->albumRepository->createPublicSortedQuery($this->securityHelper->getUser()));
    }

    /**
     * Return a paginator of albums of a user
     *
     * @return Paginator
     **/
    public function getUserAlbums($username)
    {
        return $this->paginate($this->albumRepository->createPublicUserSortedQuery($this->getUser($username), $this->securityHelper->getUser()));
    }

    /**
     * Get a paginator of photos of an album
     *
     * @return Paginator
     **/
    public function getAlbumPhotos(AlbumInterface $album)
    {
        return $this->paginate($this->photoRepository->createQueryByAlbum($album));
    }

    /**
     * Find a photo
     *
     * @return Photo
     **/
    public function getPhoto(AlbumInterface $album, $number, $incrementImpressions = false)
    {
        $photo = $album->getPhotos()->getPhotoByNumber($number);

        if (empty($photo)) {
            throw new NotFoundHttpException(sprintf('The photo number "%s" does not exist in album "%s"', $number, $album->getTitle()));
        }

        if ($incrementImpressions) {
            $this->incrementImpressions($photo);
        }

        return $photo;

    }

    protected function incrementImpressions($object)
    {
        $hash = md5(get_class($object).$object->getId());

        if(!$this->request->getSession()->has($hash)) {
            $object->incrementImpressions();
            $this->documentManager->flush();
            $this->request->getSession()->set($hash, true);
        }
    }

    protected function getUser($username)
    {
        $user = $this->userManager->findUserByUsername($username);

        if (empty($user)) {
            throw new NotFoundHttpException(sprintf('The user "%s" does not exist', $username));
        }

        return $user;
    }

    /**
     * Returns a new album instance
     *
     * @return AlbumInterface
     **/
    public function createAlbum()
    {
        $albumClass = $this->albumRepository->getDocumentName();

        return new $albumClass();
    }

    /**
     * Returns a new photo instance
     *
     * @return PhotoInterface
     **/
    public function createPhoto()
    {
        $photoClass = $this->photoRepository->getDocumentName();

        return new $photoClass();
    }

    protected function paginate($data)
    {
        if ($data instanceof Builder) {
            $adapter = new DoctrineMongoDBAdapter($data);
        } else {
            $adapter = new ArrayAdapter($data);
        }
        $paginator = new Paginator($adapter);

        $paginator->setCurrentPageNumber($this->request->query->get('page'));
        $paginator->setItemCountPerPage(10);
        $paginator->setPageRange(5);

        return $paginator;
    }
}
