<?php

namespace markhuot\voyage\actions;

class ParseOrderedMatrixCombinations
{
    /**
     * @param array<string, mixed> $matrix
     * @return array<array<string, string|null>>
     */
    public function __invoke(array $matrix): array
    {
        $normalized = $this->normalizeMatrix($matrix);
        $sortedKeys = $this->sortByDependencies($normalized);

        return $this->generatePairs($sortedKeys, $normalized);
    }

    /**
     * @param array<string, mixed> $matrix
     * @return array<string, array<string, string|null>>
     */
    protected function normalizeMatrix(array $matrix): array {
        /** @var array<string, array<string, string|null>> $normalized */
        $normalized = [];
    
        foreach ($matrix as $key => $values) {
            if (!is_array($values)) {
                $values = [$values]; // Ensure array format
            }
    
            foreach ($values as $subKey => $value) {
                if (is_string($subKey)) {
                    // Handle 'key => value' case (e.g., 'relations' => 'depends_on:phase=default')
                    if (is_string($value) || is_null($value)) {
                        $normalized[$key][$subKey] = $value;
                    } else {
                        $normalized[$key][$subKey] = null;
                    }
                } else {
                    // Handle regular indexed arrays (e.g., 'phase' => ['default', ...])
                    if (is_string($value) || is_numeric($value)) {
                        $normalized[$key][(string)$value] = null;
                    } else {
                        $normalized[$key]['invalid'] = null;
                    }
                }
            }
        }
    
        return $normalized;
    }

    /**
     * @param array<string, array<string, string|null>> $items
     * @return array<string>
     */
    protected function sortByDependencies(array $items): array {
        /** @var array<string> $sorted */
        $sorted = [];
        /** @var array<string, bool> $visited */
        $visited = [];
    
        /**
         * @param array<string, string|null> $item
         * @param string $key
         */
        $visit = function (array $item, string $key) use (&$sorted, &$visited, &$items, &$visit) {
            if (isset($visited[$key])) {
                return; // Already processed
            }
            $visited[$key] = true;
    
            if (isset($items[$key])) {
                foreach ($items[$key] as $subKey => $dependency) {
                    if (is_string($dependency) && str_starts_with($dependency, 'depends_on:')) {
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

    /**
     * @param array<string> $sortedKeys
     * @param array<string, array<string, string|null>> $matrix
     * @return array<array<string, string>>
     */
    protected function generatePairs(array $sortedKeys, array $matrix): array {
        /** @var array<array<string, string>> $combinations */
        $combinations = [[]];
    
        foreach ($sortedKeys as $key) {
            /** @var array<array<string, string>> $newCombinations */
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
