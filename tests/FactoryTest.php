<?php

use MacFJA\InjectionFactory\Factory;

require_once __DIR__.'/TestClasses.php';

class FactoryTest extends PHPUnit_Framework_TestCase {

    public function testNoArgument() {
        /** @var SimpleA $simpleA */
        $simpleA = Factory::create('SimpleA');
        $this->assertEquals('SimpleA:SimpleB-OK', $simpleA.'');

        /** @var SimpleB $simpleB */
        $simpleB = Factory::create('SimpleB');
        $this->assertEquals('SimpleB-OK', $simpleB->exist());

        /** @var SimpleB $simpleB */
        $concreteB = Factory::create('ConcreteB');
        $this->assertEquals('AbstractC-OK:ConcreteB-OK', $concreteB->exist());
    }

    public function testWithArguments() {
        $simpleA = Factory::create('SimpleA', array('b' => new SimpleB()));
        $this->assertEquals('SimpleA:SimpleB-OK', $simpleA.'');

        $complexA = Factory::create('ComplexA', array('boolean' => true, 'string' => 'hello world'));
        $this->assertEquals('ComplexA:SimpleB-OK:string=hello world:boolean=yes:defaultBoolean=no', $complexA.'');

        $complexA = Factory::create('ComplexA', array('boolean' => true, 'string' => 'hello world', 'defaultBoolean' => true));
        $this->assertEquals('ComplexA:SimpleB-OK:string=hello world:boolean=yes:defaultBoolean=yes', $complexA.'');

        $complexA = Factory::create('ComplexA', array(new SimpleB(), true, 'hello world', true));
        $this->assertEquals('ComplexA:SimpleB-OK:string=hello world:boolean=yes:defaultBoolean=yes', $complexA.'');
    }

    public function testInterface() {
        $interfacedA = Factory::create('InterfacedA');
        $this->assertEquals('InterfacedA:InterfacedB-OK', $interfacedA.'');
    }

    public function testAbstract() {
        $concretedA = Factory::create('ConcretedA');
        $this->assertEquals('ConcretedA:AbstractC-OK:ConcreteB-OK', $concretedA.'');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testOneMissingArgument() {
        Factory::create('ComplexA', array('boolean' => true));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMultipleMissingArguments() {
        Factory::create('ComplexA');
    }

    public function testSingleton() {
        $singleton = Factory::create('SingletonA::get');
        $this->assertEquals('SingletonA:SimpleB-OK', $singleton.'');

        $singleton = Factory::create('SingletonA->get');
        $this->assertEquals('SingletonA:SimpleB-OK', $singleton.'');

        $singleton = Factory::create(array('SingletonA', 'get'));
        $this->assertEquals('SingletonA:SimpleB-OK', $singleton.'');
    }

    public function testInitializer() {
        $initA = Factory::create('InitializeA::init');
        $this->assertEquals('InitializeA:SimpleB-OK:string=hello', $initA.'');

        $initA = Factory::create('InitializeA::init', array('string' => 'world'));
        $this->assertEquals('InitializeA:SimpleB-OK:string=world', $initA.'');
    }

    public function testConcreteList() {
        Factory::setConcreteClasses(json_decode(file_get_contents(__DIR__.'/concrete.json'), true));
        Factory::setConcreteClass('InterfaceC', 'InterfacedB');

        $interfacedA = Factory::create('InterfacedA');
        $this->assertEquals('InterfacedA:InterfacedB-OK', $interfacedA.'');

        $concretedA = Factory::create('ConcretedA');
        $this->assertEquals('ConcretedA:AbstractC-OK:ConcreteB-OK', $concretedA.'');

        /** @var ReplacedB $replacedB */
        $replacedB = Factory::create('ReplacedB');
        $this->assertEquals('AbstractC-OK:ConcreteB-OK', $replacedB->exist());

        Factory::setConcreteClasses(array());
    }

    public function testCallbacks() {
        Factory::setCallback(
            'ReplacedB',
            function(Factory $factory, $className, $arguments) {
                return $factory->create('SimpleA');
            }
        );
        $replacedB = Factory::create('ReplacedB');
        $this->assertEquals('SimpleA:SimpleB-OK', $replacedB.'');
        Factory::setCallbacks(array());
    }
}