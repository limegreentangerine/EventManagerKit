<?php

namespace Concrete\Package\EventManagerKit;

use Concrete\Core\Entity\Package;
use ClassKit\Package\PackageController;
use Concrete\Core\Support\Facade\Events;
use Concrete\Core\Asset\{Asset, AssetList};
use Concrete\Core\Database\Connection\Connection;
use ClassKit\Package\Traits\{BlockTrait, PageTrait};
use Concrete\Core\Page\{Page, Single as SinglePage};
use EventManagerKit\Frontend\{EventsEndpoint, IcsEndpoint, SitemapListener};

class Controller extends PackageController
{
    use BlockTrait;
    use PageTrait;

    /**
     * The packages handle.
     * Note that this must be unique in the
     * entire concrete5 package ecosystem.
     *
     * @var string
     */
    protected $pkgHandle = 'event_manager_kit';

    /**
     * The packages version.
     *
     * @var string
     */
    protected $pkgVersion = '1.0.5';

    /**
     * The minimum Concrete version compatible with the package.
     * Override this value according to the minimum required version for your package.
     *
     * @var string
     */
    protected $appVersionRequired = '9.5.0';

    /**
     * The minimum PHP version compatible with the package.
     * Override this value according to the minimum required version for your package.
     *
     * @var string
     * @var string
     */
    protected $phpVersionRequired = '8.4';

    /**
     * Package service providers to register.
     *
     * eg. 'Concrete\Package\PackageHandle\Src\Providers\PackageServiceProvider'
     *
     * @var array
     */
    protected $providers = [];

    /**
     * An array describing the package dependencies.
     * Keys are package handles.
     * Values may be:
     * - false: this package can't be installed if the other package is already installed.
     * - true: this package can't be installed of the other package is not installed
     * - a string: this package can't be installed of the other package is not installed or it's installed with an older version
     * - an array with two strings, representing the minimum and the maximum version of the other package to be installed.
     *
     * @var array
     *
     * @example [
     *     // This package can't be installed if a package with handle other_package_1 is already installed.
     *     'other_package_1' => false,
     *     // This package can't be installed if a package with handle other_package_2 is not installed.
     *     'other_package_2' => true,
     *     // This package can't be installed if a package with handle other_package_3 is not installed, or it has a version prior to 1.0
     *     'other_package_3' => '1.0',
     *     // This package can't be installed if a package with handle other_package_4 is not installed, or it has a version prior to 2.0, or it has a version after 2.9
     *     'other_package_4' => ['2.0', '2.9'],
     * ]
     */
    protected $packageDependencies = [
        'class_kit' => true,
    ];

    /**
     * Package class autoloader registrations
     * The package install helper class, included with this boilerplate,
     * is activated by default.
     *
     * @see https://goo.gl/4wyRtH
     * @var array
     */
    protected $pkgAutoloaderRegistries = [
        'src' => '\EventManagerKit',
    ];

    /**
     * Package tasks to register.
     *
     * eg. 'task_handle' => \PackageHandle\Command\Task\Controller\TaskHandleController::class,
     *
     * @var array
     */
    protected $tasks = [];

    public function getPackageName()
    {
        return t('Event Manager');
    }

    public function getPackageDescription()
    {
        return t('Manage multilingual events in the dashboard and show them on the site.');
    }

    public function installOrUpgrade(Package $pkg)
    {
        // Entities are installed from src/Entity by the package install.
        $this->autoInstallBlocks($pkg);

        $pages = [
            '' => [t('Event Manager'), t('Event Manager Dashboard')],
            '/events' => [t('Events'), t('Manage events')],
            '/event_types' => [t('Event Types'), t('Manage event types')],
            '/event_tags' => [t('Event Tags'), t('Manage event tags')],
            '/locations' => [t('Locations'), t('Manage locations')],
            '/import' => [t('Import'), t('Import events from CSV')],
        ];
        $db = $this->app->make(Connection::class);

        // Public event page, /events/{slug}; kept out of navigation menus.
        $events = $this->addSinglePage('/events', $pkg, t('Events'), t('Public event page'));
        $events->setAttribute('exclude_nav', true);

        foreach ($pages as $suffix => [$name, $description]) {
            $path = '/dashboard/event_manager' . $suffix;
            $this->addSinglePage($path, $pkg, $name, $description);

            // A page created before its view file existed keeps an empty
            // cFilename and renders blank; write it directly.
            $page = Page::getByPath($path);
            if (!$page->getCollectionFilename()) {
                $db->update('Pages', ['cFilename' => SinglePage::getPathToNode($path, $pkg)], ['cID' => $page->getCollectionID()]);
            }
        }
    }

    public function on_start()
    {
        parent::on_start();

        $al = AssetList::getInstance();
        $al->register('javascript', 'event_manager_kit/events', 'js/events.js', ['position' => Asset::ASSET_POSITION_FOOTER, 'minify' => false, 'combine' => true], 'event_manager_kit');
        $al->registerGroup('event_manager_kit/events', [['javascript', 'event_manager_kit/events']]);
    }

    public function registerRoutes(): void
    {
        // Public JSON endpoint the blocks fetch events from. Exclude it from CDN caching.
        $router = $this->app->make('router');
        $router->register(EventsEndpoint::PATH, EventsEndpoint::class . '::events', 'event_manager_kit.events', [], [], '', [], ['GET']);
        $router->register(IcsEndpoint::EVENT_PATH, IcsEndpoint::class . '::event', 'event_manager_kit.ics', ['slug' => '[^/]+'], [], '', [], ['GET']);
        $router->register(IcsEndpoint::FEED_PATH, IcsEndpoint::class . '::feed', 'event_manager_kit.feed', [], [], '', [], ['GET']);
    }

    public function registerEvents(): void
    {
        Events::addListener('on_sitemap_xml_ready', SitemapListener::addEvents(...));
    }
}
