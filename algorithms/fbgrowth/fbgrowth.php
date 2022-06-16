<?php

include 'tree.php';
class FPGrowth
{
    protected $support = 0;
    protected $confidence = 0;

    private $patterns;
    private $rules;

    private $dataset;
    public $duration = 0;


    /**
     * FPGrowth constructor.
     * @param $support 1, 2, 3 ...
     * @param $confidence 0 ... 1
     */
    public function __construct($dataset,$support, $confidence)
    {
        $this->support = $support;
        $this->confidence = $confidence;
        $this->dataset = $dataset;
        $start = microtime(true);
        $this->patterns = $this->toFrequentItemset($this->findFrequentPatterns($dataset, $this->support));
        $this->duration = (microtime(true) - $start)*1000;
    }

    /**
     * Do algorithm
     * @param $transactions
     */
    public function run($transactions)
    {
        $this->patterns = $this->findFrequentPatterns($transactions, $this->support);
        $this->rules = $this->generateAssociationRules($this->patterns, $this->confidence);
    }

    protected function findFrequentPatterns($transactions, $support_threshold)
    {
        $tree = new FPTree($transactions, $support_threshold, null, null);
        return $tree->minePatterns($support_threshold);
    }
    protected function toFrequentItemset($array){
        $freq = [];$i = 0;
        foreach($array as $key => $_){

            $items = explode(",",$key);
            if(!array_key_exists($i,$freq)){
                $freq += [
                    $i => []
                ];
            }
            foreach($items as $item){
                array_push($freq[$i],$item); 
            }
            $i++;
        }
        return $freq;
    }
    public function AssociationRules(){
        $assRules = new AssociationRules($this->patterns,$this->support,$this->confidence);
        $assRules->makeTable($this->dataset);
    
        return $assRules->process();
    }

    protected function generateAssociationRules($patterns, $confidence_threshold)
    {
        $rules = [];
        foreach (array_keys($patterns) as $itemsetStr) {
            $itemset = explode(',', $itemsetStr);
            $upper_support = $patterns[$itemsetStr];
            for ($i = 1; $i < count($itemset); $i++) {
                foreach (self::combinations($itemset, $i) as $antecedent) {
                    sort($antecedent);
                    $antecedentStr = implode(',', $antecedent);
                    $consequent = array_diff($itemset, $antecedent);
                    sort($consequent);
                    $consequentStr = implode(',', $consequent);
                    if (isset($patterns[$antecedentStr])) {
                        $lower_support = $patterns[$antecedentStr];
                        $confidence = (floatval($upper_support) / $lower_support);
                        if ($confidence >= $confidence_threshold) {
                            $rules[] = [$antecedentStr, $consequentStr, $confidence];
                        }
                    }
                }
            }
        }
        return $rules;
    }

    public static function iter($var)
    {

        switch (true) {
            case $var instanceof \Iterator:
                return $var;

            case $var instanceof \Traversable:
                return new \IteratorIterator($var);

            case is_string($var):
                $var = str_split($var);

            case is_array($var):
                return new \ArrayIterator($var);

            default:
                $type = gettype($var);
                throw new \InvalidArgumentException("'$type' type is not iterable");
        }

        return;
    }

    public static function combinations($iterable, $r)
    {
        $pool = is_array($iterable) ? $iterable : iterator_to_array(self::iter($iterable));
        $n = sizeof($pool);

        if ($r > $n) {
            return;
        }

        $indices = range(0, $r - 1);
        yield array_slice($pool, 0, $r);

        for (; ;) {
            for (; ;) {
                for ($i = $r - 1; $i >= 0; $i--) {
                    if ($indices[$i] != $i + $n - $r) {
                        break 2;
                    }
                }

                return;
            }

            $indices[$i]++;

            for ($j = $i + 1; $j < $r; $j++) {
                $indices[$j] = $indices[$j - 1] + 1;
            }

            $row = [];
            foreach ($indices as $i) {
                $row[] = $pool[$i];
            }

            yield $row;
        }
    }
}