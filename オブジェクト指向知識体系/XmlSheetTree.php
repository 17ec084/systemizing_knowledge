<?php

require 'TreeConverter.php';
/**
 * 警告<br>
 * 本クラスでオーバライドした__toString()について、Xdebugの入った環境でバグを引き起こす潜在的な脆弱性が見つかった。<br>
 * インスタンスを文字列に変換する機能を利用する場合、Xdebugを無効にするか、var_dump関数を書き換えない設定をすべきである。
 * <hr>
 *
 * 本来はXmlReaderSheetTreeの内部privateクラスとして造りたかったクラス。<br>
 * 外部からの呼び出しは想定していない。<br>
 * シートに対応する木構造を表すクラス<br>
 * <br>
 * 「順番」について<br>
 * 本クラスで表現される木構造には、「順番」という概念がある。<br>
 * その順番というのは、各レベルにおいて同レベル同士の前後関係を元に決定され、辞書順のように上のレベルの前後関係ほど、順番を決定する際に優先される。<br>
 * 例えば最上位の1番目のノードを1、その1番目の子ノード(※)を1-1のようにあらわすとき、<br>
 * 1, 1-1, 2-1, 1-2, 1-1-1の5種類のノードは、最初から順に並べたとき、<br>
 * 1, 1-1, 1-1-1, 1-2, 2-1のようになる。<br>
 * 「順番」を考えるとxmlから木構造を読み取るのに効率が良い。<br>
 * (順番の「最後」のノードを基準に、配列を更新すべきインデックスを決定できる)<br>
 * ※実際には下のノードに親クラスを記録していくため、木構造の親子関係と、表現するクラス間の親子関係は真逆となる
 * @see XmlSheetTree::add
 * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
 *
 *
 */
class XmlSheetTree
{
    private $tree_as_array;
    private $last_node_str;//評価されると「tree_as_arrayの『最後』のノード」となるような文字列


    /**
     * $this = argではなく、$this->すべてのプロパティ = arg->すべてのプロパティを行っている。<br>
     * つまり、引数として自分自身の型のインスタンスを受け取り、それを「ディープコピー」している。<br>
     *
     * @param XmlSheetTree $tree
     */
    function __construct(?XmlSheetTree $tree)
    {
        $this->tree_as_array = $tree==null?["id"=> "meta-"]:$tree->tree_as_array;
        $this->last_node_str = $tree==null?'$this->tree_as_array':$tree->last_node_str;

    }

    /**
     * 最後のノードの次に、新しいノードを加える<br>
     * @param int $next_generation 最後のノードから見て、次のノードは何レベル分上であるか。
     * -1以上でかつその数値分上のレベルへ進んでも最高レベルを上回らないような整数。<br>
     * この引数によるノードの追加の仕組みは一見無駄に複雑だが、<br>
     * 「クラス関係記録シート」をXMLReaderでread()しながら追加していくとき、<br>
     * 「END ELEMENT」ノード(閉じタグノード)に途中で遭遇した回数-1がこのパラメータに対応するため、<br>
     * XMLReaderを利用するコードが簡潔に書けるようになる。
     * @param string $class_name そのノードで表現するクラス名
     *
     */
    function add($next_generation, $class_name):void
    {
        if(!is_numeric($next_generation))
            throw new Exception("引数の型が異常です");
        if($next_generation<-1)
            throw new Exception("いきなり親より上の世代の尊属クラスを作ることはできません");
        $new_node = "['name' => '".$class_name."', 'parents' => []]";

        //まずidなしで追加する
            $matches = [];
            $last_node_child_str = $this->last_node_str;
            $last_node_final_idx = -1;
            for($i=0; $i<$next_generation+1; $i++)
            {
                preg_match
                ("/(^(.|[ \r\n])*)\[[ \t\r\n]*.parents.[ \t\r\n]*\][ \t\r\n]*\[[ \t\r\n]*([0-9]*)[ \t\r\n]*\][ \t\r\n]*$/",
                    $last_node_child_str,
                    $matches);
                $last_node_child_str = $matches[1];
                $last_node_final_idx = $matches[3];
            }
            eval($last_node_child_str."['parents'][$last_node_final_idx+1] = ".$new_node.";");
            $this->last_node_str = $last_node_child_str."['parents'][".($last_node_final_idx+1)."]";

        //idをparentsの前に追加する
            //idを特定する
            $last_node_child_id = "";
            eval("\$last_node_child_id = ".$last_node_child_str."['id'];");
            if(strcmp($last_node_child_id, "meta-")==0)
                $id = $last_node_final_idx+2;
            else
           {
                $matches = [];
                //preg_match("/^((.|[ \r\n])*[^0-9]*)[ \r\n]*$/", $last_node_child_id, $matches);
                $id = $last_node_child_id."-".($last_node_final_idx+2);

            }

            //idを追加する
            eval("$this->last_node_str"."['id'] = '$id';");

            //見栄えのため、idをparentsの前に移動(parentsを一度$tmpへコピーしてunsetし、再び追加)
            eval("\$tmp = $this->last_node_str"."['parents'];");
            eval("unset($this->last_node_str"."['parents']);");
            eval("$this->last_node_str"."['parents'] = \$tmp;");

    }

    function set_tree_as_array(array $tree_as_array){$this->tree_as_array = tree_as_array;}

    /**
     * @return array
     * <br>
     * [<br>
     * 　[<br>
     * 　"name" => string,<br>
     * 　"id" => "1",<br>
     * 　"parents" =><br>
     * 　[<br>
     * 　　[<br>
     * 　　"name" => string,<br>
     * 　　"id" => "1-1",<br>
     * 　　"parents" => ...<br>
     * 　　],<br>
     * 　　[<br>
     * 　　"name" => string,<br>
     * 　　"id" => "1-2",<br>
     * 　　"parents" => ...<br>
     * 　　],<br>
     * 　　...<br>
     * 　]<br>
     * 　],<br>
     * 　...<br>
     * ]
     */
    function get_as_assoc_array():?array{return $this->tree_as_array["parents"];}
    /*
     * フィールド$tree_as_arrayは再帰処理に最適化するために
     * ["id" => "meta-", "parents" => [(ツリー)] ]という構造をしている。
     * ここから[(ツリー)]を取り出して返却する。
     */

    function get_deep_copy():XmlSheetTree{return new XmlSheetTree($this);}

    /**
     * 子クラスを持たないクラスの<b>クラス名</b>を返却する
     * @return array of string
     */
    function get_kid_classes():array
    {
        $serialized = $this->get_as_serialized();
        $all_classes_names = array_column($serialized, "name");
        $all_classes_parentses = array_column($serialized, "parents");
        $all_classes_names_having_child = [];
        foreach($all_classes_parentses as $class_parents)
        {
            $parents_names = array_column($class_parents, "name");
            $all_classes_names_having_child = array_merge($all_classes_names_having_child, $parents_names);
        }
        $all_classes_names_having_child = array_unique($all_classes_names_having_child);
        $all_classes_names_having_no_child = array_diff($all_classes_names, $all_classes_names_having_child);
        return $all_classes_names_having_no_child;
    }

    function get_as_serialized():array
    {
        return (new TreeConverter($this->get_deep_copy()))->serialize()->get();
    }
    /*
     function get_as_serialied():array
     {
         $all_classes_names = $this->get_all_classes_names();
         $serialized = [];
         foreach($all_classes_names as $class_name)
         {
         $parents = $this->get_parents_of($class_name);
         $parents_ = [];
         foreach($parents as $parent)
         {
         $parent["parents"] = [];//親より上の先祖の情報を消す
         array_push($parents_, $parent);
         }

         array_push($serialized, ["name"=>$class_name, "parents"=>$parents_]);
         }
         return $serialized;
     }
     */

    function get_as_half_systemized():array//半体系形の配列を返却する
    {
        return (new TreeConverter($this->get_deep_copy()))->half_systemize()->get();
    }

    /**
     * 指定されたクラスの親クラスを取得する
     */
    function get_parents_of(string $class_name):array
    {
        return $this->private_get_parents_of($class_name, $this->tree_as_array["parents"], []);
    }

    /**
     * 再帰するprivateメソッド。
     * @see XmlSheetTree::get_parents_of
     */
    private function private_get_parents_of(string $class_name, array $array, array $parents_already_found):array
    {
        foreach($array as $elem)
        {
            if(strcmp($elem["name"], $class_name)==0)
                $parents_already_found = array_merge($parents_already_found, $elem["parents"]);
            else
               if($elem["parents"]!=null)
                 {
                     $parents_already_found = $this->private_get_parents_of($class_name, $elem["parents"], $parents_already_found);
                 }
        }
        return $parents_already_found;
    }


    function get_all_classes_names():array
    {
        $array_as_str = (string)$this;

        $other_than_name = "(?:(?:(?:[^n]|(?:n[^a]))|(?:na[^m]))|(?:nam[^e]))*";
        //文字列「name」以外にマッチするパターン

        $garbage = "\"]=>[ \t\r\n]*string\([0-9]*\)[ \t\r\n]*\"";
        //var_dumpで「name」キーとその値の間に挟まった内容 にマッチするパターン

        $until_just_before_name_value = $other_than_name."name".$garbage;

        $name_value = '[^"]*';

        $ptn_without_delimiter = "(?:".$until_just_before_name_value."(".$name_value."))";

        $pattern = "/$ptn_without_delimiter/s";

        $matches = [];
        preg_match_all($pattern, $array_as_str, $matches);

        return array_unique($matches[1]);
    }

    //@override
    /**
     * @deprecated Xdebugの入った環境でバグを引き起こす潜在的な脆弱性が見つかった。<br>
     * この機能を利用する場合、Xdebugを無効にするか、var_dump関数を書き換えない設定をすべきである。
     * @return string
     */
    function __toString()
    {
        ob_start();//output buffer開始
        var_dump($this->get_deep_copy());//コンソールに流れず、バッファに溜まる
        $array_as_str = ob_get_clean();//バッファに溜まった内容を取り出す。同時にバッファも終了しているようだ
        return $array_as_str;
    }
}

/*以下はテストクラス*/

class XmlSheetTreeTester
{
    function __construct()
    {
        $sheet_tree = new XmlSheetTree(null);
        var_dump($sheet_tree->get_as_assoc_array());
        $sheet_tree->add(-1, "子クラス1");
        var_dump($sheet_tree->get_as_assoc_array());
        $sheet_tree->add(0, "子クラス2");
        var_dump($sheet_tree->get_as_assoc_array());
        $sheet_tree->add(-1, "親クラス2-1");
        var_dump($sheet_tree->get_as_assoc_array());
        $sheet_tree->add(1, "子クラス3");
        var_dump($sheet_tree->get_as_assoc_array());
        $sheet_tree->add(-1, "親クラス3-1");
        $sheet_tree->add(-1, "祖父母クラス3-1-1");
      //$sheet_tree->add(-1, "曾祖父母クラス3-1-1-1");
        $sheet_tree->add(-1, "子クラス2");
        $sheet_tree->add(2, "親クラス3-2");
        var_dump($sheet_tree->get_as_assoc_array());
        $copy = $sheet_tree->get_deep_copy();
        $copy->add(0, "親クラス3-3をコピーにのみ追加");
        var_dump($sheet_tree->get_as_assoc_array());
        var_dump($copy->get_as_assoc_array());

        var_dump($copy->get_all_classes_names());
        var_dump($copy->get_parents_of("子クラス3"));
        print "--------------------------\n";
        var_dump($copy->get_as_serialized());
        var_dump($copy->get_kid_classes());
        var_dump($copy->get_as_half_systemized());
    }
}

new XmlSheetTreeTester();
?>