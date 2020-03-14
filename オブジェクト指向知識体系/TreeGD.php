<?php

class TreeGD
{
    private $serialized_array;
    private $half_systemized_array;
    private $dir;
    private $gd;

    function __construct(TreeConverter $tree_converter, string $dir)
    {
        $this->serialized_array = $tree_converter->serialize()->get();
        $this->half_systemized_array = $tree_converter->half_systemize()->get();
        $this->dir = $dir;
        $this->gd = (new GD(2000, 1414));
    }

    function get_serialized_as_png()
    {
        $this->get_as_png($this->serialized_array);
    }

    function get_half_systemized_as_png()
    {
        $this->get_as_png($this->half_systemized_array);
    }

    function get_full_systemized_as_png()
    {
        $this->get_as_png(null);
    }

    private function get_as_png(?array $array)
    {

    }

}


?>