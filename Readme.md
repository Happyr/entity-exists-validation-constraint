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
     * @EntityExist(entity="App\Entity\User")
     *
     * @var int User's id property
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

In case you are using other constraints to validate the property before entity should be checked in the database (like `@Assert\Uuid`) you should use [Group sequence](https://symfony.com/doc/current/validation/sequence_provider.html) in order to avoid 500 errors from Doctrine mapping.

```php
namespace App\Message\Command;

use Happyr\Validator\Constraint\EntityExist;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"EmailUser", "DatabaseCall"})
 */
final class EmailUser
{
    /**
     * @Assert\NotBlank
     * @Assert\Uuid
     * @EntityExist(entity="App\Entity\User", groups={"DatabaseCall"}, property="uuid")
     *
     * @var string Uuid
     */
    private $user;

    // ...
```

Sometimes we may also need to check not only whether the entity exists, but also the state (for example is an article published).
To do this need to create a new Constraint, which extends from EntityExist instead of the default Symfony Constraint class.

```php
<?php

namespace App\Validator;

use Happyr\Validator\Constraint\EntityExist;

/**
 * @Annotation
 */
class ArticleEntityExist extends EntityExist
{
}

```

Next, we create a validator class. Default convention for Symfony is the fully qualified name of the constraint class suffixed with "Validator".
See `Symfony\Component\Validator\Constraint::validatedBy` for more information. You can overwrite this method to return another fully qualified name of the validator class.
In validator class you can inject other services, but not forget to call parent constructor.
The parent validator `EntityExistValidator` calls the method `checkEntity` with fetched entity.
The default implementation always returns true, so you need to overwrite this method.

```php
<?php

namespace App\Validator;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Happyr\Validator\Constraint\EntityExistValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ArticleEntityExistValidator extends EntityExistValidator
{
    protected function checkEntity(object $entity): bool
    {
        if (!$entity instanceof Article) {
            throw new UnexpectedValueException($entity, Article::class);
        }

        return $entity->isPublished();
    }
}
```

Another example of validator with constructor injection:

```php
<?php

namespace App\Offer\Validator;

use App\Offer\Entity\Offer;
use App\SalesChannel\SalesChannelContext;
use Doctrine\ORM\EntityManagerInterface;
use Happyr\Validator\Constraint\EntityExistValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class OfferEntityExistValidator extends EntityExistValidator
{
    private SalesChannelContext $salesChannelContext;

    public function __construct(SalesChannelContext $salesChannelContext, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
        
        $this->salesChannelContext = $salesChannelContext;
    }

    protected function checkEntity(object $entity): bool
    {
        if (!$entity instanceof Offer) {
            throw new UnexpectedValueException($entity, Offer::class);
        }

        return $entity->isVisible()
            && $this->salesChannelContext->getCurrentSalesChannel()->equals($entity->getSalesChannel());
    }
}

```

Finally, in your entity or DTO, use a new constraint instead of `EntityExist`.

```php
<?php

namespace App\Dto;

use App\Validator\ArticleEntityExist;
use Symfony\Component\Validator\Constraints as Assert;

class CommentArticleRequestDto
{
    /**
     * @Assert\NotBlank()
     * @ArticleEntityExist(entity="App\Entity\Article")
     */
    public int $articleId;

    //....
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
