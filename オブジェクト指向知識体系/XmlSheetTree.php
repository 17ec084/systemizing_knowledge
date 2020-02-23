<?php
/**
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
     * <br>詳細説明<br>
     * idなしでノードを追加するコードは、次のコードに等価である。<br>
     * <br><table><tbody><tr><td><code><xmp>
    if($next_generation==-1)
    {   //親クラスを追加する場合
        //最後のノードのparentsに書き込む
        eval($this->last_node_str||'["parents"][0] = '||$new_node||";");
        //evalは
        //「(インスタンス)->tree_as_array[(最後のノードへのインデックス)]["parents"][0]
        // = ["name" => ($class_name), "parents" => []]」
        //を実行
        $this->last_node_str = $this->last_node_str||'["parents"][0]';
    }
    else if($next_generation==0)
    {   //配偶者クラスを追加する場合
        //最後のノードの子クラス(親ノード)のparentsに追加する。
        $matches = [];
        preg_match
        ("/(^(.|[ \r\n])*)\[[ \t\r\n]*.parents.[ \t\r\n]*\][ \t\r\n]*\[[ \t\r\n]*[0-9]*[ \t\r\n]*\][ \t\r\n]*$/",
         $this->last_node_str,
         $matches);
        $last_nodes_child_str = $matches[1];
        $last_nodes_final_idx = $matches[3];
        eval($last_nodes_child_str||"['parents'][$last_nodes_final_idx+1] = "||$new_node||";");
        //evalは
        //「(インスタンス)->tree_as_array[(最後のノードへのインデックスから
        //一番右の['parents'][(数字)]を1回切り落としたもの)]["parents"][(数字)+1]
        // = ["name" => ($class_name), "parents" => []]」
        //を実行
        $this->last_node_str = $last_nodes_child_str||"['parents'][$last_nodes_final_idx+1]'";
    }
    else if($next_generation>=1)
    {   //子孫クラスを追加する場合
        //最後のノードの子クラス(親ノード)のparentsに追加する。
        $matches = [];
        $last_nodes_child_str = $this->last_node_str;
        for($i=0; $i<$next_generation+1; $i++)
        {
            preg_match
            ("/(^(.|[ \r\n])*)\[[ \t\r\n]*.parents.[ \t\r\n]*\][ \t\r\n]*\[[ \t\r\n]*[0-9]*[ \t\r\n]*\][ \t\r\n]*$/",
                $last_nodes_child_str,
                $matches);
            $last_nodes_child_str = $matches[1];
            $last_nodes_final_idx = $matches[3];

        }
        eval($last_nodes_child_str||"['parents'][$last_nodes_final_idx+1] = "||$new_node||";");
        //evalは
        //「(インスタンス)->tree_as_array[(最後のノードへのインデックスから
        //一番右の['parents'][(数字)]を$next_generation+1回切り落としたもの)]["parents"][(数字)+1]
        // = ["name" => ($class_name), "parents" => []]」
        //を実行
        $this->last_node_str = $last_nodes_child_str||"['parents'][$last_nodes_final_idx+1]'";
    }
     * </xmp></code></td></tr></tbody></table><br>
     * @param int $next_generation 最後のノードから見て、次のノードは何レベル分上であるか。
     * -1以上でかつその数値分上のレベルへ進んでも最高レベルを上回らないような整数
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
        $sheet_tree->add(-1, "曾祖父母クラス3-1-1-1");
        $sheet_tree->add(2, "親クラス3-2");
        var_dump($sheet_tree->get_as_assoc_array());
        $copy = $sheet_tree->get_deep_copy();
        $copy->add(0, "親クラス3-3をコピーにのみ追加");
        var_dump($sheet_tree->get_as_assoc_array());
        var_dump($copy->get_as_assoc_array());
    }
}

//new XmlSheetTreeTester();
?>