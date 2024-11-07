<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/hubspot.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Hubspot\Hooks\PageLayoutView;

use SevenShores\Hubspot\Exceptions\BadRequest;
use T3G\Hubspot\Domain\Repository\Hubspot\FormRepository;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders the content element preview for hubspot content elements in page module.
 */
class HubspotPreviewRenderer implements PageLayoutViewDrawItemHookInterface
{
    /**
     * Preprocesses the preview rendering of a content element of type "hubspot_form".
     *
     * @param \TYPO3\CMS\Backend\View\PageLayoutView $parentObject Calling parent object
     * @param bool $drawItem Whether to draw the item using the default functionality
     * @param string $headerContent Header content
     * @param string $itemContent Item content
     * @param array $row Record row of tt_content
     * @return void
     */
    public function preProcess(
        PageLayoutView &$parentObject,
        &$drawItem,
        &$headerContent,
        &$itemContent,
        array &$row
    ) {
        if ($row['CType'] === 'hubspot_form') {
            $drawItem = false;
            $this->renderHubspotFormPreview($itemContent, $row);
        }
        if ($row['CType'] === 'hubspot_cta') {
            $drawItem = false;
            $this->renderHubspotCtaPreview($itemContent, $row);
        }
    }

    /**
     * Transform form structure from hubspot API to field list.
     *
     * @param array $form
     * @return array
     */
    protected function getFormFieldLabels(array $form) : array
    {
        $formFieldGroups = $form['formFieldGroups'] ?? [];
        $fields = [];
        foreach ($formFieldGroups as $formFieldGroup) {
            if (isset($formFieldGroup['fields'])) {
                foreach ($formFieldGroup['fields'] as $field) {
                    $fields[] = $field['label'];
                }
            }
        }
        return $fields;
    }

    /**
     * Renders the hubspot form preview.
     *
     * @param string &$itemContent
     * @param array &$row
     */
    protected function renderHubspotFormPreview(string &$itemContent, array &$row)
    {
        try {
            if (!empty($row['hubspot_guid'])) {
                $hubspotFormRepository = GeneralUtility::makeInstance(FormRepository::class);
                $form = $hubspotFormRepository->getFormForPreview($row['hubspot_guid']);
                $itemContent .= '<p><strong>Hubspot Form:</strong> <br />' . $form['name'] . '</p>';
                $fields = $this->getFormFieldLabels($form);
                $itemContent .= '<p><strong>Fields:</strong> ' . implode(', ', $fields) . '</p>';
            } else {
                $itemContent .= '<div class="callout-warning">No form selected!</div>';
            }
        } catch (BadRequest $exception) {
            $itemContent .= '<div class="callout-warning">' . $exception->getMessage() . '</div>';
        }
    }

    /**
     * Renders the hubspot form preview.
     *
     * @param string &$itemContent
     * @param array &$row
     */
    protected function renderHubspotCtaPreview(string &$itemContent, array &$row)
    {
        if (!empty($row['hubspot_cta'])) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tx_hubspot_cta');
            $result = $queryBuilder
                ->select('hubspot_guid', 'name')
                ->from('tx_hubspot_cta')
                ->where(
                    $queryBuilder->expr()->eq('uid', $row['hubspot_cta'])
                )
                ->execute()->fetchAllAssociative();
            if(count($result)){
                $result = $result[0];
                $itemContent .= '<p><strong>Hubspot CTA NAME:</strong> <br />' . $result['name'] . '</p>';
                $itemContent .= '<p><strong>Hubspot Guid:</strong> <br />' . $result['hubspot_guid'] . '</p>';
            }
        } else {
            $itemContent .= '<div class="callout-warning">No CTA selected!</div>';
        }
    }
}
