<?php

namespace ArtisanSDK\Server\Tests;

use ArtisanSDK\Server\Traits\FluentProperties;

class FluentPropertiesTest extends TestCase
{
    /**
     * Test that a fluent property can be set.
     */
    public function testSetFluentProperty()
    {
        $stub = new FluentPropertiesStub();
        $stub->fooString('bar');
        $stub->fooArray(['bar']);

        $this->assertSame('bar', $stub->fooString, 'Calling property() with a string as the second argument should set the string value as a named property based on the first argument.');
        $this->assertSame(['bar'], $stub->fooArray, 'Calling property() with an array as the second argument should set the array value as a named property based on the first argument.');
    }

    /**
     * Test that a fluent property can be gotten.
     */
    public function testGetFluentProperty()
    {
        $stub = new FluentPropertiesStub();

        $this->assertSame('foo', $stub->barString(), 'Calling property() without a second argument should return the string value under the named property based on the first argument.');
        $this->assertSame(['foo'], $stub->barArray(), 'Calling property() without a second argument should return the array value under the named property based on the first argument.');
    }

    /**
     * Test that a fluent property call can be chained.
     */
    public function testFluentPropertyChaining()
    {
        $stub = new FluentPropertiesStub();
        $this->assertSame($stub, $stub->fooString('foo'), 'Calling property() with multiple arguments should be fluent, allowing for chaining: return the class itself after setting a value.');
        $this->assertSame($stub, $stub->fooString('foo')->barString('bar'), 'Calling property() with multiple arguments should be fluent, allowing for chaining: return the class itself after setting a value.');
        $this->assertSame('bar', $stub->fooString('foo')->barString(), 'Calling property() without multiple arguments should return the value: do not chain after returning a value.');
        $this->assertSame('foo', $stub->fooString(), 'Calling property() repeatedly in a chain should allow for multiple values to be set.');
        $this->assertSame('bar', $stub->barString(), 'Calling property() repeatedly in a chain should allow for multiple values to be set.');
    }
}

class FluentPropertiesStub
{
    use FluentProperties;

    protected $barString = 'foo';
    protected $barArray = ['foo'];

    public function __call($method, $arguments = [])
    {
        return call_user_func_array([$this, 'property'],
            array_merge([$method], $arguments)
        );
    }
}
