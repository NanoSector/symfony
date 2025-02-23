<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Controller;

use Psr\Container\ContainerInterface as Psr11ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Tests\Controller\ContainerControllerResolverTest;

class ControllerResolverTest extends ContainerControllerResolverTest
{
    public function testGetControllerOnContainerAware()
    {
        $resolver = $this->createControllerResolver();
        $request = Request::create('/');
        $request->attributes->set('_controller', 'Symfony\Bundle\FrameworkBundle\Tests\Controller\ContainerAwareController::testAction');

        $controller = $resolver->getController($request);

        $this->assertInstanceOf(ContainerAwareController::class, $controller[0]);
        $this->assertInstanceOf(ContainerInterface::class, $controller[0]->getContainer());
        $this->assertSame('testAction', $controller[1]);
    }

    public function testGetControllerOnContainerAwareInvokable()
    {
        $resolver = $this->createControllerResolver();
        $request = Request::create('/');
        $request->attributes->set('_controller', 'Symfony\Bundle\FrameworkBundle\Tests\Controller\ContainerAwareController');

        $controller = $resolver->getController($request);

        $this->assertInstanceOf(ContainerAwareController::class, $controller);
        $this->assertInstanceOf(ContainerInterface::class, $controller->getContainer());
    }

    public function testContainerAwareControllerGetsContainerWhenNotSet()
    {
        class_exists(AbstractControllerTest::class);

        $controller = new ContainerAwareController();

        $container = new Container();
        $container->set(TestAbstractController::class, $controller);

        $resolver = $this->createControllerResolver(null, $container);

        $request = Request::create('/');
        $request->attributes->set('_controller', TestAbstractController::class.'::testAction');

        $this->assertSame([$controller, 'testAction'], $resolver->getController($request));
        $this->assertSame($container, $controller->getContainer());
    }

    public function testAbstractControllerGetsContainerWhenNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"Symfony\\Bundle\\FrameworkBundle\\Tests\\Controller\\TestAbstractController" has no container set, did you forget to define it as a service subscriber?');

        class_exists(AbstractControllerTest::class);

        $controller = new TestAbstractController(false);

        $container = new Container();
        $container->set(TestAbstractController::class, $controller);

        $resolver = $this->createControllerResolver(null, $container);

        $request = Request::create('/');
        $request->attributes->set('_controller', TestAbstractController::class.'::fooAction');

        $this->assertSame([$controller, 'fooAction'], $resolver->getController($request));
        $this->assertSame($container, $controller->setContainer($container));
    }

    public function testAbstractControllerServiceWithFqcnIdGetsContainerWhenNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"Symfony\\Bundle\\FrameworkBundle\\Tests\\Controller\\DummyController" has no container set, did you forget to define it as a service subscriber?');

        class_exists(AbstractControllerTest::class);

        $controller = new DummyController();

        $container = new Container();
        $container->set(DummyController::class, $controller);

        $resolver = $this->createControllerResolver(null, $container);

        $request = Request::create('/');
        $request->attributes->set('_controller', DummyController::class.'::fooAction');

        $this->assertSame([$controller, 'fooAction'], $resolver->getController($request));
        $this->assertSame($container, $controller->getContainer());
    }

    public function testAbstractControllerGetsNoContainerWhenSet()
    {
        class_exists(AbstractControllerTest::class);

        $controller = new TestAbstractController(false);
        $controllerContainer = new Container();
        $controller->setContainer($controllerContainer);

        $container = new Container();
        $container->set(TestAbstractController::class, $controller);

        $resolver = $this->createControllerResolver(null, $container);

        $request = Request::create('/');
        $request->attributes->set('_controller', TestAbstractController::class.'::fooAction');

        $this->assertSame([$controller, 'fooAction'], $resolver->getController($request));
        $this->assertSame($controllerContainer, $controller->setContainer($container));
    }

    public function testAbstractControllerServiceWithFcqnIdGetsNoContainerWhenSet()
    {
        class_exists(AbstractControllerTest::class);

        $controller = new DummyController();
        $controllerContainer = new Container();
        $controller->setContainer($controllerContainer);

        $container = new Container();
        $container->set(DummyController::class, $controller);

        $resolver = $this->createControllerResolver(null, $container);

        $request = Request::create('/');
        $request->attributes->set('_controller', DummyController::class.'::fooAction');

        $this->assertSame([$controller, 'fooAction'], $resolver->getController($request));
        $this->assertSame($controllerContainer, $controller->getContainer());
    }

    protected function createControllerResolver(?LoggerInterface $logger = null, ?Psr11ContainerInterface $container = null)
    {
        if (!$container) {
            $container = $this->createMockContainer();
        }

        return new ControllerResolver($container, $logger);
    }

    protected function createMockParser()
    {
        return $this->createMock(ControllerNameParser::class);
    }

    protected function createMockContainer()
    {
        return $this->createMock(ContainerInterface::class);
    }
}

class ContainerAwareController implements ContainerAwareInterface
{
    private ?ContainerInterface $container = null;

    public function setContainer(?ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    public function testAction()
    {
    }

    public function __invoke()
    {
    }
}

class DummyController extends AbstractController
{
    public function getContainer(): Psr11ContainerInterface
    {
        return $this->container;
    }

    public function fooAction()
    {
    }
}
