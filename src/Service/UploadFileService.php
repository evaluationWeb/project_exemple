<?php

namespace App\Service;

use Mithridatem\Routing\Http\FileRequestInterface;

class UploadFileService
{
    public function __construct(private string $uploadDirectory) {}

    public function uploadFile(FileRequestInterface $fileRequestInterface) {}
}
