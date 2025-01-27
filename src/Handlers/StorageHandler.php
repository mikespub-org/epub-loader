<?php

/**
 * StorageHandler class
 */

namespace Marsender\EPubLoader\Handlers;

use Marsender\EPubLoader\ActionHandler;

class StorageHandler extends ActionHandler
{
    /**
     * Summary of storage
     * @return array<mixed>
     */
    public function storage()
    {
        $storage = [
            'google_drive' => 'Google Drive (Personal) Mapping',
            //'microsoft_onedrive' => 'Microsoft OneDrive (Personal) Mapping',
        ];

        return ['storage' => $storage];
    }
}
