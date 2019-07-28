<?php

declare(strict_types=1);

namespace Happyr\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * @author Radoje Albijanic <radoje.albijanic@gmail.com>
 */
final class EntityExist extends Constraint
{
    public $message = 'Entity "%entity%" with property "%property%": "%value%" does not exist.';
    public $property = 'id';
    public $entity;
}
