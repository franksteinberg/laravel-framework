<?php

namespace Illuminate\Tests\View\Blade;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Compilers\ComponentTagCompiler;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;
use Mockery as m;

class BladeComponentTagCompilerTest extends AbstractBladeTestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSlotsCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot name="foo">
</x-slot>');

        $this->assertSame("@slot('foo', null, []) \n".' @endslot', trim($result));
    }

    public function testInlineSlotsCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot:foo>
</x-slot>');

        $this->assertSame("@slot('foo', null, []) \n".' @endslot', trim($result));
    }

    public function testDynamicSlotsCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot :name="$foo">
</x-slot>');

        $this->assertSame("@slot(\$foo, null, []) \n".' @endslot', trim($result));
    }

    public function testDynamicSlotsCanBeCompiledWithKeyOfObjects()
    {
        $result = $this->compiler()->compileSlots('<x-slot :name="$foo->name">
</x-slot>');

        $this->assertSame("@slot(\$foo->name, null, []) \n".' @endslot', trim($result));
    }

    public function testSlotsWithAttributesCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot name="foo" class="font-bold">
</x-slot>');

        $this->assertSame("@slot('foo', null, ['class' => 'font-bold']) \n".' @endslot', trim($result));
    }

    public function testInlineSlotsWithAttributesCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot:foo class="font-bold">
</x-slot>');

        $this->assertSame("@slot('foo', null, ['class' => 'font-bold']) \n".' @endslot', trim($result));
    }

    public function testSlotsWithDynamicAttributesCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot name="foo" :class="$classes">
</x-slot>');

        $this->assertSame("@slot('foo', null, ['class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\$classes)]) \n".' @endslot', trim($result));
    }

    public function testSlotsWithClassDirectiveCanBeCompiled()
    {
        $result = $this->compiler()->compileSlots('<x-slot name="foo" @class($classes)>
</x-slot>');

        $this->assertSame("@slot('foo', null, ['class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssClasses(\$classes))]) \n".' @endslot', trim($result));
    }

    public function testBasicComponentParsing()
    {
        $this->mockViewFactory();

        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert type="foo" limit="5" @click="foo" wire:click="changePlan(\'{{ $plan }}\')" required /><x-alert /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['type' => 'foo','limit' => '5','@click' => 'foo','wire:click' => 'changePlan(\''.e(\$plan).'\')','required' => true]); ?>\n".
"@endComponentClass##END-COMPONENT-CLASS####BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testBasicComponentWithEmptyAttributesParsing()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert type="" limit=\'\' @click="" required /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['type' => '','limit' => '','@click' => '','required' => true]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testDataCamelCasing()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile user-id="1"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => '1'])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonData()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :user-id="1"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => 1])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonDataShortSyntax()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :$userId></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => \$userId])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonDataWithStaticClassProperty()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :userId="User::$id"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => User::\$id])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonDataWithStaticClassPropertyAndMultipleAttributes()
    {
        $result = $this->compiler(['input' => TestInputComponent::class])->compileTags('<x-input :label="Input::$label" :$name value="Joe"></x-input>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestInputComponent', 'input', ['label' => Input::\$label,'name' => \$name,'value' => 'Joe'])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestInputComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));

        $result = $this->compiler(['input' => TestInputComponent::class])->compileTags('<x-input value="Joe" :$name :label="Input::$label"></x-input>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestInputComponent', 'input', ['value' => 'Joe','name' => \$name,'label' => Input::\$label])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestInputComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testSelfClosingComponentWithColonDataShortSyntax()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :$userId/>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => \$userId])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testSelfClosingComponentWithColonDataAndStaticClassPropertyShortSyntax()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :userId="User::$id"/>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => User::\$id])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testSelfClosingComponentWithColonDataMultipleAttributesAndStaticClassPropertyShortSyntax()
    {
        $result = $this->compiler(['input' => TestInputComponent::class])->compileTags('<x-input :label="Input::$label" value="Joe" :$name />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestInputComponent', 'input', ['label' => Input::\$label,'value' => 'Joe','name' => \$name])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestInputComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));

        $result = $this->compiler(['input' => TestInputComponent::class])->compileTags('<x-input :$name :label="Input::$label" value="Joe" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestInputComponent', 'input', ['name' => \$name,'label' => Input::\$label,'value' => 'Joe'])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestInputComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testEscapedColonAttribute()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :user-id="1" ::title="user.name"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', ['userId' => 1])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([':title' => 'user.name']); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonAttributesIsEscapedIfStrings()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile :src="\'foo\'"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('foo')]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testClassDirective()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile @class(["bar"=>true])></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\Illuminate\Support\Arr::toCssClasses(['bar'=>true]))]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonNestedComponentParsing()
    {
        $result = $this->compiler(['foo:alert' => TestAlertComponent::class])->compileTags('<x-foo:alert></x-foo:alert>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'foo:alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testColonStartingNestedComponentParsing()
    {
        $result = $this->compiler(['foo:alert' => TestAlertComponent::class])->compileTags('<x:foo:alert></x-foo:alert>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'foo:alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiled()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert/></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testClassNamesCanBeGuessed()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        Container::setInstance($container);

        $result = $this->compiler()->guessClassName('alert');

        $this->assertSame("App\View\Components\Alert", trim($result));

        Container::setInstance(null);
    }

    public function testClassNamesCanBeGuessedWithNamespaces()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        Container::setInstance($container);

        $result = $this->compiler()->guessClassName('base.alert');

        $this->assertSame("App\View\Components\Base\Alert", trim($result));

        Container::setInstance(null);
    }

    public function testComponentsCanBeCompiledWithHyphenAttributes()
    {
        $this->mockViewFactory();

        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert class="bar" wire:model="foo" x-on:click="bar" @click="baz" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','wire:model' => 'foo','x-on:click' => 'bar','@click' => 'baz']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithDataAndAttributes()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert title="foo" class="bar" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', ['title' => 'foo'])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','wire:model' => 'foo']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testComponentCanReceiveAttributeBag()
    {
        $this->mockViewFactory();
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile class="bar" {{ $attributes }} wire:model="foo"></x-profile>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\$attributes),'wire:model' => 'foo']); ?> @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testSelfClosingComponentCanReceiveAttributeBag()
    {
        $this->mockViewFactory();

        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<div><x-alert title="foo" class="bar" {{ $attributes->merge([\'class\' => \'test\']) }} wire:model="foo" /></div>');

        $this->assertSame("<div>##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', ['title' => 'foo'])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar','attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\$attributes->merge(['class' => 'test'])),'wire:model' => 'foo']); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##</div>', trim($result));
    }

    public function testComponentsCanHaveAttachedWord()
    {
        $result = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile></x-profile>Words');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestProfileComponent', 'profile', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestProfileComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?> @endComponentClass##END-COMPONENT-CLASS##Words", trim($result));
    }

    public function testSelfClosingComponentsCanHaveAttachedWord()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert/>Words');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##Words', trim($result));
    }

    public function testSelfClosingComponentsCanBeCompiledWithBoundData()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert :title="$title" class="bar" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', ['title' => \$title])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['class' => 'bar']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testPairedComponentTags()
    {
        $result = $this->compiler(['alert' => TestAlertComponent::class])->compileTags('<x-alert>
</x-alert>');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\Tests\View\Blade\TestAlertComponent', 'alert', [])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\Tests\View\Blade\TestAlertComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes([]); ?>
 @endComponentClass##END-COMPONENT-CLASS##", trim($result));
    }

    public function testClasslessComponents()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(true);
        Container::setInstance($container);

        $result = $this->compiler()->compileTags('<x-anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'anonymous-component', ['view' => 'components.anonymous-component','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testClasslessComponentsWithIndexView()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(false, true);
        Container::setInstance($container);

        $result = $this->compiler()->compileTags('<x-anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'anonymous-component', ['view' => 'components.anonymous-component.index','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testPackagesClasslessComponents()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(true);
        Container::setInstance($container);

        $result = $this->compiler()->compileTags('<x-package::anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'package::anonymous-component', ['view' => 'package::components.anonymous-component','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
'@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testClasslessComponentsWithAnonymousComponentNamespace()
    {
        $container = new Container;

        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));

        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturnUsing(function ($arg) {
            // In our test, we'll do as if the 'public.frontend.anonymous-component'
            // view exists and not the others.
            return $arg === 'public.frontend.anonymous-component';
        });

        Container::setInstance($container);

        $blade = m::mock(BladeCompiler::class)->makePartial();

        $blade->shouldReceive('getAnonymousComponentNamespaces')->andReturn([
            'frontend' => 'public.frontend',
        ]);

        $compiler = $this->compiler([], [], $blade);

        $result = $compiler->compileTags('<x-frontend::anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'frontend::anonymous-component', ['view' => 'public.frontend.anonymous-component','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testClasslessComponentsWithAnonymousComponentNamespaceWithIndexView()
    {
        $container = new Container;

        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));

        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturnUsing(function (string $viewNameBeingCheckedForExistence) {
            // In our test, we'll do as if the 'public.frontend.anonymous-component'
            // view exists and not the others.
            return $viewNameBeingCheckedForExistence === 'admin.auth.components.anonymous-component.index';
        });

        Container::setInstance($container);

        $blade = m::mock(BladeCompiler::class)->makePartial();

        $blade->shouldReceive('getAnonymousComponentNamespaces')->andReturn([
            'admin.auth' => 'admin.auth.components',
        ]);

        $compiler = $this->compiler([], [], $blade);

        $result = $compiler->compileTags('<x-admin.auth::anonymous-component :name="\'Taylor\'" :age="31" wire:model="foo" />');

        $this->assertSame("##BEGIN-COMPONENT-CLASS##@component('Illuminate\View\AnonymousComponent', 'admin.auth::anonymous-component', ['view' => 'admin.auth.components.anonymous-component.index','data' => ['name' => 'Taylor','age' => 31,'wire:model' => 'foo']])
<?php if (isset(\$attributes) && \$constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php \$attributes = \$attributes->except(collect(\$constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php \$component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Taylor'),'age' => 31,'wire:model' => 'foo']); ?>\n".
            '@endComponentClass##END-COMPONENT-CLASS##', trim($result));
    }

    public function testAttributeSanitization()
    {
        $class = new class
        {
            public function __toString()
            {
                return '<hi>';
            }
        };

        $model = new class extends Model
        {
        };

        $this->assertEquals(e('<hi>'), BladeCompiler::sanitizeComponentAttribute('<hi>'));
        $this->assertEquals(e('1'), BladeCompiler::sanitizeComponentAttribute('1'));
        $this->assertEquals(1, BladeCompiler::sanitizeComponentAttribute(1));
        $this->assertEquals(e('<hi>'), BladeCompiler::sanitizeComponentAttribute($class));
        $this->assertSame($model, BladeCompiler::sanitizeComponentAttribute($model));
    }

    public function testItThrowsAnExceptionForNonExistingAliases()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(false);
        Container::setInstance($container);

        $this->expectException(InvalidArgumentException::class);

        $this->compiler(['alert' => 'foo.bar'])->compileTags('<x-alert />');
    }

    public function testItThrowsAnExceptionForNonExistingClass()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(false);
        Container::setInstance($container);

        $this->expectException(InvalidArgumentException::class);

        $this->compiler()->compileTags('<x-alert />');
    }

    public function testAttributesTreatedAsPropsAreRemovedFromFinalAttributes()
    {
        $container = new Container;
        $container->instance(Application::class, $app = m::mock(Application::class));
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');
        $factory->shouldReceive('exists')->andReturn(false);
        Container::setInstance($container);

        $attributes = new ComponentAttributeBag(['userId' => 'bar', 'other' => 'ok']);

        $component = m::mock(Component::class);
        $component->shouldReceive('withName', 'test');
        $component->shouldReceive('shouldRender')->andReturn(true);
        $component->shouldReceive('resolveView')->andReturn('');
        $component->shouldReceive('data')->andReturn([]);
        $component->shouldReceive('withAttributes');

        $__env = m::mock(\Illuminate\View\Factory::class);
        $__env->shouldReceive('getContainer->make')->with(TestProfileComponent::class, ['userId' => 'bar', 'other' => 'ok'])->andReturn($component);
        $__env->shouldReceive('startComponent');
        $__env->shouldReceive('renderComponent');

        $template = $this->compiler(['profile' => TestProfileComponent::class])->compileTags('<x-profile {{ $attributes }} />');
        $template = $this->compiler->compileString($template);

        ob_start();
        eval(" ?> $template <?php ");
        ob_get_clean();

        $this->assertNull($attributes->get('userId'));
        $this->assertSame($attributes->get('other'), 'ok');
    }

    protected function mockViewFactory($existsSucceeds = true)
    {
        $container = new Container;
        $container->instance(Factory::class, $factory = m::mock(Factory::class));
        $factory->shouldReceive('exists')->andReturn($existsSucceeds);
        Container::setInstance($container);
    }

    protected function compiler(array $aliases = [], array $namespaces = [], ?BladeCompiler $blade = null)
    {
        return new ComponentTagCompiler(
            $aliases, $namespaces, $blade
        );
    }
}

class TestAlertComponent extends Component
{
    public $title;

    public function __construct($title = 'foo', $userId = 1)
    {
        $this->title = $title;
    }

    public function render()
    {
        return 'alert';
    }
}

class TestProfileComponent extends Component
{
    public $userId;

    public function __construct($userId = 'foo')
    {
        $this->userId = $userId;
    }

    public function render()
    {
        return 'profile';
    }
}

class TestInputComponent extends Component
{
    public $userId;

    public function __construct($name, $label, $value)
    {
        $this->name = $name;
        $this->label = $label;
        $this->value = $value;
    }

    public function render()
    {
        return 'input';
    }
}
