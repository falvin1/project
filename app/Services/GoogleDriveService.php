<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;

class GoogleDriveService
{
    protected $drive;


    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path(config('services.google.drive_credentials_path')));
        $client->addScope(Drive::DRIVE_FILE);
        
        $this->drive = new Drive($client);
    }

    public function uploadFile($fileContent, $fileName)
    {
        $folderId = config('services.google.folder_id');
        
        $fileMetadata = new Drive\DriveFile([
            'name' => $fileName,
            'parents' => [$folderId],
        ]);

        $file = $this->drive->files->create($fileMetadata, [
            'data' => $fileContent,
            'mimeType' => "application/pdf",
            'uploadType' => 'multipart',
        ]);

        return $file->id;
    }
}
