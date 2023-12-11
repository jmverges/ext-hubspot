<?php
declare(strict_types=1);

namespace T3G\Hubspot\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImportCtaService
{
    public static function importCta($cta, $overwrite = false)
    {
        [$updatedAt, $guid, $name, $code] = $cta;

        $table = 'tx_hubspot_cta';
        $conn = static::createConnectionPool()->getConnectionForTable($table);
        $record = [
            'hubspot_updated_at' => $updatedAt,
            'hubspot_guid' => $guid,
            'name' => $name,
            'hubspot_cta_code' => $code,
        ];

        $databaseRecord = static::fetchCtaRecord((string) $guid, $name);

        $isNewFormat = ctype_digit($guid);

        $uid = 'NEW123';
        if ($databaseRecord) {
            $uid = $databaseRecord['uid'];
            if ($databaseRecord['hubspot_updated_at'] >= $updatedAt && !$overwrite) {
                return 'skip';
            }

            if ($databaseRecord['version'] < 2 && $isNewFormat) {
                // Our input is a new format CTA but the resolved DB record is old format. Migrate the record.
                static::backupCtaRecord($databaseRecord['hubspot_guid']);
                $record['hubspot_guid'] = $guid;
            }
        } else {
            $record['pid'] = static::getStoragePid();
        }

        if ($isNewFormat) {
            $record['version'] = 2;
        }

        $dataHandler = self::getDataHandlerInstance();

        $dataHandler->start(
            [
                $table => [
                    $uid => $record
                ]
            ],
            []
        );

        if (!$dataHandler->checkModifyAccessList($table)) {
            return 'no_permissions';
        }

        if ($dataHandler->process_datamap() === false) {
            return 'error';
        }

        if ($uid == 'NEW123') {
            return 'created';
        }

        return 'updated';
    }

    /**
     * @param string $guid
     * @param string $name
     * @return array|null
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected static function fetchCtaRecord(string $guid, string $name): ?array
    {
        $queryBuilder = static::createConnectionPool()->getQueryBuilderForTable('tx_hubspot_cta');
        $expression = $queryBuilder->expr();
        return $queryBuilder->select('uid', 'hubspot_updated_at', 'hubspot_guid', 'name', 'hubspot_cta_code', 'version')
            ->from('tx_hubspot_cta')
            ->where(
                $expression->orX(
                    // Either a match for explicit guid wuth version flag of 2, meaning the CTA is a new format CTA
                    $expression->andX(
                        $expression->eq('hubspot_guid', $queryBuilder->createNamedParameter($guid)),
                        $expression->eq('version', 2)
                    ),
                    // Or a match for name and version flag of 1, meaning we look for a legacy CTA record in our DB so
                    // we can on-the-fly migrate it to the new format
                    $expression->andX(
                        $expression->eq('name', $queryBuilder->createNamedParameter($name)),
                        $expression->eq('version', 1)
                    )
                )
            )
            // order by version DESC so any matching NEW format record takes priority if more than one record found
            ->orderBy('version', 'DESC')
            ->executeQuery()
            ->fetchAssociative() ?: null;
    }

    protected static function backupCtaRecord(string $guid): void
    {
        $fields = implode(
            ',',
            [
                'uid',
                'pid',
                'tstamp',
                'crdate',
                'cruser_id',
                'deleted',
                'hidden',
                'starttime',
                'endtime',
                'hubspot_guid',
                'hubspot_updated_at',
                'name',
                'hubspot_cta_code',
                'version',
            ]
        );
        $connection = static::createConnectionPool()->getConnectionForTable('tx_hubspot_cta_backup');
        $connection->exec(
            'REPLACE INTO tx_hubspot_cta_backup (' .
            $fields .
            ') (SELECT ' .
            $fields .
            ' FROM tx_hubspot_cta WHERE hubspot_guid = \'' .
            $guid .
            '\')'
        );
    }

    protected static function getStoragePid(): int
    {
        /** @var ExtensionConfiguration $extensionConfiguration */
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        return (int)$extensionConfiguration->get('hubspot', 'storagePid');
    }

    protected static function getDataHandlerInstance(): DataHandler
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->enableLogging = false;
        $dataHandler->isImporting = true;
        return $dataHandler;
    }

    protected static function createConnectionPool(): ConnectionPool
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        return $connectionPool;
    }
}
