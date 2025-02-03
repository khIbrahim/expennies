<?php

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;

class UploadedReceiptValidator implements RequestValidatorInterface
{

    public function validate(array $data): array
    {
        /** @var UploadedFileInterface|null $file */
        $file = $data['receipt'] ?? null;

        if(! $file){
            throw new ValidationException(['receipt' => "Sélectionnez un fichier"]);
        }

        if($file->getError() !== UPLOAD_ERR_OK){
            throw new ValidationException(['receipt' => "Erreur lors de l'upload du fichier", "error" => $file->getError()]);
        }

        $maxFileSize = 5 * 1024 * 1024;
        if($file->getSize() > $maxFileSize){
            throw new ValidationException(['receipt' => "Fichier trop large, maximum = 5 MB"]);
        }

        $fileName = $file->getClientFilename();
        if(! preg_match('/^[a-zA-Z0-9\s._-]+$/', $fileName)){
            throw new ValidationException(['receipt' => "Nom du fichier invalide"]);
        }

        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $tmpFilePath      = $file->getStream()->getMetadata('uri');

        if(! in_array($file->getClientMediaType(), $allowedMimeTypes)){
            throw new ValidationException(['receipt' => "Type de fichier non valide (doit être une image ou un document)"]);
        }

        $detector = new FinfoMimeTypeDetector();
        $mimeType = $detector->detectMimeTypeFromFile($tmpFilePath);
        if(! in_array($mimeType, $allowedMimeTypes)){
            throw new ValidationException(['receipt' => "Type de fichier invalide"]);
        }

        return $data;
    }
}