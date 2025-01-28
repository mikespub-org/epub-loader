<?php

/**
 * GoogleDriveHandler class
 */

namespace Marsender\EPubLoader\Storage\Google;

use Marsender\EPubLoader\Handlers\StorageHandler;
use Marsender\EPubLoader\RequestHandler;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\ReadOnly\ReadOnlyFilesystemAdapter;
use Masbug\Flysystem\GoogleDriveAdapter;
use Exception;

class GoogleDriveHandler extends StorageHandler
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
            case 'google_drive':
                $result = $this->google_drive();
                break;
            case 'google_callback':
                $result = $this->google_callback();
                break;
            default:
                $result = $this->$action();
        }
        return $result;
    }

    /**
     * Summary of google_drive
     * @return array<mixed>|null
     */
    public function google_drive()
    {
        $folderId = $this->request->get('authorId');
        $fileId = $this->request->get('fileId') ?? $folderId;
        $cacheFile = null;
        if (!empty($this->cacheDir)) {
            $makeDir = $this->cacheDir . '/google_drive';
            if (!is_dir($makeDir) && !mkdir($makeDir, 0o755, true)) {
                throw new Exception('Cannot create directory: ' . $makeDir);
            }
            $cacheFile = $makeDir . '/getfiles.json';
            if (file_exists($cacheFile)) {
                $result = json_decode(file_get_contents($cacheFile), true);
                if (empty($fileId) && empty($folderId)) {
                    return $result;
                }
                if (!empty($result['folders'][$folderId])) {
                    $result['folderId'] = $folderId;
                    $result['folderDetails'] = $result['folders'][$folderId];
                    if ($fileId != $folderId) {
                        $result['fileId'] = $fileId;
                        foreach ($result['folders'][$folderId]['children'] as $file) {
                            if ($file['id'] == $fileId) {
                                $result['fileDetails'] = $file;
                                break;
                            }
                        }
                    }
                    return $result;
                }
                $result['fileId'] = $fileId;
                foreach ($result['files'] as $file) {
                    if ($file['id'] == $fileId) {
                        $result['fileDetails'] = $file;
                        break;
                    }
                }
                return $result;
            }
        }

        if (empty($_SESSION['access_token'])) {
            // @todo let RequestHandler know we handled this
            putenv('PHPUNIT_TESTING=1');

            $redirect_uri = $this->getActionUrl('google_callback');
            header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
            return null;
        }

        $client = $this->getClient();
        $client->setAccessToken($_SESSION['access_token']);

        $service = $this->getService($client);

        // bottom-up approach finding epub books first
        $files = $this->getBottomUpByBooks($service);
        // @todo top-down approach starting with library folder id or path

        if (!empty($cacheFile)) {
            $result = ['files' => $files, 'folders' => $this->folders];
            file_put_contents($cacheFile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
        return ['files' => $files];
    }

    /**
     * Summary of google_callback
     * @return array<mixed>|null
     */
    public function google_callback()
    {
        // @todo let RequestHandler know we handled this
        putenv('PHPUNIT_TESTING=1');

        $client = $this->getClient();
        $code = $this->request->get('code');

        if (empty($code)) {
            $auth_url = $client->createAuthUrl();
            header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
            return null;
        }

        $client->authenticate($code);
        $_SESSION['access_token'] = $client->getAccessToken();

        $redirect_uri = $this->getActionUrl('google_drive');
        header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
        return null;
    }

    /**
     * Summary of google_test
     * @return string|null
     */
    public function google_test()
    {
        if (empty($_SESSION['access_token'])) {
            // @todo let RequestHandler know we handled this
            putenv('PHPUNIT_TESTING=1');

            $redirect_uri = $this->getActionUrl('google_callback');
            header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
            return null;
        }

        $client = $this->getClient();
        $client->setAccessToken($_SESSION['access_token']);

        $service = $this->getService($client);
        /**
        $adapter = $this->getAdapter($service);
        $contents = $adapter->listContents('', true);
        foreach ($contents as $id => $item) {
            var_dump(json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
         */
        $this->getTopDownByLibrary($service);
        return 'done';
    }

    /**
     * Summary of getConfig
     * @return array<mixed>
     */
    protected function getConfig()
    {
        $auth = $this->request->getAuth();
        return $auth['Google']['Drive'];
    }

    /**
     * Summary of getClient
     * @return Client
     */
    protected function getClient()
    {
        $redirectUrl = $this->getActionUrl('google_callback');
        if (!str_contains($redirectUrl, '://')) {
            var_dump($_SERVER);
        }
        $config = $this->getConfig();
        $client = new Client();
        //$client->setApplicationName($this->request->getAppName());
        //$client->setDeveloperKey($config['api_key']);
        $client->setAuthConfig($config['client_secret']);
        $client->addScope(Drive::DRIVE_READONLY);
        //$client->addScope(Drive::DRIVE_FILE);
        $client->setRedirectUri($redirectUrl);
        $client->setAccessType('offline');        // offline access
        $client->setIncludeGrantedScopes(true);   // incremental auth
        return $client;
    }

    /**
     * Summary of getService
     * @param Client $client
     * @return Drive
     */
    protected function getService($client)
    {
        $service = new Drive($client);
        return $service;
    }

    /**
     * Summary of getAdapter
     * @param Drive $service
     * @return GoogleDriveAdapter
     */
    protected function getAdapter($service)
    {
        $config = $this->getConfig();
        $root = $config['root'] ?? null;
        $adapter = new GoogleDriveAdapter($service, $root);
        return $adapter;
    }

    /**
     * Summary of getFilesystem
     * @param Drive $service
     * @return Filesystem
     */
    protected function getFilesystem($service)
    {
        // The internal adapter
        $adapter = $this->getAdapter($service);
        // Turn it into a read-only adapter
        $adapter = new ReadOnlyFilesystemAdapter($adapter);
        // Instantiate the filesystem
        $filesystem = new Filesystem($adapter);
        return $filesystem;
    }

    /**
     * Top-down approach starting with library folder id or path
     * @param Drive $service
     * @return array<mixed>
     */
    protected function getTopDownByLibrary($service)
    {
        $path = '.';
        $recursive = true;
        $filesystem = $this->getFilesystem($service);
        try {
            $listing = $filesystem->listContents($path, $recursive);

            /** @var \League\Flysystem\StorageAttributes $item */
            foreach ($listing as $item) {
                $path = $item->path();

                if ($item instanceof \League\Flysystem\FileAttributes) {
                    // handle the file
                    echo 'File: ' . $path . "<br>";
                    echo json_encode($item) . "<br>";
                } elseif ($item instanceof \League\Flysystem\DirectoryAttributes) {
                    // handle the directory
                    echo 'Dir: ' . $path . "<br>";
                    echo json_encode($item) . "<br>";
                }
            }
        } catch (FilesystemException $exception) {
            // handle the error
            echo $exception->getMessage();
        }
        return [];
    }

    /**
     * Bottom-up approach finding epub books first
     * @param Drive $service
     * @return array<mixed>
     */
    protected function getBottomUpByBooks($service)
    {
        $files = $this->getEpubList($service);

        $this->folders = [];
        foreach ($files as $file) {
            foreach ($file->parents as $parentId) {
                if (!empty($this->folders[$parentId])) {
                    continue;
                }
                $this->folders[$parentId] = $this->getParentFolderList($service, $parentId);
            }
        }

        return $files;
    }

    /**
     * Summary of getDatabaseList
     * @param Drive $service
     * @return array<mixed>
     */
    protected function getDatabaseList($service)
    {
        $query = ['q' => "name = 'metadata.db'"];
        return $this->listFiles($service, $query);
    }

    /**
     * Summary of getEpubList
     * @param Drive $service
     * @return array<mixed>
     */
    protected function getEpubList($service)
    {
        $query = ['q' => "mimeType='application/epub+zip'"];
        //$query = ['q' => "name contains '.epub'"];
        return $this->listFiles($service, $query);
    }

    /**
     * Summary of getCoverList
     * @param Drive $service
     * @return array<mixed>
     */
    protected function getCoverList($service)
    {
        $query = ['q' => "name = 'cover.jpg'"];
        return $this->listFiles($service, $query);
    }

    /**
     * Summary of getMetadataList
     * @param Drive $service
     * @return array<mixed>
     */
    protected function getMetadataList($service)
    {
        $query = ['q' => "name = 'metadata.opf'"];
        return $this->listFiles($service, $query);
    }

    /**
     * Summary of getParentFolderList
     * @param Drive $service
     * @param string $parentId
     * @param int $level with Library > Authors > Titles (123) > Title - Author.epub
     * @return array<mixed>
     */
    protected function getParentFolderList($service, $parentId, $level = 3)
    {
        echo "Looking for parent $parentId<br>";
        $folder = $this->getFile($service, $parentId);
        if ($level > 0) {
            foreach ($folder->parents as $ancestorId) {
                if (!empty($this->folders[$ancestorId])) {
                    continue;
                }
                $this->folders[$ancestorId] = $this->getParentFolderList($service, $ancestorId, $level - 1);
            }
        }

        $query = ['q' => "'$parentId' in parents"];
        $children = $this->listFiles($service, $query);
        return ['folder' => $folder, 'children' => $children];
    }

    /**
     * Summary of getAllFoldersList
     * @param Drive $service
     * @return array<mixed>
     */
    protected function getAllFoldersList($service)
    {
        $query = ['q' => "mimeType='application/vnd.google-apps.folder'"];
        return $this->listFiles($service, $query);
    }

    /**
     * Summary of listFiles
     * @param Drive $service
     * @param array<mixed> $query
     * @return array<mixed>
     */
    protected function listFiles($service, $query = [])
    {
        $fileList = [];
        $pageToken = null;
        $params = [
            'q' => "name = 'invalid.file'",
            'spaces' => 'drive',
            'pageToken' => $pageToken,
            'fields' => 'nextPageToken, files(id, kind, name, mimeType, parents, thumbnailLink, webViewLink, webContentLink, properties)',
            // For cover.jpg thumbnailLink = https://lh3.googleusercontent.com/drive-storage/AJQWtBMoh2yAONKrFAbcJ528EArN7Qbnh9xsfuMpd57OnrTbQNXY3HKJGKM2_4-6W_AZmK99V0NmAuHGtm9XLdVS9wcJsxx1pQBAZ2hQp7lhXVJLPw=s220
        ];
        $params = array_replace($params, $query);
        do {
            $params['pageToken'] = $pageToken;
            $response = $service->files->listFiles($params);
            $fileList = array_merge($fileList, $response->getFiles());
            $pageToken = $response->getNextPageToken();
        } while ($pageToken != null);

        return $fileList;
    }

    /**
     * Summary of getFile
     * @param Drive $service
     * @param string $fileId
     * @return DriveFile
     */
    protected function getFile($service, $fileId)
    {
        $response = $service->files->get($fileId, ['fields' => 'id, kind, name, mimeType, parents, webViewLink, properties']);
        return $response;
    }
}
