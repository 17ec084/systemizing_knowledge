<?php
require 'AAAReader.php';
require 'Shape.php';
require 'atan2_.php';
/**
 * html5のCanvasのクラス
 * @author <a href=http://github.com/17ec084>Tomotaka Hirata(17ec084)</a>
 *
 */
class Canvas
{
    private $width;
    private $height;
    private $code;
    private $script;

    function __construct(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
        $this->code =
        "<!DOCTYPE HTML>
        <html lang=\"ja\">
        <head>
        <!-- 17ec084 safe_title --><title></title><!-- 17ec084 end safe_title -->
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
        </head>
        <body>
        <canvas id=\"cv\" width=\"$width\" height=\"$height\" style=\"border: 1px solid #333333;\"></canvas>
        <!-- 17ec084 safe_script --><script></script><!-- 17ec084 end safe_script -->
        </body>
        </html>";
        $this->script = "";

    }

    function set_title(string $title):Canvas
    {
        $this->code =
        preg_replace
        (
            "/<!-- 17ec084 safe_title -->(?:.|[ \t\r\n])*<!-- 17ec084 end safe_title -->/U" ,
            "<!-- 17ec084 safe_title --><title>$title</title><!-- 17ec084 end safe_title -->" ,
            $this->code
        );
        //修飾子Uにより貪欲さ反転
        return $this;
    }

    /**
     * javascriptによるcanvas操作を追加
     * @param string $str
     */
    function add_script(string $str):Canvas{$this->script = $this->script.$str."\n"; return $this;}

    /**
     * 長方形の追加。引数は連想配列として。<br>
     * (https://qiita.com/17ec084/items/559080ef25daed7730f1に寄せられたコメント)
     * @param {center(int配列。キー0がx座標、キー1がy座標) height(int) width(int) (angle=0.0)?}|{fourCoords} (必須) 座標を決定する
     * @param {(border_color(枠線の色。RGB値。000000～FFFFFF))? border_width? (back_color)?} (任意)
     * @param {text(長方形に含める文字列を指定する) text_size text_color?}(任意)
     */
    function create_rectangle(array $AAA):Canvas
    {
        $ar = new AAAReader($AAA);
        $x = 0; $y = 1;

        if(!$ar->g_has("center") && !$ar->g_has("fourCoords"))
            new Exception();
        if($ar->g_has("center"))
        {
            $center = $ar->g("center");
            $center_x = $center[0];
            $center_y = $center[1];
            $height = $ar->g("height");
            $width = $ar->g("width");
            $angle = $ar->g_has("angle")?$ar->g("angle"):0.0;
            $rectangle = new Rectangle();
            $rectangle->set_CHWA($center_x, $center_y, $height, $width, $angle);
            $coords = $rectangle->get_coords();
        }
        else
       {
            $coords = $ar->g('coords');
        }
        $this
        ->add_script('ctx = document.getElementById("cv").getContext("2d");')
        ->add_script('ctx.beginPath();')
        ->add_script('ctx.moveTo('.$coords[0][$x].', '.$coords[0][$y].');')
        ->add_script('ctx.lineTo('.$coords[1][$x].', '.$coords[1][$y].');')
        ->add_script('ctx.lineTo('.$coords[2][$x].', '.$coords[2][$y].');')
        ->add_script('ctx.lineTo('.$coords[3][$x].', '.$coords[3][$y].');')
        ->add_script('ctx.closePath();');
        if($ar->g_has("border_color"))
            $this->add_script('ctx.strokeStyle = "#'.$ar->g("border_color").'";');
        if($ar->g_has("border_width"))
            $this->add_script('ctx.lineWidth = '.$ar->g("border_width").';');
        $this->add_script('ctx.stroke();');


        if($ar->g_has("back_color"))
            $this
            ->add_script('ctx.fillStyle = "#'.$ar->g("back_color").'";')
            ->add_script('ctx.fill();');
        if($ar->g_has("text"))
        {
            $this
            ->add_script('ctx = document.getElementById("cv").getContext("2d");')
            ->add_script('ctx.font = "'.$ar->g("text_size").'px \'ＭＳ ゴシック\'";')
            ->add_script('ctx.textAlign = "center";')
            ->add_script('ctx.textBaseline = "middle";');
            if($ar->g_has("text_color"))
                $this->add_script('ctx.fillStyle = "#'.$ar->g("text_color").'";');
            $this->add_script('ctx.fillText("'.$ar->g("text").'", '.(int)(($coords[0][$x]+$coords[2][$x])/2).', '.(int)(($coords[0][$y]+$coords[2][$y])/2).');');
        }

        return $this->add_script('ctx.restore();'."\n".'ctx.save()');//初期化忘れずに.また一度restoreするとsaveの内容が消えるので、すぐsaveも実行。
    }

    /**
     * 円の追加。
     * @param {center([x, y]) radius border_color? back_color? {text text_size text_color?}?}
     *
     */
    function create_circle(array $AAA):Canvas
    {
        $ar = new AAAReader($AAA);
        $this
        ->add_script('ctx = document.getElementById("cv").getContext("2d");')
        ->add_script('ctx.beginPath();')
        ->add_script('ctx.arc('.$ar->g("center")[0].', '.$ar->g("center")[1].', '.$ar->g("radius").', 0, 2*Math.PI, false);');
        if($ar->g_has("border_color"))
            $this->add_script('ctx.strokeStyle = "#'.$ar->g("border_color").'";');
        if($ar->g_has("border_width"))
            $this->add_script('ctx.lineWidth = '.$ar->g("border_width").';');
        $this->add_script('ctx.stroke();');
        if($ar->g_has("back_color"))
            $this
            ->add_script('ctx.fillStyle = "#'.$ar->g("back_color").'";')
            ->add_script('ctx.fill();');
        if($ar->g_has("text"))
        {
            $this
            ->add_script('ctx = document.getElementById("cv").getContext("2d");')
            ->add_script('ctx.font = "'.$ar->g("text_size").'px \'ＭＳ ゴシック\'";')
            ->add_script('ctx.textAlign = "center";')
            ->add_script('ctx.textBaseline = "middle";');
            if($ar->g_has("text_color"))
                $this->add_script('ctx.fillStyle = "#'.$ar->g("text_color").'";');
            $this->add_script('ctx.fillText("'.$ar->g("text").'", '.$ar->g("center")[0].', '.$ar->g("center")[1].');');
        }
        return $this->add_script('ctx.restore();'."\n".'ctx.save()');
    }

    /**
     * 矢印の追加
     * @param {type(矢印の種類 int 0～5) size(矢の終点マークの大きさ) from(開始座標) to(終了座標) color? width?}
     */
    function create_arrow(array $AAA):Canvas
    {
        $ar = new AAAReader($AAA);
        $type = $ar->g("type");
        if($type>=6) throw new Exception("typeが異常です");

        $x = 0; $y = 1;

        $start = $ar->g("from"); $end = $ar->g("to"); $size = $ar->g("size");
        $angle = atan2_($end[$x]-$start[$x], $end[$y]-$start[$y]);

        $abs = sqrt(($end[$x]-$start[$x])**2+($end[$y]-$start[$y])**2);
        //まず下向きの矢印「↓」を生成。ただし始点を原点,Aとする。
        //終点をBとする。
        $A = [0, 0];
        $B = [0, $abs];

        //タイプ0の矢をBC、BDとする。タイプ1の矢は、タイプ0にCD(中点E)を加えたもの。
        //タイプ2の矢はCDの線分を描画しない。BE＝EFなFを考え、CF,DFを描画。
        $C = [ $size, $abs-  $size];
        $D = [-$size, $abs-  $size];
        $E = [     0, $abs-  $size];
        $F = [     0, $abs-2*$size];


        //ShapeControllerクラスに渡して傾ける＋平行移動
        $arrow = new Shape();
        $arrow->set_coords([$A, $B, $C, $D, $E, $F]);
        $controller = new ShapeController($arrow);
        $arrow = $controller->rotate_origin(90-$angle)->move($start[$x], $start[$y])->get_coords();
        $A = $arrow[0]; $B = $arrow[1]; $C = $arrow[2]; $D = $arrow[3]; $E = $arrow[4]; $F = $arrow[5];

        $this
        ->add_script('ctx = document.getElementById("cv").getContext("2d");');
        if($ar->g_has("width"))
            $this->add_script('ctx.lineWidth = '.$ar->g("width").';');
        if($ar->g_has("color"))
            $this
            ->add_script('ctx.strokeStyle = "#'.$ar->g("color").'";')
            ->add_script('ctx.fillStyle = "#'.$ar->g("color").'";');

        $this
        ->add_script('ctx.beginPath();')
        ->add_script('ctx.moveTo('.$A[$x].', '.$A[$y].');');

        $stroke = 'ctx.stroke();';
        $beginPath = 'ctx.beginPath();';

        if($type%3 == 0)
            $this
            ->add_script('ctx.lineTo('.$B[$x].', '.$B[$y].');')
            ->add_script($stroke);
        if($type%3 == 1)
            $this
            ->add_script('ctx.lineTo('.$E[$x].', '.$E[$y].');')
            ->add_script($stroke);
        if($type%3 == 2)
            $this
            ->add_script('ctx.lineTo('.$F[$x].', '.$F[$y].');')
            ->add_script($stroke);

        $this
        ->add_script($beginPath)
        ->add_script('ctx.moveTo('.$C[$x].', '.$C[$y].');')
        ->add_script('ctx.lineTo('.$B[$x].', '.$B[$y].');')
        ->add_script('ctx.lineTo('.$D[$x].', '.$D[$y].');');

        if($type%3 == 0)
            return
           $this
            ->add_script($stroke)
            ->add_script('ctx.restore();'."\n".'ctx.save()');
        if($type%3 == 1)
            $this->add_script('ctx.closePath();');
        if($type%3 == 2)
            $this
            ->add_script('ctx.lineTo('.$F[$x].', '.$F[$y].');')
            ->add_script('ctx.closePath();');

        $this->add_script($stroke);

        if($type >= 3)
            $this->add_script('ctx.fill();');

        return $this->add_script('ctx.restore();'."\n".'ctx.save()');
    }

    /**
     * Canvasを描画するhtml5コードを文字列として返却する
     */
    function get_code():string
    {
        return
       preg_replace
        (
            "/<!-- 17ec084 safe_script -->(?:.|[ \t\r\n])*<!-- 17ec084 end safe_script -->/U" ,
            "<!-- 17ec084 safe_script --><script>
ctx = document.getElementById(\"cv\").getContext(\"2d\");
ctx.strokeStyle = \"#000000\";
ctx.save();
$this->script</script><!-- 17ec084 end safe_script -->" ,
            $this->code
        );
    }

}

//以下テストクラス

class CanvasTester
{
    function __construct()
    {
        $cv = new Canvas(5000, 5000);
        print $cv
        ->set_title("テスト")
        ->create_rectangle
        ([
            'center'=>[2500, 3000],
            'height'=>1000,
            'width'=>2000,
            'angle'=>1,
            'back_color'=>'ffffcc',
            'border_width'=> 100
        ])
        ->create_rectangle
        ([
            'center'=>[2500, 3000],
            'height'=>1000,
            'width'=>2000,
            'angle'=>45,
            'back_color'=>'ccffff',
            'border_color'=>'ff0000',
            'text'=>'17ec084',
            'text_size'=>100,
            'text_color'=>'ffccff'
        ])
        ->create_circle
        ([
            'center'=>[2000, 3500],
            'radius'=>500,
            'back_color'=>'aa0000',
            'text'=>'sample',
            'text_color'=>'ffffff',
            'text_size'=>150
        ])
        ->create_arrow
        ([
            'type'=>0,
            'size'=>100,
            'color'=>'FF0000',
            'width'=>50,
            'from'=>[2500, 2500],
            'to'=>[3000,2500]
        ])
        ->create_arrow
        ([
            'type'=>1,
            'size'=>150,
            'color'=>'00FF00',
            'width'=>10,
            'from'=>[3000, 2500],
            'to'=>[3500,2000]
        ])
        ->create_arrow
        ([
            'type'=>2,
            'size'=>50,
            'color'=>'0000FF',
            'width'=>30,
            'from'=>[3500, 2000],
            'to'=>[3000,1500]
        ])
        ->create_arrow
        ([
            'type'=>3,
            'size'=>300,
            'color'=>'00FFFF',
            'width'=>100,
            'from'=>[3000, 1500],
            'to'=>[2500,1500]
        ])
        ->create_arrow
        ([
            'type'=>4,
            'size'=>100,
            'color'=>'FF00FF',
            'width'=>30,
            'from'=>[2500, 1500],
            'to'=>[2000,2000]
        ])
        ->create_arrow
        ([
            'type'=>5,
            'size'=>100,
            'color'=>'FFFF00',
            'width'=>30,
            'from'=>[2000, 2000],
            'to'=>[2500,2500]
        ])
        ->get_code();
        file_put_contents("test.html", $cv->get_code());
    }
}

new CanvasTester();


?>