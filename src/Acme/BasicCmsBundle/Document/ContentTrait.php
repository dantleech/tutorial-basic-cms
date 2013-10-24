<?php

namespace Acme\BasicCmsBundle\Document;

trait ContentTrait
{
    /**
     * @PHPCRODm\Id()
     */
    protected $id;

    /**
     * @PHPCRODM\ParentDocument()
     */
    protected $parent;

    /**
     * @PHPCRODM\NodeName()
     */
    protected $title;

    /**
     * @PHPCRODM\String(nullable=true)
     */
    protected $content;

    /**
     * @PHPCRODM\Referrers(referringDocument="Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route", referencedBy="content")
     */
    protected $routes;

    public function getId()
    {
        return $this->id;
    }

    public function getParent() 
    {
        return $this->parent;
    }
    
    public function setParent($parent)
    {
        $this->parent = $parent;
    }
    
    
    public function getTitle() 
    {
        return $this->title;
    }
    
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getContent() 
    {
        return $this->content;
    }
    
    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getRoutes()
    {
        return $this->routes;
    }
}
