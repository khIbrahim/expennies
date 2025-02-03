<?php

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;

class CsvUploadRequestValidator implements RequestValidatorInterface
{

    private const ALLOWED_MIMETYPE = 'text/csv';

    public function validate(array $data): array
    {
        /** @var ?UploadedFileInterface $file */
        $file = $data['csvFile'] ?? null;

        if(! $file){
            throw new ValidationException(['csvFile' => "Sélectionnez un fichier csv"]);
        }

        if($file->getError() !== UPLOAD_ERR_OK){
            throw new ValidationException(['csvFile' => "Erreur lors de l'upload du fichier", "error" => $file->getError()]);
        }

        $fileName = $file->getClientFilename();
        if(! preg_match('/^[a-zA-Z0-9\s._-]+$/', $fileName)){
            throw new ValidationException(['csvFile' => "Nom du fichier non valide"]);
        }

        if($file->getClientMediaType() != self::ALLOWED_MIMETYPE){
            throw new ValidationException(['csvFile' => "Type de fichier non valide"]);
        }

        $tmpFilePath = $file->getStream()->getMetadata('uri');
        $detector    = new FinfoMimeTypeDetector();
        $mimeType    = $detector->detectMimeTypeFromFile($tmpFilePath);
        if($mimeType != self::ALLOWED_MIMETYPE){
            throw new ValidationException(['csvFile' => "Type de fichier invalide"]);
        }

        return $data;
    }
}