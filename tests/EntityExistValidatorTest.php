<?php

declare(strict_types=1);

namespace Test\Happyr\Validator\Constraint;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Happyr\Validator\Constraint\EntityExist;
use Happyr\Validator\Constraint\EntityExistValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Mapping\Loader\AttributeLoader;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class EntityExistValidatorTest extends TestCase
{
    /** @var MockObject */
    private $entityManager;

    /** @var MockObject */
    private $context;

    /** @var EntityExistValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $this->context = $this->getMockBuilder(ExecutionContextInterface::class)->getMock();

        $this->validator = new EntityExistValidator($this->entityManager);
        $this->validator->initialize($this->context);
    }

    public function testValidateWithWrongConstraint(): void
    {
        $this->expectException(\LogicException::class);
        $this->validator->validate('foo', new NotNull());
    }

    public function testValidateWithNoEntity(): void
    {
        $constraint = new EntityExist();

        $this->expectException(\LogicException::class);
        $this->validator->validate('foobar', $constraint);
    }

    public function testValidateValidEntity(): void
    {
        $this->context->expects($this->never())->method('buildViolation');
        $constraint = new EntityExist();
        $constraint->entity = 'App\Entity\User';

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => 'foobar'])
            ->willReturn('my_user');

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with('App\Entity\User')
            ->willReturn($repository);

        $this->validator->validate('foobar', $constraint);
    }

    /**
     * @dataProvider getEmptyOrNull
     */
    public function testValidateSkipsIfValueEmptyOrNull($value): void
    {
        $this->context->expects($this->never())->method('buildViolation');
        $constraint = new EntityExist();
        $constraint->entity = 'App\Entity\User';

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->exactly(0))
            ->method('findOneBy')
            ->with(['id' => $value])
            ->willReturn('my_user');

        $this->entityManager
            ->expects($this->exactly(0))
            ->method('getRepository')
            ->with('App\Entity\User')
            ->willReturn($repository);

        $this->validator->validate($value, $constraint);
    }

    public function getEmptyOrNull(): \Generator
    {
        yield [''];
        yield [null];
    }

    public function testValidateValidEntityWithCustomProperty(): void
    {
        $this->context->expects($this->never())->method('buildViolation');
        $constraint = new EntityExist();
        $constraint->entity = 'App\Entity\User';
        $constraint->property = 'uuid';

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => 'foobar'])
            ->willReturn('my_user');

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with('App\Entity\User')
            ->willReturn($repository);

        $this->validator->validate('foobar', $constraint);
    }

    public function testValidateInvalidEntity(): void
    {
        $violationBuilder = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
        $violationBuilder->method('setParameter')->will($this->returnSelf());

        $this->context->expects($this->once())->method('buildViolation')->willReturn($violationBuilder);
        $constraint = new EntityExist();
        $constraint->entity = 'App\Entity\User';

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->validator->validate(1, $constraint);
    }

    /**
     * @requires PHP 8
     */
    public function testValidateFromAttribute()
    {
        $this->context->expects($this->never())->method('buildViolation');

        $classMetadata = new ClassMetadata(EntityDummy::class);
        (new AttributeLoader())->loadClassMetadata($classMetadata);

        [$constraint] = $classMetadata->properties['user']->constraints;

        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => 'foobar'])
            ->willReturn('my_user');

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with('App\Entity\User')
            ->willReturn($repository);

        $this->validator->validate('foobar', $constraint);
    }
}

class EntityDummy
{
    #[EntityExist(entity: 'App\Entity\User', property: 'uuid')]
    private $user;
}
