<?php

namespace Knp\FriendlyContexts\Reflection;

class ObjectReflector
{

    public function getReflectionClass($object)
    {
        return new \ReflectionClass($object);
    }

    public function getClassName($object)
    {
        return $this->getReflectionClass($object)->getShortName();
    }

    public function getClassNamespace($object)
    {
        return $this->getReflectionClass($object)->getNamespaceName();
    }

    public function getClassLongName($object)
    {
        return sprintf(
            "%s\\%s",
            $this->getClassNamespace($object),
            $this->getClassName($object)
        );
    }

    public function isInstanceOf($object, $class)
    {
        return $object instanceof $class;
    }
}