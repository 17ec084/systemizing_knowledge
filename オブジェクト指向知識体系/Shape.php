<?php
/**
 * 形
 * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
 *
 */
class Shape
{
    private const x=0;
    private const y=1;
    private $coords = [[]];

    //超重要。もしもsetterを抽象化する場合、getterも抽象化する必要がある。さもなくば親の初期値が返却されてしまう。
    /**
     * 各頂点を返却
     */
    function get_coords():array{return $this->coords;}

    /**
     * 各頂点を指定
     */
    function set_coords(array $coords):void{$this->coords = $coords;}



}


/**
 * <h1>このクラスの存在意義</h1>
 * <p>Shapeクラスのメソッド内における$thisは、子クラスの$thisとは別物である。
 * このことを知らずに、図形を回転させたり平行移動したりするメソッドをShape内に書いたはいいが、
 * 子クラスのインスタンスからメソッドを読んでもcoordsが空になってしまい、使えなかった。</p>
 * <p>この問題を解決するために、Shapeオブジェクトを外から操作する別のクラスが必要となった。それがこのクラスだ。</p>
 * @see Shape
 * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
 *
 */
class ShapeController
{

    private $shape;

    function __construct(Shape $shape)
    {
        $this->shape = $shape;
    }

    function get_shape(){return $this->shape;}
    function get_coords():array{return $this->shape->get_coords();}


    /**
     * 平行移動
     * @param int $dx
     * @param int $dy
     */
    function move(int $dx, int $dy):ShapeController
    {
        //デフォルト操作
        $x = 0; $y = 1;
        $coords = [];
        foreach($this->shape->get_coords() as $coord)
        {
            $coord[$x] = $coord[$x]+$dx;
            $coord[$y] = $coord[$y]+$dy;
            array_push($coords, $coord);
        }
        $this->shape->set_coords($coords);
        //hogeクラスでオーバライドの場合
        //→if($this->shape instanceof Hoge)


        return $this;
    }

    /**
     * 原点を中心に回転
     * @param float $angle 度数法による回転角
     */
    function rotate_origin(float $angle):ShapeController
    {
        $angle = $angle*M_PI/180;
        $x = 0; $y = 1;
        $coords = [];
        foreach($this->shape->get_coords() as $coord)
        {
            $coord_x = $coord[$x]*cos(-$angle)-$coord[$y]*sin(-$angle);
            $coord_y = $coord[$x]*sin(-$angle)+$coord[$y]*cos(-$angle);


            $coord[$x] = $coord_x;
            $coord[$y] = $coord_y;
            array_push($coords, $coord);
        }
        $this->shape->set_coords($coords);

        return $this;
    }

    /**
     * 中心を軸に回転する
     * @param float $angle
     */
    function rotate_center(float $angle):ShapeController
    {
        if($this->shape instanceof Rectangle)
        {
            $x = 0; $y = 1;

            $angle = $this->shape->get_angle()+$angle;

            $width = $this->shape->get_width();
            $height = $this->shape->get_height();
            $center_x = $this->shape->get_center()[$x];
            $center_y = $this->shape->get_center()[$y];
            $origin_centered = new Rectangle();//中心を原点に持ってきたインスタンスを作る
            $origin_centered
            ->set_coords
            (-$width/2, $height/2,//A
              $width/2, $height/2,//B
              $width/2,-$height/2,//C
             -$width/2,-$height/2)//D
            ;
            $controller = new ShapeController($origin_centered);
            $this->shape->set_coords
            (
                $controller
                ->rotate_origin($angle)
                ->move($center_x, $center_y)
                ->get_coords()
            );
        }
        else
       {
            throw new Exception('この図形には対応していません');
       }

    }


    /**
     * 引数で与えた座標が図形によって占められているか否か
     */
    function has_eaten(array $coord):bool
    {
        if($this->shape instanceof Rectangle)
        {

        }
    }


    /**
     * 図形の左端を返却
     */
    function get_leftest():int
    {
        $x = 0;
        $coords = $this->shape->get_coords();
        $leftest = $coords[0][$x];
        foreach($coords as $coord)
            if($coord[$x]<$leftest)
                $leftest = $coord[$x];
        return $leftest;
    }

    /**
     * 図形の右端を返却
     */
    function get_rightest():int
    {
        $x = 0;
        $coords = $this->shape->get_coords();
        $rightest = 0;
        foreach($coords as $coord)
            if($coord[$x]>$rightest)
                $rightest = $coord[$x];
        return $rightest;
    }

    /**
     * 図形の上端を返却
     */
    function get_top():int
    {
        $y = 1;
        $coords = $this->shape->get_coords();
        $top = $coords[0][$y];
        foreach($coords as $coord)
            if($coord[$y]<$top)
                $top = $coord[$y];
        return $top;
    }

    /**
     * 図形の下端を返却
     */
    function get_bottom():int
    {
        $y = 1;
        $coords = $this->shape->get_coords();
        $bottom = 0;
        foreach($coords as $coord)
            if($coord[$y]>$bottom)
                $bottom = $coord[$y];
        return $bottom;
    }

    /**
     * 座標情報を反時計回り順に並び替える。<br><br>
     * <h1>アルゴリズム</h1>
     * <p>まず、凸包による「輪ゴムかけ」を行う。
     * 凸包はその図形を含む最小の矩形(座標軸に平行垂直)を内側に小さくしていくことを、
     * すべての頂点に重なるまで繰り返すことで求められる。</p>
     * <p>このとき、頂点と、凸包を求める縮小を続ける図形の重なった地点の前後関係を遂次求めることで、
     * 凸包における頂点の順番をそろえることができる。</p>
     * <p>凸包が求められたてもさらに縮小を続ければ、いづれは図形に完全一致する。</p>
     * <p>順番も求めることができる。</p>
     */ /*
    function get_sorted_coords():void
    {
        $x = $this::x;
        $y = $this::y;

        //左右上下端の平均が原点に来るよう、座標軸を平行移動した系を作る
        $delta_x = ($this->get_rightest()-$this->get_leftest())/2;
        $delta_y = ($this->get_bottom()-$this->get_top())/2;
        $that_coords = [];
        $i=0;
        foreach($this->coords as $coord)
        {
            $that_coords[$i][$x]=$coord[$x]-$delta_x;
            $that_coords[$i][$y]=$coord[$y]-$delta_y;
            $i++;
        }
        $that = new AnyShape();
        $that->set_coords($that_coords);

        //凸包を作る
        $convex_hull = new ConvexHull($that);





    }

    /**
     * 図形の面積を返却。<br>
     * 任意の図形をy軸に平行な方向で、左右端以外の頂点ごとに切り分けていくと、<br>
     * 必ず四角形と高々2つの三角形に分けることができる。<br>
     * (証明も簡単。左右端については、切断線及び図形の高々3辺により、三角形または四角形が取り出せる。<br>
     * 左右端以外は2つの切断線及び図形の2辺により、四角形が取り出せる。qed)<br>
     * 後は三角形および四角形の面積の公式を結合法則でまとめると、<br>
     * 任意の図形(多角形)における面積Sを求める次の公式を得ることができる。<br>
     * S=(1/2)|∑(j=[0,n-1],(x_j-x_(j+1))*(y_j+y_(j+1)))|<br>
     * 但し点は反時計回りに順に並んでいて、かつx_n=x_0, y_n=y_0とみなす必要がある。
     *
     * 参考:https://keisan.casio.jp/exec/system/1377138797
     * (但し四角形と三角形に分けるアイデアは17ec084独自のもの。)
     *\/
    function get_area():float
    {
        $x = $this::x; $y = $this::y;
        $this->get_sorted_coords();
        $p = $this->coords;
        //x_jは$p[j][$x]
    }

    /**
     * 重心位置を返却<br>
     * 参考1によれば、重心位置は面積の情報と積分の計算で求められるそう。<br>
     * 具体的な式は次のとおり。<br>
     * ｘG＝(1/S)∫ｘds，ｙG＝(1/S)∫ｙds<br>
     * 参考1:http://www7b.biglobe.ne.jp/~math-tota/su3/gravcal.htm
     *\/
    function get_intersection()
    {
        $x_min = $this->get_leftest();
    }
    function rotation(float $angle)
    {
        foreach($this->coords as $coord)

    }
    */

}

/**
 * 三角形
 * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
 *
 */
class Angle3 extends Shape
{
    function get_coords():array{return $this->coords;}

    function set_coords_6args(int $Ax, int $Ay, int $Bx, int $By, int $Cx, int $Cy)
    {
        $this->coords =
        [
            [$Ax, $Ay],
            [$Bx, $By],
            [$Cx, $Cy]
        ];
    }

    function set_coords(array $coords):void
    {

        $this->set_coords_6args
        ($coords[0][0], $coords[0][1], $coords[1][0], $coords[1][1],
            $coords[2][0], $coords[2][1]);
    }

    /**
     * 三角形の領域を表す数式を返却する
     */
    function get_region():string
    {
        return (new Angle3RegionReporter($this))->report();
    }

    /**
     * 引数の点が三角形の内部または淵に位置するか否か
     */
    function has_eaten(array $food_coord):bool
    {
        return (new Angle3RegionReporter($this))->has_eaten($food_coord);
    }
}

    /**
     * Angle3の内部クラスとして造りたかったもの。<br>
     * 外部からの利用は想定していない。
     * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
     *
     */
    class Angle3RegionReporter
    {
        private $As;
        private $Bs;
        private $Cs;
        private $formulas;

        function __construct(Angle3 $angle3)
        {
            $A = $angle3->get_coords()[0];
            $B = $angle3->get_coords()[1];
            $C = $angle3->get_coords()[2];
            $this->As = [$A, $B, $C];
            $this->Bs = [$B, $C, $A];
            $this->Cs = [$C, $A, $B];
        }

        /**
         * 文字列形式で領域を表す数式を返却。<br>
         */
        function report():string
        {
            $x = 0; $y = 1;
            $str = "";
            for($i = 0; $i < 3; $i++)
            {
                $A = $this->As[$i]; $B = $this->Bs[$i]; $C = $this->Cs[$i];

                if($A[$x] == $B[$x] || is_infinite(($A[$y]-$B[$y])/((float)($A[$x]-$B[$x]))))
                //ABの式がx=□の形
                    if($A[$x] < $C[$x])
                        $str .= "x>=".$A[$x]."\n";
                    else
                      $str .= "x<=".$A[$x]."\n";
                else//ABの式がy=ax+bの形
                {
                    $a = ($A[$y]-$B[$y])/((float)($A[$x]-$B[$x]));
                    $b = $A[$y]-$a*$A[$x];
                    if($C[$y]<$a*$C[$x]+$b)
                        $str .= "y<=".$a."*x+(".$b.")\n";
                    else
                      $str .= "y>=".$a."*x+(".$b.")\n";
                }
            }
            $this->formulas = explode("\n", rtrim($str, "\n"));
            return $str;
        }

        /**
         * 引数の座標が三角形により占められているか否かを判定する
         * @param [int, int] $food_coord
         */
        function has_eaten(array $food_coord):bool
       {
            $this->report();
            $x = $food_coord[0]; $y = $food_coord[1];
            foreach($this->formulas as $formula)
            {
                $formula = preg_replace("/y/", $y, $formula);
                $formula = preg_replace("/x/", $x, $formula);
                if(!eval("return $formula;"))
                    return false;
            }
            return true;
        }


    }


/**
 * 四角形
 * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
 *
 */
class Angle4 extends Shape
{
    function get_coords():array{return $this->coords;}

    function set_coords_8args(int $Ax, int $Ay, int $Bx, int $By, int $Cx, int $Cy, int $Dx, int $Dy):void
    {
        $this->coords =
        [
            [$Ax, $Ay],
            [$Bx, $By],
            [$Cx, $Cy],
            [$Dx, $Dy]
        ];
    }

    function set_coords(array $coords):void
    {

        $this->set_coords_8args
        ($coords[0][0], $coords[0][1], $coords[1][0], $coords[1][1],
         $coords[2][0], $coords[2][1], $coords[3][0], $coords[3][1]);
    }



    /**
     * 四角形の領域を表す数式を取得する。
     */
    function get_region():string
    {
        return (new Angle4RegionReporter($this))->report();
    }

    /**
     * 引数の点が四角形の内部または淵に位置するか否か
     */
    function has_eaten(array $food_coord):bool
    {
        return (new Angle4RegionReporter($this))->has_eaten($food_coord);
    }

    /**
     * <p>四角形が凹図形の場合、内角が180度を超える角が何番目であるかを0オリジンで返却。</p>
     * <p>凸図形の場合、falseを返却する。
     * 凹図形か凸図形かを判定したい場合、厳密な等号「[=!]==」が必要</p>
     * <h1>アルゴリズム</h1>
     * <p>各頂点を1度ずつ「内角が180度を超えている」と疑い、それ以外の3頂点で三角形を作る。
     * この三角形の内部に疑われる頂点があった場合、疑いどおり、その頂点は内角が180度を超えている。</p>
     */
    function get_concave()
    {
        $i = 0;
        foreach($this->coords as $suspected_coord)
        {
            $angle3 = new Angle3();
            $angle3->set_coords(array_merge(array_diff($this->coords, [$suspected_coord])));
            if($angle3->has_eaten($suspected_coord))
                return $i;
            $i++;
       }
       return false;
    }

    /**
     * 2つの三角形に分割する。凸図形の場合は引数(0～3)番目の頂点およびその対角を結ぶ線分で切り分ける。<br>
     * 凹図形の場合は、引数を無視し、分割可能な形で切り分ける
     * @return [angle3, angle3]
     * /
    function split_to_angle3s(int $idx):array
    {
        if(($i=$this->get_concave())===false)//凸図形の場合
        {
            $triangleA =
            [$this->coord[]];
        }
    }*/

}


    /**
     * Angle4の内部クラスとして造りたかったもの。<br>
     * 外部からの利用は想定していない。<br>
     * また本来は、4以上角形に対応すべきかもしれず議論が必要だが、現段階では四角形のみに対応している。
     * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
     *
     */
    class Angle4RegionReporter
    {
        private $a3rrs;
        private $formulases;

        /*function __construct(Angle4 $angle4)
        {
            if($angle4->get_concave()===false)//凸図形の場合

        }*/

        /**
         * 文字列形式で領域を表す数式を返却。<br>
         */
        function report():string
        {
            $x = 0; $y = 1;
            $str = "";
            for($i = 0; $i < 3; $i++)
            {
                $A = $this->As[$i]; $B = $this->Bs[$i]; $C = $this->Cs[$i];

                if($A[$x] == $B[$x] || is_infinite(($A[$y]-$B[$y])/((float)($A[$x]-$B[$x]))))
                    //ABの式がx=□の形
                    if($A[$x] < $C[$x])
                        $str .= "x>=".$A[$x]."\n";
                        else
                            $str .= "x<=".$A[$x]."\n";
                            else//ABの式がy=ax+bの形
                            {
                                $a = ($A[$y]-$B[$y])/((float)($A[$x]-$B[$x]));
                                $b = $A[$y]-$a*$A[$x];
                                if($C[$y]<$a*$C[$x]+$b)
                                    $str .= "y<=".$a."*x+(".$b.")\n";
                                    else
                                        $str .= "y>=".$a."*x+(".$b.")\n";
                            }
            }
            $this->formulas = explode("\n", rtrim($str, "\n"));
            return $str;
        }

        /**
         * 引数の座標が三角形により占められているか否かを判定する
         * @param [int, int] $food_coord
         */
        function has_eaten(array $food_coord):bool
        {
            $this->report();
            $x = $food_coord[0]; $y = $food_coord[1];
            foreach($this->formulas as $formula)
            {
                $formula = preg_replace("/y/", $y, $formula);
                $formula = preg_replace("/x/", $x, $formula);
                if(!eval("return $formula;"))
                    return false;
            }
            return true;
        }
    }

/**
 * 矩形
 * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
 *
 */
class Rectangle extends Angle4
{
    private $center;
    private $height;
    private $width;
    private $angle;
    private $judge=0.01;

    function get_coords():array{return $this->coords;}

    function move($dx, $dy):void
    {
        parent::move($dx, $dy);
    }

    /*
     * 隠し機能
     */
    function set_judge(float $judge):void{$this->judge = $judge;}

    /**
     * 矩形の4頂点を指定する。<br>
     * 渡された4点が実際に矩形を作りうるかの判定も行う。<br>
     * 作り得ないと判断した場合、例外を投げる。<br>
     * 判断は、「矩形の任意の二辺は垂直または平行」による。
     * {@inheritDoc}
     * @see angle4::set_coords()
     */
    function set_coords_8args(int $Ax, int $Ay, int $Bx, int $By, int $Cx, int $Cy, int $Dx, int $Dy):void
    {

        //各辺の傾き
        $AB = ($Bx-$Ax!=0)?($By-$Ay)/(float)($Bx-$Ax):INF;
        $BC = ($Cx-$Bx!=0)?($Cy-$By)/(float)($Cx-$Bx):INF;
        $CD = ($Dx-$Cx!=0)?($Dy-$Cy)/(float)($Dx-$Cx):INF;
        $DA = ($Ax-$Dx!=0)?($Ay-$Dy)/(float)($Ax-$Dx):INF;
        $this->center = [($Cx+$Ax)/2.0, ($Cy+$Ay)/2.0];
        $this->angle = atan(abs($AB))*180/M_PI;//まずは0～90°ABと中心の上下左右関係や、傾きをもとに決定
        $midpoint_AB_y = $By+$Ay/2.0; $center_y = $this->center[1]; $center_x = $this->center[0];
        if(!is_infinite($AB))
            if($midpoint_AB_y>$center_y)//91～269°
                if($AB<0)//91(89)～179(1)°
                    $this->angle = 180-$this->angle;
                else//180(0)～269°(89°)
                    $this->angle = 180+$this->angle;
            else//0～89、271～359°
                if($AB<0)//271(89)～359(1)°
                    $this->angle = 360-$this->angle;
                else//0～89°
                    ;
        else
           if($Ax<$center_x)
                $this->center = 270;
        $this->width = sqrt(($Ax-$Bx)*($Ax-$Bx)+($Ax-$Bx)*($Ax-$Bx));
        $this->height = sqrt(($Cx-$Bx)*($Cx-$Bx)+($Cx-$Bx)*($Cx-$Bx));

        if($AB!=0 && !is_infinite($AB))
        {
            $AB_vertical_to_BC = (abs((($AB*$BC)+1)/(-1))<$this->judge);
            $AB_parallel_to_CD = (abs($AB-$CD)/$AB<$this->judge);
            $AB_vertical_to_DA = (abs((($AB*$DA)+1)/(-1))<$this->judge);
        }
        else
       if($AB==0)
        {
            $AB_vertical_to_BC = is_infinite($BC);
            $AB_parallel_to_CD = abs($CD)<$this->judge;
            $AB_vertical_to_DA = is_infinite($DA);
        }
        else
       {
           $AB_vertical_to_BC = abs($BC)<$this->judge;
            $AB_parallel_to_CD = is_infinite($CD);
            $AB_vertical_to_DA = abs($DA)<$this->judge;
        }

        if($AB_vertical_to_BC&&$AB_parallel_to_CD&&$AB_vertical_to_DA)
            parent::set_coords_8args($Ax, $Ay, $Bx, $By, $Cx, $Cy, $Dx, $Dy);
        else
            throw new Exception("矩形ではありませんでした。小さな矩形に対して角度が細かすぎ、「四辺全てが直角」でないと安定された可能性があります");
    }

    /**
     * 中心座標、高さ、幅、回転角より矩形を指定する
     * @param int $center_x
     * @param int $center_y
     * @param int $height
     * @param int $width
     * @param float $angle 度数法
     */
    function set_CHWA(int $center_x, int $center_y, int $height, int $width, float $angle):void
    {
        $this->center = [$center_x, $center_y];
        $this->height = $height;
        $this->width = $width;
        $this->angle = $angle;
        $origin_centered = new Rectangle();//中心を原点に持ってきたインスタンスを作る
        $origin_centered->set_judge(1);
        $origin_centered->set_coords
        ([[-$width/2, $height/2],  //A
          [ $width/2, $height/2],  //B
          [ $width/2,-$height/2],  //C
          [-$width/2,-$height/2]]);//D
        $this->coords =
        (new ShapeController($origin_centered))
        ->rotate_origin($angle)
        ->move($center_x, $center_y)
        ->get_coords();

    }

    /**
     * 幅の取得
     */
    function get_width():int{return $this->width;}

    /**
     * 高さの取得
     */
    function get_height():int{return $this->height;}

    /**
     * 角度の取得
     */
    function get_angle():float{return $this->angle;}

    /**
     * 中心座標の取得
     */
    function get_center():array{return $this->center;}

/*
    function rotate_center(float $angle):void
    {
        $x = 0; $y = 1;

        $this->angle = $this->angle+$angle;

        $width = $this->width;
        $height = $this->height;
        $center_x = $this->center[$x];
        $center_y = $this->center[$y];
        $origin_centered = new Rectangle();//中心を原点に持ってきたインスタンスを作る
        $this->coords =
        $origin_centered
        ->set_coords
        (-$width/2, $height/2,//A
          $width/2, $height/2,//B
          $width/2,-$height/2,//C
         -$width/2,-$height/2)//D
        ->rotate_origin($angle)
        ->move($center_x, $center_y)
        ->get_coords();

    }
*/



}








/*
class AnyShape extends Shape
{
    function set_coords(array $coords):void
    {$this->coords = $coords;}
}

class ConvexHull extends AnyShape
{
    private $target;
    private $ch;

    function __construct(AnyShape $any_shape)
    {
        $delta_x = $any_shape->get_rightest();
        $delta_y = $any_shape->get_bottom();
        $this->target = $any_shape;
        $this->reducer = new ConvexHullReducer
        (
            [
                [-$delta_x, -$delta_y], //左上
                [-$delta_x,  $delta_y], //左下
                [ $delta_x, -$delta_y], //右上
                [ $delta_x,  $delta_y]  //右下
            ]
            ,
            $this->target
        );

        while($this->reducer->is_not_completed())
        {
            $this->reducer->reduce();
            $this->reducer->check();
        }

    }
    private function set_coords(){}



}

class ConvexHullReducer
{
    private $coords;//縮小の結果見つかった頂点(暫定的反時計回り順)
    private $target;
    private $points;//縮小の基準となる左右上下端

    private $delta_x;
    private $delta_y;
    function __construct(array $points, AnyShape $target)
    {
        $x = Shape::x; $y = Shape::y;
        $this->points = $points;
        $this->target = $target;
        $this->coords = [];
        $this->delta_x = $points[3][$x];
        $this->delta_y = $points[3][$y];
    }

    function reduce():void
    {
        $x = Shape::x; $y = Shape::y;
        $delta_x = $this->delta_x-1;
        $delta_y = $this->delta_y-1;
        $this->points =
        [
            [-$delta_x, -$delta_y], //左上
            [-$delta_x,  $delta_y], //左下
            [ $delta_x, -$delta_y], //右上
            [ $delta_x,  $delta_y]  //右下
        ];
        $this->delta_x = $delta_x;
        $this->delta_y = $delta_y;
    }

    function check():void//検出状況の確認
    {
        $x = Shape::x; $y = Shape::y;
        $coords_not_found = array_diff($this->target->coords, $this->coords);
        foreach($coords_not_found as $coord_not_fount)
        {
            if
           (
                abs($coord_not_fount[$x])==abs($this->delta_x)
                ||
                abs($coord_not_fount[$y])==abs($this->delta_y)
            )
                $this->set_found($coord_not_fount);//見つかったものとし、反時計回り順に気を付け$this->coordsに追加

        }
    }
    function is_not_completed():bool{return count($this->coords)!=count($this->target->coords);}
}
*/






class Angle3Tester
{
    function __construct()
    {
        $angle3 = new Angle3();
        $angle3->set_coords_6args(1, 2, 3, 4, 1, 0);
        print $angle3->get_region()."\n";
        if($angle3->has_eaten([3,2])) print "true\n";
        else print "false\n";
        $angle3->set_coords_6args(1, 2, 3, 4, 4, 0);
        print $angle3->get_region()."\n";
        if($angle3->has_eaten([3,2])) print "true\n";
        else print "false\n";
    }
}

new Angle3Tester();


?>