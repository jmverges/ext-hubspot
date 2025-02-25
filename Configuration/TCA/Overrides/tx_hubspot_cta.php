<?php

// Compose label by appending "v1" or "v2" to the record's title, indicating the version of the CTA
$GLOBALS['TCA']['tx_hubspot_cta']['ctrl']['label_userFunc'] = \T3G\Hubspot\Utility\TcaLabelUtility::class . '->createCtaLabel';

// Necessary in order for "version" column to be included in record array passed to label userFunc
$GLOBALS['TCA']['tx_hubspot_cta']['ctrl']['label_alt'] = 'version';
