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
    /** @var array<mixed> */
    protected $getfiles = [];

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

        $this->getfiles = json_decode(file_get_contents($cacheFile), true);
        if (empty($folderId)) {
            $result['folders'] = $this->getTopLevelDirs();
            return $result;
        }

        $folder = $this->getFolderById($folderId);
        if (empty($folder)) {
            $result['folders'] = $this->getTopLevelDirs();
            return $result;
        }

        if (str_contains($folder['Path'], '/')) {
            $parentPath = dirname($folder['Path']);
            $parent = $this->getFolderByPath($parentPath);
            if (!empty($parent)) {
                $result['folders'][] = $parent;
            }
        }
        $result['folders'][] = $folder;
        $result['folderDetails'] = json_encode($folder, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        foreach ($this->getfiles as $file) {
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

    /**
     * Summary of getFolderById
     * @param string $folderId
     * @return array<mixed>|null
     */
    protected function getFolderById($folderId)
    {
        $folders = array_filter($this->getfiles, function ($file) use ($folderId) {
            return $file['ID'] == $folderId;
        });
        if (empty($folders)) {
            return null;
        }
        return reset($folders);
    }

    /**
     * Summary of getFolderByPath
     * @param string $folderPath
     * @return array<mixed>|null
     */
    protected function getFolderByPath($folderPath)
    {
        $folders = array_filter($this->getfiles, function ($file) use ($folderPath) {
            return $file['Path'] == $folderPath;
        });
        if (empty($folders)) {
            return null;
        }
        return reset($folders);
    }

    /**
     * Summary of getTopLevelDirs
     * @return array<mixed>
     */
    protected function getTopLevelDirs()
    {
        $folders = [];
        foreach ($this->getfiles as $file) {
            if ($file['IsDir'] && $file['Name'] == $file['Path']) {
                $folders[] = $file;
            }
        }
        return $folders;
    }
}
