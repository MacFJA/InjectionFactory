<?php

class SimpleA {
    /** @var SimpleB */
    protected $b;

    function __construct(SimpleB $b)
    {
        $this->b = $b;
    }

    function __toString()
    {
        return 'SimpleA:'.$this->b->exist();
    }


}

class SimpleB {
    function exist() {
        return 'SimpleB-OK';
    }
}

class ComplexA {
    protected $b;
    protected $boolean;
    protected $string;
    protected $defaultBoolean;

    function __construct(SimpleB $b, $boolean, $string, $defaultBoolean=false)
    {
        $this->b = $b;
        $this->boolean = $boolean;
        $this->defaultBoolean = $defaultBoolean;
        $this->string = $string;
    }


    function __toString()
    {
        return 'ComplexA:'.
        $this->b->exist().
        ':string='.$this->string.
        ':boolean='.($this->boolean?'yes':'no').
        ':defaultBoolean='.($this->defaultBoolean?'yes':'no');
    }
}

interface InterfaceC {
    function exist();
}

class InterfacedB implements InterfaceC {
    function exist() {
        return 'InterfacedB-OK';
    }
}

class InterfacedA {
    /** @var InterfaceC */
    protected $c;

    function __construct(InterfaceC $c)
    {
        $this->c = $c;
    }

    function __toString()
    {
        return 'InterfacedA:'.$this->c->exist();
    }
}

abstract class AbstractC {
    function exist() {
        return 'AbstractC-OK';
    }
}

class ConcreteB extends AbstractC {
    function __construct() {

    }

    function exist() {
        return parent::exist().':ConcreteB-OK';
    }
}

abstract class ReplacedB extends SimpleB {
    function exist()
    {
        return 'ReplacedB-OK';
    }

}

class ConcretedA {
    /** @var InterfaceC */
    protected $c;

    function __construct(AbstractC $c)
    {
        $this->c = $c;
    }

    function __toString()
    {
        return 'ConcretedA:'.$this->c->exist();
    }
}

class SingletonA {
    /** @var null|static  */
    static protected $instance = null;
    /** @var SimpleB */
    protected $b;

    static public function get(SimpleB $b) {
        if(self::$instance == null) {
            self::$instance = new static($b);
        }
        return self::$instance;
    }

    protected function __construct(SimpleB $b) {
        $this->b = $b;
    }

    function __toString()
    {
        return 'SingletonA:'.$this->b->exist();
    }
}

class InitializeA {
    /** @var SimpleB */
    protected $b;
    protected $string;

    public function init(SimpleB $b, $string = 'hello')
    {
        $this->b = $b;
        $this->string = $string;
    }

    function __toString()
    {
        return 'InitializeA:'.
        $this->b->exist().
        ':string='.$this->string;
    }
}