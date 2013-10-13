Part 2 Creating a Basic CMS with the CMF
========================================

This post will show you how to create a basic CMS from scratch using the following bundles:

* RoutingAutoBundle
* DoctrinePhpcrAdminBundle
* MenuBundle

The resulting system will be equivalent to that provided by the SimpleCmsBundle but more powerful.

The SimpleCmsBundle combines the route, menu and content into a single document and uses a custom
router. This approach will combine only the menu and content into a single document and the routes
will be managed automatically and the native CMF ``DynamicRouter`` will be used.

The CMS will have two types of content:

* Pages - HTML content accessed at, for example ``/page/home``, '`/page/about`', etc.
* Blog Posts - Blog posts accessed as `/blog/2012/10/23/my-blog-post`.

It is assumed that you have:

* A working knowledge of the Symfony 2 framework

Initializing the Project
------------------------

Create a New Project
~~~~~~~~~~~~~~~~~~~~

Get started by installing the following PHPCR ODM based Symfony distribution:

.. code-block:: bash

    $ composer create-project --stablitiy=dev dantleech/symfony-doctrine-phpcr-edition basic-cms

.. note::

    The `PHPCR ODM Symfony distribtion`_ above is the same as the `Symfony Standard Edition`_ except
    that the Doctrine ORM is replaced by the PHPCR-ODM.

.. note::

    You could also use the `Symfony CMF Standard Edition`_. The CMF Standard Edition is also based
    on the Symfony Standard Edition and replaces the ORM with the PHPCR-ODM, however it also includes
    the entire CMF stack and some other dependencies which are not required for this tutorial.

If you have got PHP 5.4 you can start the inbuilt web server:

.. code-block:: bash

    $ php app/console server:run

and go to http://localhost:8000 to verify that everything is working.

Add Additional Bundles
~~~~~~~~~~~~~~~~~~~~~~

Now update ``composer.json`` and add the following dependencies:

.. code-block:: javascript

    require: {
        ...
        "symfony-cmf/routing-auto-bundle": "dev-master",
        "sonata-project/doctrine-phpcr-admin-bundle": "dev-master",
        "doctrine/data-fixtures": "1.0.0"
    },

and the packages we are installing are not yet stable, so change the minimum stability from ``stable`` to ``dev``.

.. code-block:: javascript

    "minimum-stability": "dev",

Add the packages to the kernel:

.. code-block:: php

    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            $bundles = array(
                // ...
                new Symfony\Cmf\Bundle\RoutingBundle\CmfRoutingBundle(),
                new Symfony\Cmf\Bundle\RoutingAutoBundle\CmfRoutingAutoBundle(),
            );

            // ...
        }
    }

Initialize the Database
~~~~~~~~~~~~~~~~~~~~~~~

If you have used the default settings, then you are using the `Doctrine DBAL Jackalope`_ PHPCR backend
with MySQL and you will need to create the MySQL database:

.. code-block:: bash

    $ mysqladmin create basic-cms -u root

and initialize it:

.. code-block:: bash

    $ php app/console doctrine:phpcr:dbal:init

.. note::

    The `Apache Jackrabbit`_ backend is a good alternative Doctrine DBAL implementation.

Start Coding
------------

Generate a new bundle:

.. code-block:: bash

    $ php app/console generate:bundle --namespace=Acme/BasicCmsBundle --no-interaction

The Documents
~~~~~~~~~~~~~

You will create 2 document classes, one for the pages and one for the posts. These two documents
share much of the same logic, so lets create a ``trait`` to reduce code duplication:

.. code-block:: php

    // src/Acme/BasicCmsBundle/Document/ContentTrait.php

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

The ``Page`` class is nice and simple:

.. code-block:: php

    // src/Acme/BasicCmsBundle/Document/Page.php

    namespace Acme\BasicCmsBundle\Document;

    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

    /**
     * @PHPCRODM\Document(referenceable=true)
     */
    class Page
    {
        use ContentTrait;
    }

The ``Post`` class is not quite as simple. It will have two special features:

* It will keep a reference to all the routes that refer to it and implement the ``RouteRefferersInterface``
  which will enable the `DynamicRouter to generate URLs`_. (for example with ``{{ path(content) }}`` in Twig).
* It will automatically set the date if it has not been explicitly set using the `pre persist lifecycle event`_.

.. code-block:: php

    // src/Acme/BasicCms/Document/Post.php

    namespace Acme\BasicCmsBundle\Document;

    use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;
    use Symfony\Cmf\Component\Routing\RouteReferrersReadInterface;

    /**
     * @PHPCRODM\Document(referenceable=true)
     */
    class Post implements RouteReferrersReadInterface
    {
        use ContentTrait;

        /**
         * @PHPCRODM\Date()
         */
        protected $date;

        /**
         * @PHPCRODM\Referrers(referringDocument="Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Phpcr\Route", referencedBy="content")
         */
        protected $routes;

        /**
         * @PHPCRODM\PrePersist()
         */
        public function updateDate()
        {
            if (!$this->date) {
                $this->date = new \DateTime();
            }
        }

        public function getDate()
        {
            return $this->date;
        }

        public function setDate($date)
        {
            $this->date = $date;
        }

        public function getRoutes()
        {
            return $this->routes;
        }
    }

.. note::

    You may be wondering why we simple do not extend the ``Page`` class instead of using of a ``trait``. We
    do this because PHPCR-ODM will take inheritance into account when querying objects - a search for ``Page`` documents
    would also return any documents which extend ``Page``.

Repository Initializer
----------------------

A `repository initializers`_ enable you to initialize required paths within your content repository, for example
we will need the paths ``/cms/pages`` and ``/cms/posts``. We can use the register a ``GenericInitializer`` class:

.. code-block:: xml

    <service id="acme.basiccms.phpcr.initializer" class="Doctrine\Bundle\PHPCRBundle\Initializer\GenericInitializer">
        <argument type="collection">
            <argument>/cms/pages</argument>
            <argument>/cms/posts</argument>
        </argument>
        <tag name="doctrine_phpcr.initializer"/>
    </service>

And run the initializer:

.. code-block:: bash

    $ php app/console doctrine:phpcr:repository:init

Create Data Fixtures
--------------------

Create a page for your CMS:

.. code-block:: php

    // src/Acme/BasicCmsBundle/DataFixtures/PHPCR/LoadPageData.php

    namespace Acme\BasicCmsBundle\DataFixtures\PHPCR;

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

and add some posts:

.. code-block:: php

    // src/Acme/BasicCmsBundle/DataFixtures/PHPCR/LoadPostData.php

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

and:

.. code-block:: bash

    $ php app/console doctrine:phpcr:fixtures:load

You should now have some data in your content repository.

.. note::

    The classes above use ``NodeHelper::createPath`` to create the paths ``/cms/posts`` and ``/cms/pages``,
    this is exactly what the initializer did -- why do the classes do it again? This is a known issue which
    is currently being worked on - the data fixtures loader will erase the database and it will **not** call
    the initializer, so when using data fixtures it is currentl necessary to manually create the paths.

Automatic Routing
-----------------

The routes (URLs) to your content will be automatically created and updated using the RoutingAutoBundle. This
bundle is very powerful and quite complicated. For a full a full explanation refer to the
`RoutingAutoBundle documentation`_.

Enable the Dynamic Router
~~~~~~~~~~~~~~~~~~~~~~~~~

The RoutingAutoBundle uses the CMFs `RoutingBundle`_ which enables routes to be provided from a database (as opposed
to being provided from ``routing.[yml|xml|php]`` files for example).

Add the following to your application configuration:

.. code-block:: yaml

    # /app/config/config.yml

    # ...
    cmf_routing:
        chain:
            routers_by_id:
                cmf_routing.dynamic_router: 20
                router.default: 100
        dynamic:
            enabled: true
            persistence:
                phpcr:
                    enabled: true
                    route_basepath: /cms/routes

Auto Routing Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~

Create the following file in your applications configuration directory:

.. code-block:: yaml

    # app/config/routing_auto.yml

    cmf_routing_auto:
        auto_route_mapping:
            Acme\BasicCmsBundle\Document\Page:
                content_path:
                    pages:
                        provider:
                            name: specified
                            path: /cms/routes/page
                        exists_action:
                            strategy: use
                        not_exists_action:
                            strategy: create
                content_name:
                    provider:
                        name: content_method
                        method: getTitle
                    exists_action:
                        strategy: auto_increment
                        pattern: -%d
                    not_exists_action:
                        strategy: create

            Acme\BasicCmsBundle\Document\Post:
                content_path:
                    blog_path:
                        provider:
                            name: specified
                            path: /cms/routes/post
                        exists_action:
                            strategy: use
                        not_exists_action:
                            strategy: create
                    date:
                        provider:
                            name: content_datetime
                            method: getDate
                            
                            strategy: use
                        not_exists_action:
                            strategy: create
                content_name:
                    provider:
                        name: content_method
                        method: getTitle
                    exists_action:
                        strategy: auto_increment
                        pattern: -%d
                    not_exists_action:
                        strategy: create

This will configure the routing auto system to automatically create and update routes for both the ``Page`` and ``Post``
documents. Let me shortly explain what the configuration for ``Post`` does:

* The ``content_path`` key represents the parent path of the content, e.g. ``/if/this/is/a/path`` then the ``content_path``
  reperesents ``/if/this/is/a``.
    * Each element under ``content_path`` reperesents a section of the URL.
    * The first element ``block_path`` uses a *provider* which *specifies* a path. If that path exists then we will do
      nothing (i.e. we will *use* it).
    * The second element uses the ``content_datetime`` provider, which will use a ``DateTime`` object returned from
      the specified method on the content object (the ``Post``) and create a path from it, e.g. ``2013/10/13``.
* The ``content_name`` key represents the last part of the path, e.g. ``path`` from ``/if/this/is/a/path``.

Now we will need to include this configuration:

.. code-block:: yaml

    # app/config/config.yml
    imports:
        # ...
        - { resource: routing_auto.yml }


Now reload the fixtures::

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

The routes have been automatically created!

.. note::

    What are those numbers? These are node names which have been created automatically by the PHPCR-ODM. Normally
    you would assign a descriptive name (e.g. ``my-first-post``).

Sonata Admin
------------

The `Sonata Admin`_ bundle will provide our administration interface.

Configure Sonata
~~~~~~~~~~~~~~~~

Enable the Sonata related bundles to your kernel:

.. code-block:: php

    // app/AppKernel.php

    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            $bundles = array(
                // ...
                new Sonata\BlockBundle\SonataBlockBundle(),
                new Sonata\jQueryBundle\SonatajQueryBundle(),
                new Knp\Bundle\MenuBundle\KnpMenuBundle(),
                new Sonata\DoctrinePHPCRAdminBundle\SonataDoctrinePHPCRAdminBundle(),
                new Sonata\AdminBundle\SonataAdminBundle(),
            );

            // ...
        }
    }

and publish your assets (ommit ``--symlink`` if you use Windows!):

.. code-block:: bash

    $ php app/console assets:install --symlink web/

Sonata requires the ``sonata_block`` bundle to be configured in your main configuration:

.. code-block:: yaml

    # app/config/config.yml

    # ...
    sonata_block:
        default_contexts: [cms]
        blocks:
            # Enable the SonataAdminBundle block
            sonata.admin.block.admin_list:
                contexts:   [admin]


and it needs the following entries in your routing file:

.. code-block:: yaml

    # app/config/routing.yml

    admin:
        resource: '@SonataAdminBundle/Resources/config/routing/sonata_admin.xml'
        prefix: /admin

    _sonata_admin:
        resource: .
        type: sonata_admin
        prefix: /admin

Great, now have a look at http://localhost:8000/admin/dashboard

No translations? Uncomment the translator in the configuration file::

    translator:      { fallback: %locale% }

Notice that the routing bundles administration class has been automatically registered - since your
routes will be handled autmatically disable this:

.. code-block:: yaml

    # app/config/config.yml

    cmf_routing:
        ...
        dynamic:
            ...
            persistence:
                phpcr:
                    ...
                    use_sonata_admin: false

Creating the Admin Classes
~~~~~~~~~~~~~~~~~~~~~~~~~~

Create the following admin classes, first for the ``Page`` document:

.. code-block:: php

    // src/Acme/BasicCmsBundle/Admin/PageAdmin.php

    namespace Acme\BasicCmsBundle\Admin;

    use Sonata\DoctrinePHPCRAdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Datagrid\ListMapper;
    use Sonata\AdminBundle\Form\FormMapper;

    class PageAdmin extends Admin
    {
        protected function configureListFields(ListMapper $listMapper)
        {
            $listMapper
                ->addIdentifier('title', 'text')
            ;
        }

        protected function configureFormFields(FormMapper $formMapper)
        {
            $formMapper
                ->with('form.group_general')
                ->add('title', 'text')
                ->add('content', 'textarea')
            ->end();
        }

        public function prePersist($document)
        {
            $parent = $this->getModelManager()->find(null, '/cms/pages');
            $document->setParent($parent);
        }

        protected function configureDatagridFilters(DatagridMapper $datagridMapper)
        {
            $datagridMapper->add('title', 'doctrine_phpcr_string');
        }

        public function getExportFormats()
        {
            return array();
        }
    }

and then for the ``Post`` document - as you have already seen this document is almost identical to the ``Page`` document,
so it extends the ``PageAdmin`` class to avoid code duplication:

.. code-block:: php

    // src/Acme/BasicCmsBundle/Admin/PostAdmin.php

    namespace Acme\BasicCmsBundle\Admin;

    use Sonata\DoctrinePHPCRAdminBundle\Admin\Admin;
    use Sonata\AdminBundle\Datagrid\DatagridMapper;
    use Sonata\AdminBundle\Datagrid\ListMapper;
    use Sonata\AdminBundle\Form\FormMapper;

    class PostAdmin extends PageAdmin
    {
        protected function configureFormFields(FormMapper $formMapper)
        {
            parent::configureFormFields($formMapper);

            $formMapper
                ->with('form.group_general')
                ->add('date', 'date')
            ->end();
        }
    }

Now we just need to add the register these classes in the dependency injection container configuraiton:

.. code-block:: xml

        <!-- src/Acme/BasicCmsBundle/Resources/services.xml -->

        <service id="acme.basiccms.admin.page" class="Acme\BasicCmsBundle\Admin\PageAdmin">

            <call method="setRouteBuilder">
                <argument type="service" id="sonata.admin.route.path_info_slashes" />
            </call>

            <tag
                name="sonata.admin"
                manager_type="doctrine_phpcr"
                group="Basic CMS"
                label="Page"
            />
            <argument/>
            <argument>Acme\BasicCmsBundle\Document\Page</argument>
            <argument>SonataAdminBundle:CRUD</argument>
        </service>

        <service id="acme.basiccms.admin.post" class="Acme\BasicCmsBundle\Admin\PostAdmin">

            <call method="setRouteBuilder">
                <argument type="service" id="sonata.admin.route.path_info_slashes" />
            </call>

            <tag
                name="sonata.admin"
                manager_type="doctrine_phpcr"
                group="Basic CMS"
                label="Blog Posts"
            />
            <argument/>
            <argument>Acme\BasicCmsBundle\Document\Post</argument>
            <argument>SonataAdminBundle:CRUD</argument>
        </service>

Check it out at http://localhost:8000/admin/dashboard

The Frontend
------------

Go to the URL http://localhost:8000/page/home in your browser - this should be our page, but it says
that it cannot find a controller.

Lets map a default controller for all instances of ``Page``::

        controllers_by_class:
            Acme\BasicCmsBundle\Document\Page: Acme\BasicCmsBundle\Controller\BasicController::pageAction

Now create the action in the default controller - we will pass the ``Page`` object and all the ``Posts`` to the
view:

.. code-block:: php

    // src/Acme/BasicCmsBundle/Controller/DefaultController.php

    //..

    class DefaultController extends Controller
    {
        // ...

        /**
         * @Template()
         */
        public function pageAction($contentDocument)
        {
            $dm = $this->get('doctrine_phpcr')->getManager();
            $posts = $dm->getRepository('Acme\BasicCmsBundle\Document\Post')->findAll();
            return array('page' => $contentDocument);
        }
    }

The ``Page`` object is passed automatically as ``$contentDocument``.

Add a corresponding twig template:

.. code-block:: jinja

    <h1>{{ page.title }}</h1>
    <p>{{ page.content|raw }}</p>
    <h2>Our Blog Posts</h2>
    <ul>
        {% for post in posts %}
            <li><a href="{{ path(post) }}">{{ post.title }}</a></li>
        {% endfor %}
    </ul>

Now have another look at: http://localhost:8000/page/home

Notice what is happening with the post routes - we pass the ``Post`` object to
the ``path`` helper and because it implements the
``RouteReferrersReadInterface`` it find the dynamic routes in our database and
generate the URL.

Click on a ``Post`` and you will have the same error that you had before when
viewing the page at ``/home``.

You should now have enough knowledge to finish this off as you like:

* Add the ``Post`` class to the controllers_by_type configuration setting in
  the configuration and route it to a new action in the controller.
* Create a new template for the ``Post``.
* Maybe you want to create a layout and make everything look good.

In Part II we will add a menu using the MenuBundle based on the ``Page``
documents.

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
