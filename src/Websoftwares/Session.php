<?php
namespace Websoftwares;
/**
 * Session class
 * PHP 5.3+ Session Class that accepts optional save handlers.
 *
 * @package Websoftwares
 * @license http://www.dbad-license.org/ DbaD
 * @version 0.0.3
 * @author Boris <boris@websoftwar.es>
 */
class Session implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $options = null;

    /**
     * __construct
     * @param object $storage
     * @param array  $options
     */
    public function __construct($storage = null,array $options = array())
    {
        // Set options
        $this->options = array_merge(
            array(
            // If enabled (default) extra meta data is added (name,created,updated)
            'meta' => true,
            // Provide custom session name
            'name' => null,
            // Lifetime of the session cookie, defined in seconds.
            // Smaller exploitation window for xss/csrf/clickjacking.
            'lifetime' => 0,
            // Path on the domain where the cookie will work. Use a single slash ('/') for all paths on the domain.
            'path' => '/',
            // Cookie domain, for example 'www.php.net'. To make cookies visible on all subdomains then the domain must be prefixed with a dot like '.php.net'.
            'domain' => null,
            // If TRUE cookie will only be sent over secure connections.
            // OWASP a9 violations.
            'secure' => true,
            // If set to TRUE then PHP will attempt to send the httponly flag when setting the session cookie.
            'httponly' => false
            ),
            $options
        );

        session_set_cookie_params(
            $this->options['lifetime'],
            $this->options['path'],
            $this->options['domain'],
            $this->options['secure'],
            $this->options['httponly']
        );

        // Disable transparent sid support
        ini_set('session.use_trans_sid', '0');

        // Only allow the session ID to come from cookies and nothing else.
        ini_set('session.use_only_cookies', '1');

        // Better entropy source
        // http://www.youtube.com/watch?v=YDW7kobM6Ik
        ini_set('session.entropy_file', "/dev/urandom");

        // Custom save handler
        $this->handler($storage);

        // http://www.php.net/manual/en/function.session-register-shutdown.php
        if (version_compare(phpversion(), '5.4.0', '>=')) {
            session_register_shutdown();
        } else {
            register_shutdown_function(array($this, 'close'));
        }
    }

    /**
     * start session
     *
     * @return boolean true if we can start session
     */
    public function start()
    {
        // Before we start session we must set session name
        if ($this->options['name']) {
            $this->name($this->options['name']);
        }

        if ($this->active()) {
            // Update meta data
            if ($this->options['meta']) {
                $this->meta();
            }

            return false;
        }

        session_start();
        // Create meta data
        if ($this->options['meta']) {
            $this->meta();
        }

        return true;
    }

    /**
     * destroy the current session
     * @see http://www.php.net/manual/en/function.session-destroy.php
     *
     * @return boolean
     */
    public function destroy()
    {
        if (! $this->active()) {
            return false;
        }
        // Unset all of the session variables.
        $_SESSION = array();

        if (ini_get('session.use_cookies')) {
            //  http://php.net/manual/en/function.session-get-cookie-params.php
            $params = session_get_cookie_params();
            // Clear session cookie
            setcookie(
                $this->name(), '', time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
                );
        }

        return session_destroy();
    }

    /**
     * close the session
     * @return boolean
     */
    public function close()
    {
        if (! $this->active()) {
            return false;
        } else {
            session_write_close();

            return true;
        }
    }

    /**
     * active find out if we have session
     * @see http://stackoverflow.com/questions/3788369/how-to-tell-if-a-session-is-active/7656468#7656468
     * @see http://www.php.net/manual/en/function.session-status.php
     *
     * @return boolean
     */
    public function active()
    {
        if (version_compare(phpversion(), '5.4.0', '>=') &&  session_status() !== PHP_SESSION_ACTIVE) {
              return false;
        }

        if (version_compare(phpversion(), '5.4.0', '<')) {
            $setting = 'session.use_trans_sid';
            $current = ini_get($setting);

            if (false === $current) {
                throw new UnexpectedValueException(sprintf('Setting %s does not exists.', $setting));
            }
            $result = @ini_set($setting, $current);

            return $result !== $current;
        }

        return true;
    }

    /**
     * id
     * @param  string $value
     * @return string
     */
    public function id($value = null)
    {
        if (! $this->active()) {
            return false;
        // Validate
        } elseif ($value && ! preg_match('/^[a-zA-Z0-9,\-]+$/', $value)) {
                throw new \InvalidArgumentException("Only alphanumeric, minus and comma characters allowed");
        }
        // Return previous/current session id
        return $value ? session_id($value) : session_id();
    }

    /**
     * regenerate
     * @see http://www.php.net/manual/en/function.session-regenerate-id.php
     *
     * @param  boolean $deleteSession
     * @return bool    true on success or false on failure.
     */
    public function regenerate($deleteSession = false)
    {
        // Cast to bool and return
        return session_regenerate_id((bool) $deleteSession);
    }

    /**
     * name
     * @see http://www.php.net/manual/en/function.session-name.php
     *
     * @param  string $value
     * @return string
     */
    protected function name($value = null)
    {
        // If we have value and function ctype_alnum is enabled
        if ($value && function_exists('ctype_alnum')) {
            // Check for alphanumeric
            if (! ctype_alnum($value)) {
                throw new \InvalidArgumentException("Only alphanumeric characters allowed");
            }
        // Alternative check for alphanumeric
        } elseif ($value && ! preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                throw new \InvalidArgumentException("Only alphanumeric characters allowed");
        }
        // Return previous/current session name
        return $value ? session_name($value) : session_name();
    }

    /**
     * meta add meta data to the session
     * @return void
     */
    protected function meta()
    {
        // Got meta data?
        $meta = isset($_SESSION['meta']);
        // Add meta data
        if (! $meta) {
            // Set the meta data in the session
           $_SESSION['meta'] = array(
                'name' => $this->name(),
                'created' => time(),
                'updated' => time()
            );
        } else {
            $_SESSION['meta']['updated'] = time();
        }
    }

    /**
     * handler
     * @see http://www.php.net/manual/en/function.session-set-save-handler.php
     *
     * @param  $handler
     * @return boolean
     */
    protected function handler($storage = null)
    {
       if ($storage) {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
               return session_set_save_handler($storage, false);
            } else {
                return session_set_save_handler(
                    array($storage, 'open'),
                    array($storage, 'close'),
                    array($storage, 'read'),
                    array($storage, 'write'),
                    array($storage, 'destroy'),
                    array($storage, 'gc')
                );
            }
        }

        return false;
    }
    /**
     * offsetExists
     *
     * @param  string  $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        $this->start();

        return isset($_SESSION[$offset]);
    }

    /**
     * offsetGet
     *
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $this->start();

        return isset($_SESSION[$offset])
            ? $_SESSION[$offset]
            : null;
    }

    /**
     * offsetSet
     *
     * @param  string $offset
     * @param  string $value
     * @return void
     */
    public function offsetSet($offset ,$value)
    {
        $this->start();
        $_SESSION[$offset] = $value;
    }

    /**
     * offsetUnset
     * @param  string  $offset
     * @return boolean
     */
    public function offsetUnset($offset)
    {
        unset($_SESSION[$offset]);
    }
}
