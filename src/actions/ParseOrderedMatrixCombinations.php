<?php

namespace markhuot\voyage\actions;

class ParseOrderedMatrixCombinations
{
    /**
     * @param array<string, mixed> $matrix
     * @return array<array-key, array{collection: string, matrix: array<string, string>}>
     */
    public function __invoke(array $matrixes): array
    {
        $denormalized = $this->denormalize($matrixes);

        return $denormalized;
    }

    protected function denormalize($matrix) {
        $denormalized = [];

        foreach ($matrix as $collection => $config) {
            $combinations = [
                ['collection' => $collection]
            ];

            foreach ($config as $key => $values) {
                if (is_array($values)) {
                    $newCombinations = [];
                    foreach ($combinations as $combination) {
                        foreach ($values as $valueKey => $value) {
                            $newCombination = $combination;
                            $newCombination['matrix'][$key] = $valueKey;
                            if (is_array($value) && isset($value['depends_on'])) {
                                $newCombination['depends_on'] = $value['depends_on'];
                            } elseif (is_string($value) && strpos($value, 'depends_on:') === 0) {
                                $dependsOnString = substr($value, strlen('depends_on:'));
                                $parts = explode('=', $dependsOnString);
                                if (count($parts) === 2) {
                                    $newCombination['depends_on'] = [$parts[0] => $parts[1]];
                                }
                            }
                            $newCombinations[] = $newCombination;
                        }
                    }
                    $combinations = $newCombinations;
                }
            }
            $denormalized = array_merge($denormalized, $combinations);
        }

        usort($denormalized, function ($a, $b) {
            $aHasDepends = isset($a['depends_on']);
            $bHasDepends = isset($b['depends_on']);

            if (!$aHasDepends && !$bHasDepends) {
                return 0;
            }

            if (!$aHasDepends && $bHasDepends) {
                return -1;
            }

            if ($aHasDepends && !$bHasDepends) {
                return 1;
            }

            if ($aHasDepends && $bHasDepends) {
                $aDependsOn = $a['depends_on'];
                $bDependsOn = $b['depends_on'];

                foreach ($aDependsOn as $depKeyA => $depValA) {
                    if (isset($a['matrix'][$depKeyA])) {
                        foreach($bDependsOn as $depKeyB => $depValB){
                           if(isset($b['matrix'][$depKeyB])){
                                if($a['matrix'][$depKeyA] === $depValB){
                                    return 1;
                                }
                                if($b['matrix'][$depKeyB] === $depValA){
                                    return -1;
                                }
                           }
                        }
                    }
                }
            }

            return 0;
        });

        return $denormalized;
    }
}
