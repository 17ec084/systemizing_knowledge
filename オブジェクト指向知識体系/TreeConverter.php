<?php
/**
 * クラス関係記録シートから生成した任意の形式のツリーを直列形や半体系形、全体系形に変換する。<br>
 * ツリーはXmlSheetTree型である必要がある。
 * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
 *
 */
class TreeConverter
{
    private $tree_as_array; private $type; private $sheet_tree;

    function __construct(XmlSheetTree $sheet_tree)
    {
        $this->tree_as_array = $sheet_tree->get_as_assoc_array();
        $this->type = "any";
        $this->sheet_tree = $sheet_tree;
    }

    function get():array{return $this->tree_as_array;}

    function serialize():TreeConverter
    {
        if(strcmp($this->type, "serialized")==0)
            return $this;
        $all_classes_names = $this->sheet_tree->get_all_classes_names();
        $serialized = [];
        foreach($all_classes_names as $class_name)
        {
            $parents = $this->sheet_tree->get_parents_of($class_name);
            $parents_ = [];
            foreach($parents as $parent)
            {
                $parent["parents"] = [];//親より上の先祖の情報を消す
                array_push($parents_, $parent);
            }

            array_push($serialized, ["name"=>$class_name, "parents"=>$parents_]);
        }
        $this->type = "serilized";
        $this->tree_as_array =  $serialized;
        return $this;
    }

    function half_systemize():TreeConverter
    {
        if(strcmp($this->type, "half_systemized")==0)
            return $this;
        if(strcmp($this->type, "serialized")!=0)
            $serialized = $this->serialize()->get();
        else $serialized = $this->get();

        $dbg = false;
        function elem_cnt(?array $array){if($array==null)return 0;return count($array);}

        $all_classes_names_having_no_child = $this->sheet_tree->get_kid_classes();
        $all_classes_names_having_child
        = array_diff(array_column($serialized, "name"), $all_classes_names_having_no_child);
        $half_systemized = $serialized;

        while(elem_cnt($all_classes_names_having_child))
            //$half_systemized自体をループ内部で書き換えるので、
            //while文は重要。漏れを防いでくれるのだ。
            foreach($all_classes_names_having_no_child as $class_name_having_no_child)
            {
                if($dbg) print $class_name_having_no_child."\n";
                $parents = $this->sheet_tree->get_parents_of($class_name_having_no_child);
                foreach($parents as $parent)
                {
                    if($dbg)print " ".$parent["name"]."\n";
                    if(elem_cnt($all_classes_names_having_child)==0)
                        break;
                        /*
                         * $half_systemizedから
                         * $class_having_no_childを探し、┓
                         * その親$parentを探し、         ┻(この座標は文字列で指定できる)
                         * そのparentキーの値として※を書き込む。
                         * ※
                         * $parentがall_classes_names_having_childの要素でない場合、
                         * 何も書き込まず、何もしないでbreakする。
                         * $parentがall_classes_names_having_childの要素の場合、
                         * キー:name, 値:get_parents_of($parent)の各要素
                         * を書き込み、
                         * $parentをall_classes_names_having_childから消去し、
                         * その各要素を$parentとみて再帰する。
                         *
                         */
                        $half_systemized =
                        (
                            (array_search($parent["name"], $all_classes_names_having_child)!==false)
                            ?
                            $this->overwrite_parent
                            (
                                $dbg,
                                $half_systemized,
                                $class_name_having_no_child."/".$parent["name"],
                                $this->sheet_tree->get_parents_of($parent["name"]),
                                $all_classes_names_having_child
                                )
                            :
                            $half_systemized
                            );

                }
            }
        //「子クラスを持つクラス」が根になっているようなノードを消す
        for($i = elem_cnt($half_systemized); $i>-1; $i--)
            if(array_search($half_systemized[$i]["name"], $all_classes_names_having_no_child)===false)
                unset($half_systemized[$i]);
        $this->type = "half_systemized";
        $this->tree_as_array = array_merge($half_systemized);
        return $this;
    }

    private function overwrite_parent
    (
        bool $dbg,
        array $half_systemized,
        string $class_path,
        array $parents,
        array &$all_classes_names_having_child
    ):array
    {
        if($dbg)$stdin = trim(fgets(STDIN));
        $classes = explode("/", $class_path);
        $target_as_str = "\$half_systemized";
        while(elem_cnt($classes))
        {
            $class_name = $classes[0];
            $target_as_str = $this->get_idx(array_shift($classes), $target_as_str, $half_systemized);
        }
        if(array_search($class_name, $all_classes_names_having_child)===false)
        {
            if($dbg)print "  クラスを持つクラスの集合に、対象ノードがなかったので、抜けます\n";
            return $half_systemized;
        }

        if($dbg)print "  ".$target_as_str."\n";
        eval("$target_as_str = \$parents;");
        if($dbg)
        {
            print "  evalにより、\$half_systemizedは次のようになった。\n";
            var_dump($half_systemized);
        }
        foreach($parents as $parent)
        {
            if($dbg)print "---\n".$parent["name"]."に対して再帰します。\n---\n";
            $half_systemized =
            $this->overwrite_parent
            (
                $dbg,
                $half_systemized,
                $class_path."/".$parent['name'],
                $this->sheet_tree->get_parents_of($parent["name"]),
                $all_classes_names_having_child
                );
            if($dbg)print "---\n".$parent["name"]."に対する再帰終わり。\n---\n";
        }

        if(array_search($class_name, $all_classes_names_having_child)!==false)
        {
            unset($all_classes_names_having_child[array_search($class_name, $all_classes_names_having_child)]);
            if($dbg)print "  子クラスを持つクラスの集合から、".$class_name."を抜きました\n";
        }
        return $half_systemized;
    }

    private function get_idx(string $class_name, string $target_as_str, array $half_systemized):string
    {
        $array = [];

        eval("\$array = $target_as_str;");
        $i = 0;
        foreach($array as $elem)
            if(strcmp($elem["name"], $class_name)==0)
                return $target_as_str."[$i]['parents']";
            else $i++;
                return $target_as_str;
    }


}


?>