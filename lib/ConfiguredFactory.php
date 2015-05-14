<?php


namespace MacFJA\InjectionFactory;


/**
 * Class ConfiguredFactory
 *
 * @author  MacFJA
 * @package MacFJA\InjectionFactory
 */
class ConfiguredFactory extends Factory
{
    /** @var array[] */
    protected $configurations = array();

    /**
     * Inject default value (from configuration) in method call
     *
     * {@inheritdoc}
     */
    protected function prepareParameters(\ReflectionMethod $method, $arguments = array())
    {
        if (array_key_exists($method->getDeclaringClass()->getName(), $this->configurations)) {
            $default = $this->configurations[$method->getDeclaringClass()->getName()];
        } else {
            $default = array();
        }
        return parent::prepareParameters($method, $arguments + $default);
    }

    /**
     * Initialize the factory
     *
     * @param array $concreteClasses
     * @param array $callbacks
     * @param array $configurations
     */
    static public function init($concreteClasses = array(), $callbacks = array(), $configurations = array())
    {
        parent::init($concreteClasses, $callbacks);
        self::setConfigurations($configurations);
    }

    /**
     * @param array $configurations
     */
    static public function setConfigurations($configurations)
    {
        self::instance()->configurations = $configurations;
    }

    // @codeCoverageIgnoreStart
    /**
     * @return array
     */
    static public function getConfigurations()
    {
        return self::instance()->configurations;
    }
    // @codeCoverageIgnoreEnd

    /**
     * @param string $class
     * @param array  $configuration
     */
    static public function setConfiguration($class, $configuration)
    {
        self::instance()->configurations[$class] = $configuration;
    }
}