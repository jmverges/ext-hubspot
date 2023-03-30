<?php
declare(strict_types = 1);

namespace T3G\Hubspot\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImportCtaService
{
    public static function importCta($cta, $overwrite = false)
    {
        $process = true;
        $table = 'tx_hubspot_cta';
        $conn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $record = [
            'pid' => static::getStoragePid(),
            'hubspot_updated_at' => $cta[0],
            'hubspot_guid' => $cta[1],
            'name' => $cta[2],
            'hubspot_cta_code' => $cta[3],
        ];


        $uid = 'NEW123';
        $databseRecord = $conn->select(['uid', 'hubspot_updated_at'], $table, [
            'hubspot_guid' => $record['hubspot_guid'],
            'pid' => static::getStoragePid()
        ])->fetchAllAssociative();
        if (count($databseRecord) > 0) {
            if ($databseRecord[0]['uid'] > 0) {
                $uid = $databseRecord[0]['uid'];
                if ($databseRecord[0]['hubspot_updated_at'] >= $cta[0] && $overwrite == false) {
                    $process = false;
                }
            }
        }

        if ($process == true) {
            $dataHandler = self::getDataHandlerInstance([
                $table => [
                    $uid => $record
                ]
            ]);
            $result = $dataHandler->process_datamap();

            if($result === false){
                return 'error';
            }

            if ($uid == 'NEW123') {
                return 'created';
            }
            return 'updated';
        }

        if ($process == false) {
            return 'skip';
        }

    }
    /**
     * @return int
     */
    protected static function getStoragePid()
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        return (int)$extensionConfiguration->get('hubspot', 'storagePid');
    }

    protected static function getDataHandlerInstance($dataMap): DataHandler
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->enableLogging = false;
        $dataHandler->isImporting = true;
        $dataHandler->start($dataMap, []);
        return $dataHandler;
    }
}
