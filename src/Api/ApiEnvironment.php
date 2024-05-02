<?php

declare(strict_types=1);

namespace App\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

class ApiEnvironment
{
    public function __construct(
        readonly public EntityManagerInterface $entity_manager,
        readonly public ValidatorInterface $validator,
        readonly public LoggerInterface $logger,
        readonly public ContainerBag $parameter_bag,

    ) {
    }
}
