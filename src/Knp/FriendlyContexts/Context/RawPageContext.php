<?php

namespace Knp\FriendlyContexts\Context;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Behat\Mink\Element\DocumentElement;
use Knp\FriendlyContexts\Page\Page;

class RawPageContext extends RawMinkContext
{
    private $pages = [];

    public function visitPage(Page $page, $arguments = null)
    {
        list($parameters, $entities) = $this->extractTable($arguments);

        $path = $this->locatePath($this->resolvePagePath($page, $parameters, $entities));

        $this->getSession()->visit($path);
    }

    public function assertPage(Page $page, $arguments = null)
    {
        list($parameters, $entities) = $this->extractTable($arguments);

        $path = $this->locatePath($this->resolvePagePath($page, $parameters, $entities));
        $this->assertSession()->addressEquals($path);
    }

    public function getPage($page)
    {
        if (isset($this->pages[$page])) {
            return $this->pages[$page];
        }

        $class = $this->getPageClassResolver()->resolveName($page);

        $this->pages[$page] = $this->getPageClassResolver()->create(
            $this->getSession(),
            $class
        );

        return $this->pages[$page];
    }

    protected function getEntityFromRecordBag($entity, $field)
    {
        $class = $this->resolveEntity($entity)->getName();

        $record = $this
            ->getRecordBag()
            ->getCollection($class)
            ->search($field)
        ;

        if (null === $record) {
            throw new \Exception(sprintf(
                'No entity %s has been found for  "%s"',
                $class,
                $field
            ));
        }

        return $record->getEntity();
    }

    protected function resolvePagePath(Page $page, $parameters, $entities)
    {
        $path = $page->getPath();

        foreach ($parameters as $key => $value) {
            $path = str_replace(sprintf('{%s}', $key), $value, $path);
        }

        if (!preg_match_all('/\{([a-zA-Z0-9]+\.[a-zA-Z0-9]+)\}/', $path, $matches)) {
            return $path;
        }

        $properties = array();

        foreach ($matches[1] as $parameter) {
            list($entityName, $field) = explode('.', $parameter);

            if (!isset($entities[$entityName])) {
                throw new \Exception(sprintf(
                    'No entity can be resolved for "%s"',
                    $entityName
                ));
            }

            $entity = $entities[$entityName];
            $properties[] = PropertyAccess::createPropertyAccessor()
                ->getValue($entity, $field)
            ;
        }

        foreach ($matches[0] as $index => $pattern) {
            $path = str_replace($pattern, $properties[$index], $path);
        }

        return $path;
    }

    protected function extractTable($parameters = null)
    {
        if (null === $parameters) {
            return [[], []];
        }

        if ($parameters instanceof TableNode) {
            $parameters = $parameters->getRowsHash();
        }

        if (!is_array($parameters)) {
            throw new \InvalidArgumentException(
                'You must precised a valid array or Behat\Gherkin\Node\TableNode to extract'
            );
        }

        $entities = [];

        foreach ($parameters as $name => $value) {
            $matches = array();
            if (preg_match('/^the (.+) "([^"]+)"$/', $value, $matches)) {
                $entity = $this->getEntityFromRecordBag($matches[1], $matches[2]);

                $entities[$name] = $entity;
                unset($parameters[$name]);
            }
        }

        return array($parameters, $entities);
    }
}