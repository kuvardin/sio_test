<?php

declare(strict_types=1);

namespace App\Util;

use Throwable;

/**
 * Кастомный шаблонизатор
 *
 * В Symfony есть шиблонизатор Twig, которым я владею, но у меня были уже готовые шаблоны под этот шаблонизатор
 */
class TemplatesEngine
{
    public static function render(string $template_path, array $data): string
    {
        foreach ($data as $key => $value) {
            $$key = $value;
        }

        try {
            ob_start();
            require $template_path;
            return ob_get_clean();
        } catch (Throwable $exception) {
            ob_get_clean();
            throw $exception;
        }
    }
}
