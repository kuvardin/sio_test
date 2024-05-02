<?php

declare(strict_types=1);

namespace App\Command;

use App\Api\v1\ApiMethod;
use App\Api\v1\ApiReflection;
use App\Localization\LocaleCode;
use App\Util\TemplatesEngine;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use RuntimeException;

#[AsCommand(
    name: 'app:generate-api-library',
    description: 'Add a short description for your command',
)]
class GenerateApiLibraryCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result_file_path = ROOT_DIR . '/public/api.js';

        $language_code = LocaleCode::RU;

        $models_errors = [];
        $models = ApiReflection::getApiModels($models_errors, $language_code);

        /** @var ApiMethod[]|string[] $methods */
        $methods = [];
        $methods_errors = [];
        $methods_data = [];

        ApiReflection::getApiMethods($methods_data, $methods_errors);

        /** @var int[][] $methods_error_codes */
        $methods_error_codes = [];
        foreach ($methods_data as $method_name => $method_data) {
            $methods[$method_name] = $method_data['class'];
            $methods_error_codes[$method_name] = $method_data['errors'];
        }

        $template_path = ROOT_DIR . '/templates/api/api_js_library.php';
        $content = TemplatesEngine::render($template_path, [
            'title' => 'API v1',
            'language_code' => $language_code,
            'models' => $models,
            'models_errors' => $models_errors,
            'methods' => $methods,
            'methods_errors' => $methods_errors,
            'methods_error_codes' => $methods_error_codes,
        ]);

        if (!preg_match('|^\s*<script>(.*?)</script>\s*$|su', $content, $match)) {
            throw new RuntimeException('Incorrect result');
        }

        $f = fopen($result_file_path, 'w');
        fwrite($f, trim($match[1]));
        fclose($f);

        return Command::SUCCESS;
    }
}
