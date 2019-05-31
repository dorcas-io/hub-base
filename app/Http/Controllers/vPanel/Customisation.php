<?php

namespace App\Http\Controllers\vPanel;

use GuzzleHttp\Psr7\Uri;
use Hostville\Dorcas\Sdk;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class Customisation extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->data = [
            'page' => ['title' => 'Customisation'],
            'header' => ['title' => 'Customisation'],
            'selectedMenu' => 'customisation',
        ];
    }
    
    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $this->setViewUiResponse($request);
        return view('vpanel.customisation', $this->data);
    }
    
    /**
     * @param Request $request
     * @param Sdk     $sdk
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post(Request $request, Sdk $sdk)
    {
        $this->validate($request, [
            'name' => 'nullable|string|max:80',
            'video_url' => 'nullable|string',
            'product_name' => 'nullable|string',
            'logo' => 'nullable|image|max:4096',
            'support_email' => 'nullable|email'
        ]);
        # validate the request
        $domain = $request->session()->get('domain');
        # get the domain
        try {
            $partner = !empty($domain->owner['data']['slug']) ? (object) $domain->owner['data'] : null;
            # set the partner, if it was resolved as such -- get the partner
            $extraConfig = (array) $partner->extra_data;
            # get the array config
            if (empty($partner)) {
                throw new \RuntimeException('Errors occurred while resolving your partner account.');
            }
            $resource = $sdk->createPartnerResource($partner->id)->addBodyParam('name', $request->input('name'));
            if ($request->has('logo')) {
                $file = $request->file('logo');
                $resource->addMultipartParam('logo', file_get_contents($file->getRealPath(), false), $file->getClientOriginalName());
            }
            if ($request->has('product_name')) {
                $extraConfig['hubConfig']['product_name'] = $request->input('product_name');
            }
            if ($request->has('support_email')) {
                $extraConfig['support_email'] = $request->input('support_email');
            }
            if ($request->has('video_url')) {
                # let's try to get the youtube ID
                if (!str_contains($request->input('video_url'), ['youtube.com', 'youtu.be'])) {
                    throw new \RuntimeException('Invalid Youtube URL provided.');
                }
                $prepend = !starts_with($request->input('video_url'), 'http') ? 'https://' : '';
                $uri = new Uri($prepend . $request->input('video_url'));
                if ($uri->getHost() === 'youtu.be') {
                    # we get the path
                    $extraConfig['welcome_video_id'] = substr($uri->getPath(), 1);
                } else {
                    # we get the query value for the "v" key
                    $query = [];
                    parse_str($uri->getQuery(), $query);
                    $extraConfig['welcome_video_id'] = $query['v'] ?? '';
                }
                $resource->addBodyParam('extra_data', $extraConfig);
            }
            $query = $resource->send('post');
            if (!$query->isSuccessful()) {
                $message = $response->errors[0]['title'] ?? '';
                throw new \RuntimeException('Failed while updating your partner information. '.$message);
            }
            Cache::forget('domain_' . $domain->prefix);
            $response = toast('Successfully saved your preferences.');
        } catch (\RuntimeException $e) {
            $response = toast($e->getMessage());
        }
        return redirect(url()->current())->with('UiToast', $response->json());
    }
}