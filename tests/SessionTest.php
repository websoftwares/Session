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

    /**
     * @runInSeparateProcess
     */
    public function testInstantiateAsObjectSucceeds()
    {
        $this->assertInstanceOf('Websoftwares\Session',  $this->session);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionStart()
    {
        $session = new Session;
        $this->assertFalse($session->active());
        $this->assertTrue($session->start());
        $this->assertFalse($session->start());
        $this->assertTrue($session->active());
        $this->assertTrue($session->destroy());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionClose()
    {
        $session = new Session;
        $this->assertFalse($session->active());
        $this->assertFalse($session->close());
        $this->assertTrue($session->start());
        $this->assertTrue($session->close());
        $this->assertFalse($session->active());
        $this->assertTrue($session->start());
        $this->assertTrue($session->destroy());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionDestroy()
    {
        $session = new Session;
        $this->assertFalse($session->active());
        $this->assertFalse($session->destroy());
        $this->assertTrue($session->start());
        $this->assertTrue($session->destroy());
        $this->assertFalse($session->active());
        $this->assertEquals($_SESSION, array());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionName()
    {
        $session = new Session;
        $method = $this->getMethod('name');
        $actual = $method->invoke($session);
        $this->assertEquals('PHPSESSID',$actual);
        $new = $method->invoke($session, 'test');
        $this->assertEquals('PHPSESSID',$new);
        $this->assertEquals('test',$method->invoke($session));
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionMeta()
    {
        $session = new Session;
        session_start();
        $method = $this->getMethod('meta');
        $actual = $method->invoke($session);
        $expected = array('meta' => array('name' => 'PHPSESSID','created' => time(),'updated' =>time()));
        $this->assertEquals($expected, $_SESSION);
        sleep(1);
        $expected['meta']['updated'] = time();
        $method->invoke($session);
        $this->assertEquals($expected, $_SESSION);
        $session->destroy();
    }

    /**
     * @runInSeparateProcess
     */
    public function testArryAccess()
    {
        $session = new Session;
        $this->assertNull($session['test']);
        $expected = array('meta' => array('name' => 'PHPSESSID','created' => time(),'updated' =>time()));
        $this->assertEquals($expected, $_SESSION);
        $value = 'sessionValue';
        $expected['test'] = $value;
        $session['test'] = $value;
        $this->assertEquals($expected, $_SESSION);
        $this->assertEquals($session['test'], $value);
        unset($session['test']);
        $this->assertEquals($_SESSION, array('meta' => array('name' => 'PHPSESSID','created' => time(),'updated' =>time())));
        $session->close();
    }

    /**
     * @runInSeparateProcess
     */
    public function testId()
    {
        $session = new Session;
        $session->start();
        $expected = 'A,-1';
        $session->id('A,-1');
        $this->assertEquals($expected, $session->id());
        $session->close();
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegenerate()
    {
        $session = new Session;
        $this->assertFalse($session->regenerate());
        $session->start();
        $this->assertTrue($session->regenerate());
        $this->assertTrue($session->regenerate('delete'));
        $session->destroy();
    }

    public function getMethod($method)
    {
        $method = $this->reflection->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @runInSeparateProcess
     */
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
}
