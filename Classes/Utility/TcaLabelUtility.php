<?php

declare(strict_types=1);


namespace T3G\Hubspot\Utility;

class TcaLabelUtility
{
    public function createCtaLabel(array &$parameters): void
    {
        $parameters['title'] = $parameters['row']['name'] . ' (v' . $parameters['row']['version'] . ')';
    }
}
