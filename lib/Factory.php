<?php


namespace MacFJA\InjectionFactory;

/**
 * Class Factory
 *
 * @author  MacFJA
 * @package MacFJA\InjectionFactory
 */
class Factory
{
    /** @var null|static Singleton of the Factory */
    static protected $instance = null;
    /** @var array List of all callbacks (key = class name, value = callback) */
    protected $callbacks = array();
    /** @var array List of conversion for non instantiable interface/class (key = non-instantiable, value = instantiable */
    protected $concreteClasses = array();
    /** @var array List of already instantiate class (singleton) (key = class name, value = singleton) */
    protected $objectInstances = array();

    /**
     * Protected constructor for Singleton use.
     *
     * @see MacFJA\InjectionFactory\Factory::init()
     */
    protected function __construct()
    {
    }

    /**
     * @param string $className
     * @param string $callback
     *
     * @internal param array $callbacks
     */
    static public function setCallback($className, $callback)
    {
        self::instance()->callbacks[$className] = $callback;
    }

    /**
     * Return the Factory singleton
     *
     * @return static
     */
    static protected function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    // @codeCoverageIgnoreStart
    /**
     * @return array
     */
    static public function getCallbacks()
    {
        return self::instance()->callbacks;
    }
    // @codeCoverageIgnoreEnd

    /**
     * @param array $callbacks
     */
    static public function setCallbacks($callbacks)
    {
        self::instance()->callbacks = $callbacks;
    }

    /**
     * Initialize the factory
     *
     * @param array $concreteClasses
     * @param array $callbacks
     */
    static public function init($concreteClasses = array(), $callbacks = array())
    {
        self::setConcreteClasses($concreteClasses);
        self::setCallbacks($callbacks);
    }

    /**
     * @param string $nonInstantiableName
     * @param string $implementationName
     *
     * @internal param array $implementationsNames
     */
    static public function setConcreteClass($nonInstantiableName, $implementationName)
    {
        self::instance()->concreteClasses[$nonInstantiableName] = $implementationName;
    }

    // @codeCoverageIgnoreStart
    /**
     * @return array
     */
    static public function getConcreteClasses()
    {
        return self::instance()->concreteClasses;
    }
    // @codeCoverageIgnoreEnd

    /**
     * @param array $implementationsNames
     */
    static public function setConcreteClasses($implementationsNames)
    {
        self::instance()->concreteClasses = $implementationsNames;
    }

    /**
     * Get a singleton of a class
     *
     * @param string|array $class
     * @param array        $arguments
     *
     * @return mixed
     */
    static public function get($class, $arguments = array())
    {
        $self = self::instance();

        list($className,) = $self->checkInterfaceAndAbstract($class);

        if (!array_key_exists($className, $self->objectInstances)) {
            $object = self::create($class, $arguments);
            self::setSingleton($className, $object);
        }
        return $self->objectInstances[$className];
    }

    /**
     * Set a singleton instance for `$class` (it's this instance that will be used in MacFJA\InjectionFactory\Factory::get)
     *
     * @param string $class
     * @param mixed $object
     */
    static public function setSingleton($class, $object) {
        self::instance()->objectInstances[$class] = $object;
    }

    /**
     * Check if the class is instantiable, if not try to find a concrete (instantiable) class
     *
     * @param string $class
     *
     * @return mixed
     */
    protected function checkInterfaceAndAbstract($class)
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            // Check if the interface/abstract class exist in the default implementation array
            if (array_key_exists($class, $this->concreteClasses)) {
                return $this->checkInterfaceAndAbstract($this->concreteClasses[$class]);
            }

            // search for concrete class
            $interface = $reflection->isInterface();
            $implementedIn = array_filter(
                get_declared_classes(),
                function ($item) use ($class, $interface) {
                    if ($interface) {
                        return in_array($class, class_implements($item));
                    } else {
                        return in_array($class, class_parents($item));
                    }
                }
            );
            // Return the first item
            return $this->checkInterfaceAndAbstract(reset($implementedIn));

        }

        return $class;
    }

    /**
     * Create a new instance a class
     *
     * @param string|array $class
     * @param array        $arguments
     *
     * @return mixed
     */
    static public function create($class, $arguments = array())
    {
        list($class, $method) = self::instance()->getClassAndMethod($class);

        if (!empty($method)) {
            return self::instance()->createFromMethod($class, $method, $arguments);
        } else {
            return self::instance()->createInstance($class, $arguments);
        }
    }

    /**
     * Extract the class name and the method name
     *
     * @param string|array $class
     *
     * @return array
     */
    protected function getClassAndMethod($class)
    {
        $method = '';

        // Check $class argument
        if (is_array($class)) {
            list($class, $method) = $class;
        } elseif (strpos($class, '::') != 0) {
            list($class, $method) = explode('::', $class);
        } elseif (strpos($class, '->') != 0) {
            list($class, $method) = explode('->', $class);
        }

        return array(
            // Numeric index for "list" function
            0 => $class,
            1 => $method,
            // Associative index for easy manipulation
            'class' => $class,
            'method' => $method
        );
    }

    /**
     * Create (and configure) the class through a class method
     *
     * @param string $class
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function createFromMethod($class, $method, $arguments = array())
    {
        $reflection = new \ReflectionClass($class);

        $reflectionMethod = $reflection->getMethod($method);
        if (($reflectionMethod->getModifiers() & \ReflectionMethod::IS_STATIC) != 0) {
            return $this->buildFromStaticMethod($class, $method, $arguments);
        } else {
            return $this->buildFromInstanceMethod($class, $method, $arguments);
        }
    }

    /**
     * Create a new instance by calling a static method of a class
     *
     * @param string $class
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function buildFromStaticMethod($class, $method, $arguments = array())
    {
        $reflection = new \ReflectionMethod($class, $method);

        $args = $this->prepareParameters($reflection, $arguments);

        return call_user_func_array(array($class, $method), $args);
    }

    /**
     * Prepare parameters that will by passe to a method
     *
     * @param \ReflectionMethod $method
     * @param array             $arguments
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    protected function prepareParameters(\ReflectionMethod $method, $arguments = array())
    {
        $assoc = (array_keys($arguments) !== range(0, count($arguments) - 1));

        $params = $method->getParameters();

        $injected = array();

        foreach ($params as $param) {
            if ($assoc && array_key_exists($param->getName(), $arguments)) {
                $injected[] = $arguments[$param->getName()];
            } elseif (!$assoc && $param->getPosition() < count($arguments)) {
                $injected[] = $arguments[$param->getPosition()];
            } elseif ($param->getClass() !== null) {
                $injected[] = $this->get($param->getClass()->getName());
            } elseif ($param->isOptional()) {
                $injected[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException('At least one required parameter is missing [' . $method->getDeclaringClass()->getName() . '::' . $method->getName(
                ) . ':' . $param->getName() . ']');
            }
        }

        return $injected;
    }

    /**
     * Create a new instance of a class and call a method
     *
     * @param string $class
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function buildFromInstanceMethod($class, $method, $arguments = array())
    {
        $reflection = new \ReflectionMethod($class, $method);

        $args = $this->prepareParameters($reflection, $arguments);

        $object = $this->createInstance($class, $arguments);

        $reflection->invokeArgs($object, $args);

        return $object;
    }

    /**
     * Create a class through constructor or callback
     *
     * @param string $class
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function createInstance($class, $arguments = array())
    {
        if (array_key_exists($class, $this->callbacks)) {
            return $this->buildFromCallback($class, $arguments);
        }
        return $this->buildFromConstructor($class, $arguments);
    }

    /**
     * Create a new instance from a callback
     *
     * @param string $class
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function buildFromCallback($class, $arguments = array())
    {
        $callback = $this->callbacks[$class];
        return call_user_func_array($callback, array($this, $class, $arguments));
    }

    /**
     * Create a new instance with the class constructor
     *
     * @param string $class
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function buildFromConstructor($class, $arguments = array())
    {
        $class = $this->checkInterfaceAndAbstract($class);
        $reflection = new \ReflectionClass($class);

        $constructor = $reflection->getConstructor();

        // No constructor defined
        if (is_null($constructor)) {
            return $reflection->newInstance();
        }

        if ($constructor->getNumberOfParameters() == 0) {
            return $reflection->newInstanceArgs();
        }

        return $reflection->newInstanceArgs($this->prepareParameters($constructor, $arguments));
    }
}