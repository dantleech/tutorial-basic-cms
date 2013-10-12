<?php

namespace Acme\BasicCmsBundle\DataFixtures\Phpcr;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Acme\BasicCmsBundle\Document\Page;
use PHPCR\Util\NodeHelper;

class LoadPageData implements FixtureInterface
{
    public function load(ObjectManager $dm)
    {
        NodeHelper::createPath($dm->getPhpcrSession(), '/cms/pages');
        $parent = $dm->find(null, '/cms/pages');

        $page = new Page;
        $page->setTitle('Home');
        $page->setParent($parent);
        $page->setContent(<<<HERE
Welcome to the homepage of this really basic CMS.
HERE
        );

        $dm->persist($page);
        $dm->flush();
    }
}
