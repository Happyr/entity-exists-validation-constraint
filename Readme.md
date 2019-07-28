# Symfony validator for Entity Exist

[![Latest Version](https://img.shields.io/github/release/happyr/entity-exists-validation-constraint.svg?style=flat-square)](https://github.com/happyr/entity-exists-validation-constraint/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/happyr/entity-exists-validation-constraint.svg?style=flat-square)](https://travis-ci.org/happyr/entity-exists-validation-constraint)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/happyr/entity-exists-validation-constraint.svg?style=flat-square)](https://scrutinizer-ci.com/g/happyr/entity-exists-validation-constraint)
[![Quality Score](https://img.shields.io/scrutinizer/g/happyr/entity-exists-validation-constraint.svg?style=flat-square)](https://scrutinizer-ci.com/g/happyr/entity-exists-validation-constraint)
[![Total Downloads](https://img.shields.io/packagist/dt/happyr/entity-exists-validation-constraint.svg?style=flat-square)](https://packagist.org/packages/happyr/entity-exists-validation-constraint)

A small validator that verifies that an Entity actually exists. This is especially useful if you use Symfony Messenger
component. Now you can safely validate the message and put it on a queue for processing later.


```php
namespace App\Message\Command;

use Happyr\Validator\Constraint\EntityExist;
use Symfony\Component\Validator\Constraints as Assert;

final class EmailUser
{
    /**
     * @Assert\NotBlank
     * @Assert\Uuid
     * @EntityExist(entity="App\Entity\User")
     *
     * @var string UUID to user's id property
     */
    private $user;

    /**
     * @Assert\NotBlank
     * @EntityExist(entity="App\Entity\Other", property="name")
     *
     * @var string The name of "Other". We use its "name" property. 
     */
    private $other;

    // ...
```

## Install

```console
composer require happyr/entity-exists-validation-constraint

```

Then register the services with:

```yaml
# config/packages/happyr_entity_exists_validator.yaml
services:
  Happyr\Validator\Constraint\EntityExistValidator:
    arguments: ['@doctrine.orm.entity_manager']
    tags: [ 'validator.constraint_validator' ]
```

## Note

The Validator will not produce a violation when value is empty. This means that you should most likely use it in
combination with `NotBlank`. 