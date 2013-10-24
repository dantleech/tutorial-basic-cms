<?php

namespace Acme\BasicCmsBundle\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * @PHPCRODM\Document()
 */
class Site
{
    /**
     * @PHPCRODM\Id()
     */
    protected $id;

    /**
     * @PHPCRODM\ReferenceOne(targetDocument="Acme\BasicCmsBundle\Document\Page")
     */
    protected $homepage;

    public function getHomepage() 
    {
        return $this->homepage;
    }
    
    public function setHomepage($homepage)
    {
        $this->homepage = $homepage;
    }
    
}
