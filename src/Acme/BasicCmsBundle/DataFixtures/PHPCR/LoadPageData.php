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

        $rootPage = new Page;
        $rootPage->setTitle('main');
        $rootPage->setParent($parent);
        $dm->persist($rootPage);

        $page = new Page;
        $page->setTitle('Home');
        $page->setParent($rootPage);
        $page->setContent(<<<HERE
Welcome to the homepage of this really basic CMS.
HERE
        );
        $dm->persist($page);

        $page = new Page;
        $page->setTitle('About');
        $page->setParent($rootPage);
        $page->setContent(<<<HERE
This page explains what its all about.
HERE
        );
        $dm->persist($page);

        $dm->flush();
    }
}
