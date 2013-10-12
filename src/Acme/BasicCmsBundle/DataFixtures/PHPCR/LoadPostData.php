<?php

namespace Acme\BasicCmsBundle\DataFixtures\Phpcr;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Acme\BasicCmsBundle\Document\Post;
use PHPCR\Util\NodeHelper;

class LoadPostData implements FixtureInterface
{
    public function load(ObjectManager $dm)
    {
        NodeHelper::createPath($dm->getPhpcrSession(), '/cms/posts');
        $parent = $dm->find(null, '/cms/posts');

        foreach (array('First', 'Second', 'Third', 'Forth') as $title) {
            $post = new Post;
            $post->setTitle(sprintf('My %s Post', $title));
            $post->setParent($parent);
            $post->setContent(<<<HERE
This is the content of my post.
HERE
            );

            $dm->persist($post);
        }

        $dm->flush();
    }
}
