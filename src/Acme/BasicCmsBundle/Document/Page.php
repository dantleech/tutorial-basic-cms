<?php

namespace Acme\BasicCmsBundle\Document;

use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
use Knp\Menu\NodeInterface;

/**
 * @PHPCRODM\Document(referenceable=true)
 */
class Page implements NodeInterface
{
    use ContentTrait;
}
