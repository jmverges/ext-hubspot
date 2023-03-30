<?php

declare(strict_types=1);

namespace T3G\Hubspot\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use T3G\Hubspot\Service\ImportCtaService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CtaApiController
{
    /**
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function updateCtaAction(ServerRequestInterface $request): ResponseInterface
    {
        //https://omnia.test/typo3/update-hubspot-ctas?secret=123123&hubspot_updated_at=1670450913000&hubspot_guid=55fc3b22-4f31-4fef-be5c-23bf01f53289&name=2022.12 | PUBLIC | Emergent | Co-branded flyer&hubspot_cta_code=<!--HubSpot Call-to-Action Code --><span class="hs-cta-wrapper" id="hs-cta-wrapper-55fc3b22-4f31-4fef-be5c-23bf01f53289"><span class="hs-cta-node hs-cta-55fc3b22-4f31-4fef-be5c-23bf01f53289" id="hs-cta-55fc3b22-4f31-4fef-be5c-23bf01f53289"><!--[if lte IE 8]><div id="hs-cta-ie-element"></div><![endif]--><a href="https://cta-redirect.hubspot.com/cta/redirect/44873/55fc3b22-4f31-4fef-be5c-23bf01f53289" target="_blank" rel="noopener"><img class="hs-cta-img" id="hs-cta-img-55fc3b22-4f31-4fef-be5c-23bf01f53289" style="border-width:0px;" src="https://no-cache.hubspot.com/cta/default/44873/55fc3b22-4f31-4fef-be5c-23bf01f53289.png"  alt="OMNIA Partners Emergent partnership flyer"/></a></span><script charset="utf-8" src="https://js.hscta.net/cta/current.js"></script><script type="text/javascript"> hbspt.cta.load(44873, '55fc3b22-4f31-4fef-be5c-23bf01f53289', {"useNewLoader":"true","region":"na1"}); </script></span><!-- end HubSpot Call-to-Action Code -->
        $params = $request->getQueryParams();
        if ($params['secret'] == self::getSecret()) {
            $overwrite = true;//isset($params['overwrite']) ? (bool)$params['overwrite'] : false;
            $cta = [
                $params['hubspot_updated_at'],
                $params['hubspot_guid'],
                $params['name'],
                $params['hubspot_cta_code'],
            ];

            $action = ImportCtaService::importCta($cta, $overwrite);

            $response = new JsonResponse([$action => $cta], 200, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Methods' => 'OPTIONS, GET, POST',
                'Access-Control-Allow-Headers' => 'Content-Type, Depth, User-Agent, X-File-Size, X-Requested-With, If-Modified-Since, X-File-Name, Cache-Control',
            ]);

            return $response;
        }
        return new JsonResponse(['error' => 'invalid secret token']);
    }

    /**
     * @return int
     */
    protected static function getSecret()
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        return (int)$extensionConfiguration->get('hubspot', 'secret');
    }


}
