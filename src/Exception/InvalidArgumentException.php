<?php

namespace aPajo\MultiTenancyBundle\Exception;

use Symfony\Component\Mailer\Exception\ExceptionInterface;

class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
