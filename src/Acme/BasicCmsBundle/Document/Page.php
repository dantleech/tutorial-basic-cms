<?php

namespace Acme\BasicCmsBundle\Document;

use Symfony\Cmf\Component\Routing\RouteReferrersReadInterface;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Knp\Menu\NodeInterface;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class Page implements NodeInterface, RouteReferrersReadInterface
{
    use ContentTrait;

    /**
     * @PHPCRODM\Children()
     */
    protected $children;

    public function getName()
    {
        return $this->title;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getOptions()
    {
        return array(
            'label' => $this->title,
            'attributes' => array(),
            'childrenAttributes' => array(),
            'displayChildren' => true,
            'linkAttributes' => array(),
            'labelAttributes' => array(),
            'content' => $this,
        );
    }
}
