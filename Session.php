<?php

/**
 * Session handler.
 */
class Session
{
    /**
     * segment for sessions.
     *
     * @var string
     */
    protected  $flash_segment = 'flash_session_segment';
    protected  $csrf_segment = 'csrf_session_segment';

    protected $segment_separator = '/';

    private static $session = null;


    protected function __construct()
    {
        $this->startSession();
        $this->initDefaultSegments();
    }

    protected function startSession()
    {
        if (!$this->isStarted()) {
            session_start();
        }
    }

    protected function isValidSegment($segment)
    {
        return is_string($segment) && strlen($segment) > 0;
    }


    protected function initDefaultSegments()
    {
        if ($this->isStarted()) {

            if (!isset($_SESSION[$this->flash_segment])) {
                $_SESSION[$this->flash_segment] = array();
            }
            if (!isset($_SESSION[$this->csrf_segment])) {
                $_SESSION[$this->csrf_segment] = array();
            }
        }
    }

    protected function isStartedMethod()
    {

        if (php_sapi_name() !== 'cli') {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
            } else {
                return session_id() === '' ? FALSE : TRUE;
            }
        }
        return FALSE;
    }


    protected  function idMethod()
    {
        return session_id();
    }

    protected function destroyMethod()
    {
        session_destroy();
        return $this;
    }

    protected function regenerateMethod()
    {
        session_regenerate_id(true);

        return $this->id();
    }





    protected function setMethod($key, $value, $segment = null)
    {
        $current = &$_SESSION;

        if ($this->isValidSegment($segment)) {

            $keyPath = explode($this->segment_separator, rtrim($segment, $this->segment_separator));
            foreach ($keyPath as $currentKey) {
                if (!isset($current[$currentKey])) {
                    $current[$currentKey] = array();
                }
                $current = &$current[$currentKey];
            }
        }
        $current[$key] = $value;
        return $this;
    }

    protected function hasMethod($key, $segment = null)
    {


        $current = &$_SESSION;

        if ($this->isValidSegment($segment)) {

            $keyPath = explode($this->segment_separator, rtrim($segment, $this->segment_separator));
            foreach ($keyPath as $currentKey) {
                if (isset($current[$currentKey]) && is_array($current[$currentKey])) {
                    $current = &$current[$currentKey];
                } else {

                    $current = null;
                    break;
                }
            }
        }


        return !is_null($current) && isset($current[$key]);
    }

    protected function getMethod($key, $default = null, $segment = null)
    {
        $current = &$_SESSION;




        if ($this->isValidSegment($segment)) {

            $keyPath = explode($this->segment_separator, rtrim($segment, $this->segment_separator));
            foreach ($keyPath as $currentKey) {
                if (isset($current[$currentKey]) && is_array($current[$currentKey])) {
                    $current = &$current[$currentKey];
                } else {

                    $current = null;
                    break;
                }
            }
        }

        if (is_null($key)) {

            return isset($current) ? $current : $default;
        }

        return  isset($current[$key]) ? $current[$key] : $default;
    }

    protected function deleteMethod($key, $segment = null)
    {


        $current = &$_SESSION;

        if ($this->isValidSegment($segment)) {

            $keyPath = explode($this->segment_separator, rtrim($segment, $this->segment_separator));
            foreach ($keyPath as $currentKey) {
                if (isset($current[$currentKey]) && is_array($current[$currentKey])) {
                    $current = &$current[$currentKey];
                } else {

                    $current = null;
                    break;
                }
            }
        }
        if (!is_null($current) && isset($current[$key])) {

            unset($current[$key]);
        }

        return $this;
    }

    protected function pullMethod($key, $default = null, $segment = null)
    {

        $value = $this->getMethod($key, $default, $segment);
        $this->deleteMethod($key, $segment);
        return $value;
    }


    protected function setFlashMethod($key, $value)
    {

        $this->set($key, $value, $this->flash_segment);
    }

    protected function getFlashMethod($key, $keep = false)
    {

        if ($keep) {

            return $this->get($key, null, $this->flash_segment);
        }

        return $this->pull($key, null, $this->flash_segment);
    }

    protected function getTokenMethod()
    {
        $token = $this->generateToken();
        $this->set($token, time(), $this->csrf_segment);
        return $token;
    }

    protected function generateToken()
    {
        return sha1(uniqid(sha1(uniqid()), true));
    }

    protected function validateTokenMethod($token, $keep = false)
    {
        $value = ($keep) ? $this->get($token, null, $this->csrf_segment) : $this->pull($token, null, $this->csrf_segment);

        return !empty($value);
    }


    public static function init()
    {
        if (is_null(self::$session)) {

            self::$session = new self();
        }

        return self::$session;
    }

    public static function __callStatic($name, $arguments)
    {
        self::init();
        $name = $name . 'Method';
        if (method_exists(self::$session, $name)) {
            return call_user_func_array(array(self::$session, $name), $arguments);
        }
    }
    public function __call($name, $arguments)
    {

        $name = $name . 'Method';
        if (method_exists($this, $name)) {
            return call_user_func_array(array($this, $name), $arguments);
        }
    }
}
