<?php

namespace App\Command;

use App\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class GenerateAppKeyCommand extends Command
{

    protected static $defaultName = 'app:generate-app-key';
    protected static $defaultDescription = 'Generate a new app key';

    public function __construct(
        private readonly Config $config
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $hasKey = $this->config->get('app_key');

        if ($hasKey){
            $helper = $this->getHelper('question');

            $question = new ConfirmationQuestion(
                "Voulez-vous génerer une nouvelle key? (toute les signatures ayant l'ancienne key vont être perdu)",
                false
            );

            if (! $helper->ask($input, $output, $question)){
                return Command::SUCCESS;
            }
        }

        $key = base64_encode(random_bytes(32));
        $envFilePath = __DIR__ . "/../../.env";

        if(! file_exists($envFilePath)){
            throw new \RuntimeException("Impossible de trouver le fichier .ENV");
        }

        $envFileContent = file_get_contents($envFilePath);

        $pattern = '/^APP_KEY=.*/m';

        if(preg_match($pattern, $envFileContent)){
            $envFileContent = preg_replace($pattern, 'APP_KEY=' . $key, $envFileContent);
        } else {
            $envFileContent .= PHP_EOL . "APP_KEY=$key";
        }

        file_put_contents($envFilePath, $envFileContent);

        $output->write("La nouvelle key a bien été générée");

        return Command::SUCCESS;
    }

}