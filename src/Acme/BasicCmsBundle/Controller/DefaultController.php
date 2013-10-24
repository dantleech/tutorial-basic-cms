<?php

namespace Acme\BasicCmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        $dm = $this->get('doctrine_phpcr')->getManager();
        $site = $dm->find(null, '/cms');
        $homepage = $site->getHomepage();

        if (!$homepage) {
            throw new NotFoundHttpException('No homepage configured');
        }

        // ????
    }

    /**
     * @Template()
     */
    public function pageAction($contentDocument)
    {
        $dm = $this->get('doctrine_phpcr')->getManager();
        $posts = $dm->getRepository('Acme\BasicCmsBundle\Document\Post')->findAll();

        return array(
            'page' => $contentDocument,
            'posts' => $posts,
        );
    }

    /**
     * @Route(
     *   name="make_homepage", 
     *   pattern="/_cms/make_homepage/{id}", 
     *   requirements={"id": ".+"}
     * )
     */
    public function makeHomepageAction($id)
    {
        $dm = $this->get('doctrine_phpcr')->getManager();

        $cms = $dm->find(null, '/cms');
        if (!$cms) {
            throw new NotFoundHttpException('Could not find /cms document!');
        }

        $page = $dm->find(null, $id);
        if (!$page) {
            throw new NotFoundHttpException(sprintf('Could not find page with ID "%s" document!', $id));
        }

        $cms->setHomepage($page);
        $dm->persist($page);
        $dm->flush();

        return $this->redirect($this->generateUrl('admin_acme_basiccms_page_edit', array( 
            'page' => $page->getId()
        )));
    }
}
