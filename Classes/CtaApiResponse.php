<?php

declare(strict_types=1);

namespace T3G\Hubspot;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3Fluid\Fluid\View\ViewInterface;

class CtaApiResponse
{
    /**
     * @var ViewInterface
     */
    protected $view;

    public function __construct()
    {
        $this->view = GeneralUtility::makeInstance(TemplateView::class);
        $this->view->setTemplateRootPaths([
            'EXT:omniapartners_template/Resources/Private/Templates/'
        ]);
        $this->view->setLayoutRootPaths([
            'EXT:omniapartners_template/Resources/Private/Layouts/'
        ]);
        $this->view->setPartialRootPaths([
            'EXT:omniapartners_template/Resources/Private/Partials/',
            'EXT:omniapartners_patterns/Resources/Private/Partials/'
        ]);
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:omniapartners_template/Resources/Private/Templates/HubspotSync/Default.html'));
    }

    public function render($data, $status = 200, array $headers = []): ResponseInterface
    {
        $this->view->assignMultiple($data);
        return new HtmlResponse($this->view->render(), $status, $headers);
    }
}
