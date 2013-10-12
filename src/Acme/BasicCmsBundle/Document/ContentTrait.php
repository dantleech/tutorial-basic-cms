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
     * @PHPCRODM\String()
     */
    protected $title;

    /**
     * @PHPCRODM\String()
     */
    protected $content;

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
}
