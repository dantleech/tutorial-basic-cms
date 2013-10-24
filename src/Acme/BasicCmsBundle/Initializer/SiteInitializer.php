<?php

namespace Acme\BasicCmsBundle\Initializer;

use Doctrine\Bundle\PHPCRBundle\Initializer\InitializerInterface;
use PHPCR\SessionInterface;

class SiteInitializer implements InitializerInterface
{
    public function init(SessionInterface $session)
    {
        $cms = $session->getNode('/cms');
        $cms->setProperty(
            'phpcr:class',  'Acme\BasicCmsBundle\Document\Site'
        );
    }
}

