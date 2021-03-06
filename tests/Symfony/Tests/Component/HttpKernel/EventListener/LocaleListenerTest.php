<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Tests\Component\HttpKernel\EventListener;

use Symfony\Component\HttpKernel\EventListener\LocaleListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class LocaleListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultLocaleWithoutSession()
    {
        $listener = new LocaleListener('fr');
        $event = $this->getEvent($request = Request::create('/'));

        $listener->onKernelRequest($event);
        $this->assertEquals('fr', $request->getLocale());
    }

    public function testDefaultLocaleWithSession()
    {
        $request = Request::create('/');
        session_name('foo');
        $request->cookies->set('foo', 'value');

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\Session', array('get'), array(), '', true);
        $session->expects($this->once())->method('get')->will($this->returnValue('es'));
        $request->setSession($session);

        $listener = new LocaleListener('fr');
        $event = $this->getEvent($request);

        $listener->onKernelRequest($event);
        $this->assertEquals('es', $request->getLocale());
    }

    public function testLocaleFromRequestAttribute()
    {
        $request = Request::create('/');
        session_name('foo');
        $request->cookies->set('foo', 'value');

        $request->attributes->set('_locale', 'es');
        $listener = new LocaleListener('fr');
        $event = $this->getEvent($request);

        // also updates the session _locale value
        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\Session', array('set', 'get'), array(), '', true);
        $session->expects($this->once())->method('set')->with('_locale', 'es');
        $session->expects($this->once())->method('get')->with('_locale')->will($this->returnValue('es'));
        $request->setSession($session);

        $listener->onKernelRequest($event);
        $this->assertEquals('es', $request->getLocale());
    }

    public function testLocaleSetForRoutingContext()
    {
        // the request context is updated
        $context = $this->getMock('Symfony\Component\Routing\RequestContext');
        $context->expects($this->once())->method('setParameter')->with('_locale', 'es');

        $router = $this->getMock('Symfony\Component\Routing\Router', array('getContext'), array(), '', false);
        $router->expects($this->once())->method('getContext')->will($this->returnValue($context));

        $request = Request::create('/');

        $request->attributes->set('_locale', 'es');
        $listener = new LocaleListener('fr', $router);
        $listener->onKernelRequest($this->getEvent($request));
    }

    private function getEvent(Request $request)
    {
        return new GetResponseEvent($this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'), $request, HttpKernelInterface::MASTER_REQUEST);
    }
}
