<?php

namespace FOQ\AlbumBundle\Twig;

use FOQ\AlbumBundle\UrlGenerator;
use FOQ\AlbumBundle\Model\AlbumInterface;
use FOQ\AlbumBundle\Model\PhotoInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Twig_Function_Method;
use Twig_Extension;

class AlbumExtension extends Twig_Extension
{
    protected $urlGenerator;

    public function __construct(UrlGenerator $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            'foq_album_albumUrl'         => new Twig_Function_Method($this, 'getAlbumUrl'),
            'foq_album_photoUrl'         => new Twig_Function_Method($this, 'getPhotoUrl'),
        );
    }

    /**
     * Returns the url generator
     *
     * @return UrlGenerator
     */
    public function getAlbumUrl($route, AlbumInterface $album, array $parameters = array(), $absolute = false)
    {
        return $this->urlGenerator->getAlbumUrl($route, $album, $parameters, $absolute);
    }

    /**
     * Returns the url generator
     *
     * @return UrlGenerator
     */
    public function getPhotoUrl($route, PhotoInterface $photo, array $parameters = array(), $absolute = false)
    {
        return $this->urlGenerator->getPhotoUrl($route, $photo, $parameters, $absolute);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'foq_album';
    }
}
