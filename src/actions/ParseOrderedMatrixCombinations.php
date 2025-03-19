<?php

namespace markhuot\voyage\actions;

class ParseOrderedMatrixCombinations
{
    public function __invoke(array $matrix)
    {
        $normalized = $this->normalizeMatrix($matrix);
        $sortedKeys = $this->sortByDependencies($normalized);

        return $this->generatePairs($sortedKeys, $normalized);
    }

    protected function normalizeMatrix(array $matrix): array {
        $normalized = [];
    
        foreach ($matrix as $key => $values) {
            if (!is_array($values)) {
                $values = [$values]; // Ensure array format
            }
    
            foreach ($values as $subKey => $value) {
                if (is_string($subKey)) {
                    // Handle 'key => value' case (e.g., 'relations' => 'depends_on:phase=default')
                    $normalized[$key][$subKey] = $value;
                } else {
                    // Handle regular indexed arrays (e.g., 'phase' => ['default', ...])
                    $normalized[$key][$value] = null;
                }
            }
        }
    
        return $normalized;
    }

    protected function sortByDependencies(array $items): array {
        $sorted = [];
        $visited = [];
    
        $visit = function ($item, $key) use (&$sorted, &$visited, &$items, &$visit) {
            if (isset($visited[$key])) {
                return; // Already processed
            }
            $visited[$key] = true;
    
            if (isset($items[$key])) {
                foreach ($items[$key] as $subKey => $dependency) {
                    if ($dependency && str_starts_with($dependency, 'depends_on:')) {
                        preg_match('/depends_on:(\w+)=(\w+)/', $dependency, $matches);
                        if ($matches) {
                            [, $depKey, $depValue] = $matches;
                            if (isset($items[$depKey][$depValue])) {
                                $visit($items[$depKey], $depValue);
                            }
                        }
                    }
                }
            }
    
            $sorted[] = $key;
        };
    
        foreach ($items as $key => $values) {
            $visit($values, $key);
        }
    
        return $sorted;
    }

    protected function generatePairs(array $sortedKeys, array $matrix): array {
        $result = [];
        $combinations = [[]];
    
        foreach ($sortedKeys as $key) {
            $newCombinations = [];
            foreach ($combinations as $combo) {
                foreach (array_keys($matrix[$key]) as $value) {
                    $newCombinations[] = array_merge($combo, [$key => $value]);
                }
            }
            $combinations = $newCombinations;
        }
    
        return $combinations;
    }
}
