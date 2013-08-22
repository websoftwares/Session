<?php
use Websoftwares\Session, Lboy\Session\SaveHandler\Memcached;
/**
 * Class SessionTest
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{
    private $reflection = null;

    public function setUp()
    {
        $this->session = new Session;
        $this->reflection = new \ReflectionClass($this->session);
    }

    public function testInstantiateAsObjectSucceeds()
    {
        $this->assertInstanceOf('Websoftwares\Session',  $this->session);
    }

    public function testSessionStart()
    {
        $this->assertFalse($this->session->active());
        $this->assertTrue($this->session->start());
        $this->assertFalse($this->session->start());
        $this->assertTrue($this->session->active());
        $this->assertTrue($this->session->close());
    }

    public function testSessionClose()
    {
        $this->assertFalse($this->session->active());
        $this->assertFalse($this->session->close());
        $this->assertTrue($this->session->start());
        $this->assertTrue($this->session->close());
        $this->assertFalse($this->session->active());
    }

    public function testSessionDestroy()
    {
        $this->assertFalse($this->session->active());
        $this->assertFalse($this->session->destroy());
        $this->assertTrue($this->session->start());
        $this->assertTrue($this->session->destroy());
        $this->assertFalse($this->session->active());
        $this->assertEquals($_SESSION, array());
    }

    public function testSessionName()
    {
        $method = $this->getMethod('name');
        $actual = $method->invoke($this->session);
        $this->assertEquals('PHPSESSID',$actual);
        $new = $method->invoke($this->session, 'test');
        $this->assertEquals('PHPSESSID',$new);
        $this->assertEquals('test',$method->invoke($this->session));
    }

    public function testSessionMeta()
    {
        session_start();
        $method = $this->getMethod('meta');
        $actual = $method->invoke($this->session);
        $expected = array('meta' => array('name' => 'test','created' => time(),'updated' =>time()));
        $this->assertEquals($expected, $_SESSION);
        sleep(1);
        $expected['meta']['updated'] = time();
        $method->invoke($this->session);
        $this->assertEquals($expected, $_SESSION);
        $this->session->destroy();
    }

    public function testArryAccess()
    {
        $this->assertNull($this->session['test']);
        $expected = array('meta' => array('name' => 'test','created' => time(),'updated' =>time()));
        $this->assertEquals($expected, $_SESSION);
        $value = 'sessionValue';
        $expected['test'] = $value;
        $this->session['test'] = $value;
        $this->assertEquals($expected, $_SESSION);
        $this->assertEquals($this->session['test'], $value);
        unset($this->session['test']);
        $this->assertEquals($_SESSION, array('meta' => array('name' => 'test','created' => time(),'updated' =>time())));
        $this->session->close();
    }

    public function testId()
    {
        $this->session->start();
        $expected = 'A,-1';
        $this->session->id('A,-1');
        $this->assertEquals($expected, $this->session->id());
        $this->session->close();
    }

    public function testRegenerate()
    {
        $this->assertFalse($this->session->regenerate());
        $this->session->start();
        $this->assertTrue($this->session->regenerate());
        $this->assertTrue($this->session->regenerate('delete'));
        $this->session->destroy();
    }

    public function getMethod($method)
    {
        $method = $this->reflection->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }

    public function testHandlerInjectionSucceeds()
    {
        // create connection to memcached
        $memcached = new \Memcached();
        $memcached->addServer('localhost', 11211);

        // register handler (PHP 5.3 compatible)
        $handler = new Memcached($memcached);

        $session = new Session($handler);
        $session->start();
        $session['serialisation'] = 'should be in json';
        $this->assertEquals('should be in json', $session['serialisation']);
        $session->close();
        $session->destroy();
    }

    public function testDefaultPropertyValuesSucceeds()
    {
        $options = $this->getProperty('options',$this->session);
        $expected = array('meta' => true,'name' => null,'lifetime' => 0,'path' => '/','domain' => null,'secure' => true,'httponly' => false);

        $this->assertInternalType('array', $options);
        $this->assertEquals($expected, $options);
    }

    public function getProperty($property)
    {
        $property = $this->reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($this->session);
    }

    public function setProperty($property, $value)
    {
        $property = $this->reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->setValue($this->session, $value);
    }

    /**
     * @expectedException invalidArgumentException
     */
    public function testSessionNameFails()
    {
        $method = $this->getMethod('name');
        $actual = $method->invoke($this->session, array('test'));
    }

    /**
     * @expectedException invalidArgumentException
     */
    public function testSessionIdFails()
    {
        $this->session->start();
        $this->session->id('A*');
        $this->session->destroy();
    }
}
