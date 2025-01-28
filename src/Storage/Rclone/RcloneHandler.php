<?php

/**
 * RcloneHandler class
 */

namespace Marsender\EPubLoader\Storage\Rclone;

use Marsender\EPubLoader\Handlers\StorageHandler;
use Marsender\EPubLoader\RequestHandler;
use Exception;

class RcloneHandler extends StorageHandler
{
    /** @var array<string, mixed> */
    protected $folders = [];

    /**
     * Summary of handle
     * @param string $action
     * @param RequestHandler $request
     * @return mixed
     */
    public function handle($action, $request)
    {
        // keep access token in session
        session_start();

        $this->request = $request;
        switch ($action) {
            case 'rclone_list':
                $result = $this->rclone_list();
                break;
            default:
                $result = $this->$action();
        }
        return $result;
    }

    /**
     * Summary of rclone_list
     * @return array<mixed>|null
     */
    public function rclone_list()
    {
        $cacheFile = null;
        if (!empty($this->cacheDir)) {
            $makeDir = $this->cacheDir . '/rclone';
            if (!is_dir($makeDir) && !mkdir($makeDir, 0o755, true)) {
                throw new Exception('Cannot create directory: ' . $makeDir);
            }
        }
        $remoteId = $this->request->get('authorId');
        $folderId = $this->request->get('folderId');
        $fileId = $this->request->get('fileId');

        $config = $this->getConfig();
        $remotes = array_keys($config);

        $result = [
            'remotes' => $remotes,
            'remoteId' => $remoteId,
            'folders' => [],
            'folderId' => $folderId,
            'files' => [],
            'fileId' => $fileId,
        ];
        if (empty($remoteId)) {
            return $result;
        }

        $cacheFile = $this->cacheDir . '/rclone/' . $remoteId . '/getfiles.json';
        if (!file_exists($cacheFile)) {
            // @todo run rcone lsjson -R --fast-list remote:"My Library/Books"
            return $result;
        }

        $getfiles = json_decode(file_get_contents($cacheFile), true);
        if (empty($folderId)) {
            $result['folders'] = $this->getTopLevelDirs($getfiles);
            return $result;
        }

        $folder = $this->getFolderById($getfiles, $folderId);
        if (empty($folder)) {
            $result['folders'] = $this->getTopLevelDirs($getfiles);
            return $result;
        }

        if (str_contains($folder['Path'], '/')) {
            $parentPath = dirname($folder['Path']);
            $parent = $this->getFolderByPath($getfiles, $parentPath);
            if (!empty($parent)) {
                $result['folders'][] = $parent;
            }
        }
        $result['folders'][] = $folder;
        $result['folderDetails'] = json_encode($folder, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        foreach ($getfiles as $file) {
            if (dirname($file['Path']) != $folder['Path']) {
                continue;
            }
            if ($file['IsDir']) {
                $result['folders'][] = $file;
                continue;
            }
            $result['files'][] = $file;
            if (!empty($fileId) && $fileId == $file['ID']) {
                $result['fileDetails'] = json_encode($file, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
        }
        return $result;
    }

    /**
     * Summary of rclone_test
     * @return array<mixed>|null
     */
    public function rclone_test()
    {
        $config = $this->getConfig();
        $remotes = array_keys($config);
        return ['remotes' => $remotes];
    }

    /**
     * Summary of getConfig
     * @return array<mixed>
     */
    protected function getConfig()
    {
        $auth = $this->request->getAuth();
        return $auth['rclone'];
    }

    protected function getFolderById($getfiles, $folderId)
    {
        $folders = array_filter($getfiles, function ($file) use ($folderId) {
            return $file['ID'] == $folderId;
        });
        if (empty($folders)) {
            return null;
        }
        return reset($folders);
    }

    protected function getFolderByPath($getfiles, $folderPath)
    {
        $folders = array_filter($getfiles, function ($file) use ($folderPath) {
            return $file['Path'] == $folderPath;
        });
        if (empty($folders)) {
            return null;
        }
        return reset($folders);
    }

    protected function getTopLevelDirs($getfiles)
    {
        $folders = [];
        foreach ($getfiles as $file) {
            if ($file['IsDir'] && $file['Name'] == $file['Path']) {
                $folders[] = $file;
            }
        }
        return $folders;
    }
}
