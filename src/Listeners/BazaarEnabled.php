<?php

namespace Flagrow\Bazaar\Listeners;

use Flagrow\Bazaar\Search\FlagrowApi;
use Flarum\Event\ConfigureWebApp;
use Flarum\Extension\ExtensionManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

class BazaarEnabled
{
    /**
     * @var ExtensionManager
     */
    protected $extensions;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var FlagrowApi
     */
    protected $client;

    public function __construct(ExtensionManager $extensions, SettingsRepositoryInterface $settings, FlagrowApi $client)
    {
        $this->extensions = $extensions;
        $this->settings = $settings;
        $this->client = $client;
    }

    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureWebApp::class, [$this, 'authenticate']);
    }

    /**
     * @param ConfigureWebApp $event
     */
    public function authenticate(ConfigureWebApp $event)
    {
        if (!$event->isAdmin()) {
            return;
        }

        $token = $this->settings->get('flagrow.bazaar.api_token');

        if (empty($token) && $this->extensions->isEnabled('flagrow-bazaar')) {
            $response = $this->client->post('/api/bazaar/beckons');

            $this->storeTokenFromRequest($response);
        }
    }

    /**
     * @param ResponseInterface $response
     */
    protected function storeTokenFromRequest(ResponseInterface $response)
    {
        if ($response->getStatusCode() !== 201) {
            return;
        }

        $tokens = $response->getHeader('Access-Token');
        $token = array_pop($tokens);

        if (empty($token)) {
            return;
        }

        $this->settings->set('flagrow.bazaar.api_token', $token);
    }
}
