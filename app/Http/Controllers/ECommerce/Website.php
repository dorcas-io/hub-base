<?php

namespace App\Http\Controllers\ECommerce;

use App\Dorcas\Hub\Utilities\DomainManager\HostingManager;
use App\Dorcas\Hub\Utilities\DomainManager\WhmApiClient;
use App\Dorcas\Hub\Utilities\UiResponse\UiResponse;
use Hostville\Dorcas\Sdk;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class Website extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->data['page']['title'] = 'Website Builder';
        $this->data['page']['header'] = ['title' => 'Website Builder'];
        $this->data['breadCrumbs'] = [
            'showHome' => true,
            'crumbs' => [
                ['text' => 'eCommerce', 'href' => route('apps.ecommerce')],
                ['text' => 'eCommerce', 'href' => route('apps.ecommerce'), 'isActive' => true],
            ]
        ];
        $this->data['currentPage'] = 'ecommerce';
        $this->data['selectedSubMenu'] = 'website';
    }
    
    /**
     * @param Request $request
     * @param Sdk     $sdk
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, Sdk $sdk)
    {
        $this->setViewUiResponse($request);
        $this->data['authParams'] = [
            'token' => $request->user()->getDorcasSdk()->getAuthorizationToken()
        ];
        $company = $this->getCompany();
        $config = (array) $company->extra_data;
        $this->data['domains'] = $domains = $this->getDomains($sdk);
        $this->data['isHostingSetup'] = !empty($config['hosting']) && !empty($domains) && $domains->count() > 0;
        return view('ecommerce.website', $this->data);
    }
    
    /**
     * @param Request $request
     * @param Sdk     $sdk
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \League\Flysystem\FileNotFoundException
     * @throws \League\Flysystem\UnreadableFileException
     * @throws \Exception
     */
    public function post(Request $request, Sdk $sdk)
    {
        try {
            $domain = $this->getDomains($sdk)->first();
            # get the domain to be setup
            if (empty($domain)) {
                throw new \RuntimeException('We could not find any of your purchased domains.');
            }
            $whm = self::getWhmClient($domain->hosting_box_id);
            # instantiate the client
            list($username, $run) = $this->suggestAccountName($whm, $domain->domain);
            $planName = $this->confirmPlanExists($whm);
            $password = WhmApiClient::generatePassword();
            $hosting = $whm->createAccount($username, $domain->domain, $planName, $password, $request->user()->email);
            # create the hosting account
            $extraData = (array) $this->getCompany()->extra_data;
            if (empty($extraData['hosting'])) {
                $extraData['hosting'] = [];
            }
            $extraData['hosting'][] = [
                'domain' => $domain->domain,
                'hosting_box_id' => $domain->hosting_box_id,
                'username' => $username,
                'password' => $password,
                'home_dir' => '/home/' . $username,
                'options' => $hosting
            ];
            # add a hosting entry
            $sdk->createCompanyService()->addBodyParam('extra_data', $extraData)->send('PUT');
            # set updates to the account
            $response = (material_ui_html_response(['Successfully setup web hosting on domain ' . $domain->domain]))->setType(UiResponse::TYPE_SUCCESS);
            
        } catch (\RuntimeException $e) {
            $response = (material_ui_html_response([$e->getMessage()]))->setType(UiResponse::TYPE_ERROR);
        }
        return redirect(url()->current())->with('UiResponse', $response);
    }
    
    /**
     * Returns the plan name.
     *
     * @param WhmApiClient $whm
     *
     * @return null|string
     */
    protected function confirmPlanExists(WhmApiClient $whm): ?string
    {
        $plans = collect($whm->listHostingPackages()['pkg'] ?? []);
        # get the list of packages
        $plan = $plans->filter(function ($data) {
            return ends_with($data['name'], 'dorcas-hosting-classic');
        })->first();
        if (!empty($plan)) {
            return $plan['name'];
        }
        $plan = $whm->addHostingPackage(
            'dorcas-hosting-basic',
            5120,
            5120,
            1024,
            WhmApiClient::CPANEL_THEME_PAPER_LANTERN,
            'en',
            10,
            300,
            100,
            1
        );
        return $plan['pkg'];
    }
    
    /**
     * Suggests a free username to use on the server.
     *
     * @param WhmApiClient $whm
     * @param string       $domain
     *
     * @return array
     */
    protected function suggestAccountName(WhmApiClient $whm, string $domain): array
    {
        $username = null;
        $runs = 0;
        while (true) {
            ++$runs;
            $username = $whm->suggestHostingAccountUsername($domain);
            if ($whm->verifyAccountUsername($username)) {
                # we just found a free username
                break;
            }
        }
        return [$username, $runs];
    }
    
    /**
     * Creates an instance of the WHM APi client.
     *
     * @param string $hostingBoxId
     *
     * @return WhmApiClient
     * @throws \League\Flysystem\FileNotFoundException
     * @throws \League\Flysystem\UnreadableFileException
     */
    public static function getWhmClient(string $hostingBoxId): WhmApiClient
    {
        $hostingManager = new HostingManager();
        $server = $hostingManager->getServers()->where('id', $hostingBoxId)->first();
        if (empty($server)) {
            throw new \RuntimeException('Could not find the hosting server information. Please contact support.');
        }
        return WhmApiClient::newInstance($server->api_endpoint, $server->api_id, $server->api_secret);
    }
}
