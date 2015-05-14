<?php

use MacFJA\InjectionFactory\ConfiguredFactory;
use MacFJA\InjectionFactory\Factory;

require_once __DIR__.'/TestClasses.php';

class ConfiguredFactoryTest extends PHPUnit_Framework_TestCase {
    public function testConfiguration() {
        $concrete = json_decode(file_get_contents(__DIR__.'/concrete.json'), true);
        $config = json_decode(file_get_contents(__DIR__.'/config.json'), true);

        ConfiguredFactory::init($concrete, array(), $config);

        $a = ConfiguredFactory::create('ComplexA');
        $this->assertEquals('ComplexA:SimpleB-OK:string=hello world:boolean=yes:defaultBoolean=yes', $a.'');

        $a = ConfiguredFactory::create('ComplexA', array('string' => 'hello'));
        $this->assertEquals('ComplexA:SimpleB-OK:string=hello:boolean=yes:defaultBoolean=yes', $a.'');

        ConfiguredFactory::setConfiguration('ComplexA', array('string' => 'world', 'boolean' => false));
        $a = ConfiguredFactory::create('ComplexA');
        $this->assertEquals('ComplexA:SimpleB-OK:string=world:boolean=no:defaultBoolean=no', $a.'');

        ConfiguredFactory::init();
    }
}