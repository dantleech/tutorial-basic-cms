reating a Basic CMS with the CMF
=================================

This post will show you how to create a basic CMS from scratch.

The CMS will have two types of content:

* Pages
* Blog Posts

You will use the following bundles:

* RoutingAutoBundle
* DoctrinePhpcrAdminBundle
* ContentBundle

in addition:

* doctrine/data-fixtures

and of course the bundles which depend on these bundles.

We will assume the following:

* A familiarity with composer
* Good knowledge of the standard symfony 2 framework

Initializing the project
------------------------

Get started by installing the following PHPCR ODM based Symfony distribution:

.. code-block:: bash

    $ composer create-project  --stablitiy=dev dantleech/symfony-doctrine-phpcr-edition basic-cms

If you have got PHP 5.4 you can fireup the inbuilt web server:

.. code-block:: bash

    $ php app/console server:run

Now go to http://localhost:8000 and everything should be working.

Add some more packages
----------------------

You will need to install the packages:

.. code-block:: javascript

    {
        ...
        "symfony-cmf/routing-auto-bundle": "dev-master",
        "sonata-project/doctrine-phpcr-admin-bundle": "dev-master"
    },

and the packages we are installing are not yet stable, so change the minimum stability from ``stable`` to ``dev``.

Add the packages to the kernel:

@todo: Create the MySQL database (if MySQL used)
@todo: Init doctrine dbal

Generate BasicCmsBundle
-----------------------

Create the Documents
--------------------

We will create a trait to share the commonalities between Post and Page.

.. code-block:: php

    // content trait

The post class - explain about the referrers and their relevance to routing.

.. code-block:: php

    // post class

.. code-block:: php

    // page class

Repository Initializer
----------------------

.. code-block:: xml

    <service id="acme.phpcr.initializer" class="Doctrine\Bundle\PHPCRBundle\Initializer\GenericInitializer">
        <argument type="collection">
            <argument>/cms/pages</argument>
            <argument>/cms/posts</argument>
        </argument>
        <tag name="doctrine_phpcr.initializer"/>
    </service>

.. code-block:: bash

@todo: Init repository

create fixtures
---------------
@todo: caveat about creating the paths until initializing problem is resolved
@todo: show fixtures class for page
@todo: show fixtures class for post

automatic routing
-----------------

@todo: Enable routing - show cmf_routing section
@todo: show auto routing configuration -- task: 
@todo: include in config.yml

Load the fixtures::

    $ php app/console doctrine:phpcr:fixtures:load

Have a look at what you have::

    $ php app/console doctrine:phpcr:node:dump
    ROOT:
      cms:
        pages:
          1076584180:
        routes:
          page:
            home:
          post:
            2013:
              10:
                12:
                  my-first-post:
                  my-second-post:
                  my-third-post:
                  my-forth-post:
        posts:
          390445918:
          1584076545:
          168754307:
          1970620640:

@todo: Explain about the automatic IDs

OK! You have something.

Sonata
------

Enable the Sonata related bundles to your kernel::

    new Sonata\BlockBundle\SonataBlockBundle(),
    new Sonata\jQueryBundle\SonatajQueryBundle(),
    new Knp\Bundle\MenuBundle\KnpMenuBundle(),
    new Sonata\DoctrinePHPCRAdminBundle\SonataDoctrinePHPCRAdminBundle(),
    new Sonata\AdminBundle\SonataAdminBundle(),

@todo: Publish your assets

Add some required configuration::

    sonata_block:
        default_contexts: [cms]
        blocks:
            # Enable the SonataAdminBundle block
            sonata.admin.block.admin_list:
                contexts:   [admin]

Add sonata routes::

    # app/config/routing.yml
    admin:
        resource: '@SonataAdminBundle/Resources/config/routing/sonata_admin.xml'
        prefix: /admin

    _sonata_admin:
        resource: .
        type: sonata_admin
        prefix: /admin

Goto: http://localhost:8000/admin/dashboard

No translations? Uncomment the translator in the configuration file::

    translator:      { fallback: %locale% }

Disable the routing admin (we handle this automatically)::

    cmf_routing:
        ...
        dynamic:
            ...
            persistence:
                phpcr:
                    ...
                    use_sonata_admin: false


2. Add Admin classes for Post and PAge

@todo: include admin classes

3. Add service definitions to services.xml::

@todo: Include service definitions

Check it out: http://localhost:8000/admin/dashboard

The Frontend
------------

Go to: http://localhost:8000/home

Should be our page, but says it cannot find a controller.

Lets map a default controller for all instances of Page::

        controllers_by_class:
            Acme\BasicCmsBundle\Document\Page: Acme\BasicCmsBundle\Controller\BasicController::pageAction

And create add that method to your default controller::

    class DefaultController extends Controller
    {
        // ...

        /**
         * @Template()
         */
        public function pageAction($contentDocument)
        {
            return array('page' => $contentDocumente;r
        }
    }

and a corresponding twig template::

    <h1>{{ page.title }}</h1>
    <p>{{ page.content|raw }}</p>
    <h2>Our Blog Posts</h2>
    <ul>
        {% for post in posts %}
            <li><a href="{{ path(post) }}">{{ post.title }}</a></li>
        {% endfor %}
    </ul>


OK now have another look at: http://localhost:8000/home

Notice what is happening with the post routes - we pass the ``Post`` object to
the ``{{ path }}`` helper and because it implements the
``RouteReferrersReadInterface`` it find the dynamic routes in our database and
generate the URL.

Click on a ``Post`` and you will have the same error that you had before when
viewing the page at ``/home``.

You should now have enough knowledge to finish this off as you like:

- Add the ``Post`` class to the controllers_by_type configuration setting in
  the configuration and route it to a new action in the controller.
- Create a new template for the ``Post``.
- Maybe you want to create a layout and make everything look good.

Thats it, in the next part we will add a simple Menu to our Basic CMS.

Things we should improve
------------------------

Sonata:

- Having to set the route builder manually sucks
- Having to call prePersist to set parent -- we could add some mechanisim to file
  documents automatically where setting a deep tree position is not required. See next section.
- Setting the document name - we should provide a mechanisim to slugify the name from something else,
  perhaps with the AutoId thingy?

PHPCR-ODM
~~~~~~~~~

- Having to do PathHelper::createPath in fixtures is not nice
- Initializer should be configurable from config.yml -- why force user to create a service?
