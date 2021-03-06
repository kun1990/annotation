<?php
/**
 * Created by PhpStorm.
 * User: janhuang
 * Date: 16/4/26
 * Time: 上午11:36
 * Github: https://www.github.com/janhuang
 * Coding: https://www.coding.net/janhuang
 * SegmentFault: http://segmentfault.com/u/janhuang
 * Blog: http://segmentfault.com/blog/janhuang
 * Gmail: bboyjanhuang@gmail.com
 * WebSite: http://www.janhuang.me
 */

namespace FastD\Annotation;

use ReflectionClass;
use ReflectionMethod;
use Iterator;
use Countable;

/**
 * Class Annotation
 *
 * @package FastD\Annotation
 */
class Annotation implements Iterator, Countable
{
    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @var array
     */
    protected $with = [];

    /**
     * @var Annotator[]
     */
    protected $annotators = [];

    /**
     * @var string
     */
    protected $filter;

    /**
     * Annotation constructor.
     *
     * @param $class
     * @param string $filter
     */
    public function __construct($class, $filter = null)
    {
        $this->filter = $filter;

        $this->annotators = $this->reflection($class);
    }

    /**
     * @param $class
     * @return AnnotatorMethod[]
     */
    protected function reflection($class)
    {
        $annotators = [];

        if (!($class instanceof ReflectionClass)) {
            $class = new ReflectionClass($class);
        }

        $parents = $this->recursiveReflectionParent($class);

        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (false !== $method->getDeclaringClass() && $method->getDeclaringClass()->getName() == $class->getName()) {
                if (null !== $this->filter && false === strpos($method->getName(), $this->filter)) {
                    continue;
                }
                $annotator = new AnnotatorMethod($method);
                $annotator = $this->merge($annotator, $parents);
                $annotators[$annotator->getName()] = $annotator;
            }
        }

        $this->resetWith();

        return $annotators;
    }

    /**
     * Recursive reflection.
     *
     * @param ReflectionClass $reflectionClass
     * @return array
     */
    protected function recursiveReflectionParent(ReflectionClass $reflectionClass)
    {
        array_push($this->with, new AnnotatorClass($reflectionClass));

        if (false !== $reflectionClass->getParentClass()) {
            $this->recursiveReflectionParent($reflectionClass->getParentClass());
        }

        return $this->with;
    }

    /**
     * @param AnnotatorMethod $annotatorMethod
     * @param AnnotatorClass[] $parents
     * @return AnnotatorMethod
     */
    protected function merge(AnnotatorMethod $annotatorMethod, array $parents = [])
    {
        $parameters = $annotatorMethod->getParameters();

        foreach ($parents as $parent) {
            if ($parent->isEmpty()) {
                continue;
            }
            $params = $parent->getParameters();
            foreach ($params as $key => $value) {
                if (isset($parameters[$key])) {
                    foreach ($value as $name => $item) {
                        if (isset($parameters[$key][$name])) {
                            if (is_string($item)) {
                                $parameters[$key][$name] = $item . $parameters[$key][$name];
                            } else if (is_array($item)) {
                                $parameters[$key][$name] = array_unique(array_merge($item, $$parameters[$key][$name]));
                            }
                        } else {
                            $parameters[$key][$name] = $item;
                        }
                    }
                } else {
                    $parameters[$key] = $value;
                }
            }
        }

        $annotatorMethod->setParameters($parameters);

        return $annotatorMethod;
    }

    /**
     * @return void
     */
    public function resetWith()
    {
        $this->with = [];
    }

    /**
     * @return AnnotatorMethod[]
     */
    public function getAnnotators()
    {
        return $this->annotators;
    }

    /**
     * @param $name
     * @return Annotator|AnnotatorMethod|null
     */
    public function getAnnotator($name)
    {
        return isset($this->annotators[$name]) ? $this->annotators[$name] : null;
    }

    /**
     * Return the current element
     *
     * @link  http://php.net/manual/en/iterator.current.php
     * @return Annotator Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this->annotators[$this->key()];
    }

    /**
     * Move forward to next element
     *
     * @link  http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        next($this->annotators);
    }

    /**
     * Return the key of the current element
     *
     * @link  http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return key($this->annotators);
    }

    /**
     * Checks if current position is valid
     *
     * @link  http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     *        Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return isset($this->annotators[$this->key()]);
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link  http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        reset($this->annotators);
    }

    /**
     * Count elements of an object
     *
     * @link  http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     *        </p>
     *        <p>
     *        The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->annotators);
    }
}