<?php

namespace Acme\BasicCmsBundle\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Symfony\Cmf\Component\Routing\RouteReferrersReadInterface;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class Post implements RouteReferrersReadInterface
{
    use ContentTrait;
    
    /**
     * @PHPCRODM\Date()
     */
    protected $date;

    /**
     * @PHPCRODM\Referrers(referringDocument="Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route", referencedBy="content")
     */
    protected $routes;

    /**
     * @PHPCRODM\PrePersist()
     */
    public function updateDate()
    {
        if (!$this->date) {
            $this->date = new \DateTime();
        }
    }

    public function getDate() 
    {
        return $this->date;
    }
    
    public function setDate($date)
    {
        $this->date = $date;
    }

    public function getRoutes()
    {
        return $this->routes;
    }
}
