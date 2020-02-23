function build()
{
    var p = new Printer();
    p.classname = get("classname");
    p.superclasses = get("is-a");
    p.comment = get("comment");
    p.links = getn("methodlink")["values"];
    p.csv_records = getn("fieldcsv")["values"];
  //var supermembers = new Ancestorsmembers().get();
  //p.links = method_override(p.links, supermembers["methodlink"]);
  //p.csv_records = field_override(p.csv_records, supermembers["fieldcsv"]);
    p.print();

}


function get(id){if (document.getElementById(id) != null) return document.getElementById(id).value; return null;}
function getn(name)//name属性からidsとvaluesの連想配列を生成
{
    var values = [], ids = [];
    for (i = 0; i < document.getElementsByName(name).length; i++)
    {
        values[i] = document.getElementsByName(name)[i].value;
        ids[i] = document.getElementsByName(name)[i].id;
    }
    
    return {ids : ids, values : values};
}

class Printer
{
/* javascriptは本来オブジェクト指向言語ではないため、classは糖衣構文に過ぎない。
https://www.sejuku.net/blog/49551 を参考にすればカプセル化も可能であるが、
他人に使わせる予定もないので、すべてのメンバをpublicのままでよいと判断した。 */

    constructor()
    {
        this.classname;
        this.superclasses;
        this.comment;
        this.links; this.formal_links;
        this.csv_records;

    }

    print()
    {
        this.superclasses = new Superclasses(this.superclasses);
        if(this.links.length == 0 || ( this.links.length == 1 && this.links[0].match(/^[ \t\r\n]*$/))) 
            this.formal_links = "(なし)";
        else
        {
            this.formal_links="<ul>";
            for(i = 0; i < this.links.length; i++)
            {
                var VO = this.links[i];
                var link = new Link(VO);
                this.formal_links += "<li>" + link.method + "(<a href='" + link.addr + "'>" + link.arg + "</a>)</li>";
            }
            this.formal_links += "</ul>";
        }
        var csv = new Csv(this.csv_records);
        document.write(`<html>
<head>
<title>`+this.classname+` - Hirata Knowledge System</title>
</head>
<body>
<h1><small>名詞: </small>`+this.classname+`</h1>
<h2>親クラス</h2>
`+this.superclasses.put_link()+`<hr>
`+this.comment+`<hr>
<h2>`+this.classname+`が主語となるときに述語となり得る動詞一覧</h2>
`+this.formal_links+`
<hr>
<h2>`+this.classname+`の属性表</h2>
`+csv.html_code+`
</body>
</html>`);
    }



}

class Link
{

    constructor(VO)
    {
        var bracket_regex = /^[^("#]+([("#]).+$/;
        this.otype = VO.match(bracket_regex)[1];
        this.method = this.get_unlinked(VO);
        this.addr   = this.get_link_addr(VO);
        this.arg    = this.get_linked(VO);
    }

    get_link_addr(VO)
    {
        if (this.otype == "\"") //文字列
            return "../文字列.html";
        if (this.otype == "(") //別のクラス
        {
            var inner_regex = /^[^("#]+\((.*)\)[ \t\r\n]*$/;
            var inner = VO.match(inner_regex)[1];
            if (inner != "") 
                return inner+".html";
            return "";
        }
        if (this.otype == "#") //形容詞の場合
            return "";
    }

    get_linked(VO)
    {
        var inner = "";
        if (this.otype == "\"") //文字列
            var inner_regex = /^[^("#]+"(.*)"$/;
        else if (this.otype == "(") //別のクラス
            var inner_regex = /^[^("#]+\((.*)\)[ \t\r\n]*$/;
        inner = VO.match(inner_regex)[1];
        return inner;
    }

    get_unlinked(VO)
    {
        if (this.otype == "#") return VO;
        var outer_regex = /^([^("#]+)[("#].*$/;
        var outer = VO.match(outer_regex)[1];
        return outer;
    }
}

class Csv
{
    constructor(csv_records)
    {
        var ct = new Csv_table(csv_records);
        if(csv_records.length == 0 || csv_records[0].match(/^[ \t\r\n]*$/)) this.html_code = "(なし)";
        else
        {
            this.html_code = "<table border='1'>";
            this.html_code += ct.get_header();
            this.html_code += ct.get_contents();
            this.html_code += "</table>";
        }
    }
}

class Csv_table
{
    constructor(csv_records){ this.csv_records = csv_records;}

    get_header(){ return "<tr><th>" + this.csv_records[0].replace(",", "</th><th>") + "</th></tr>";}
    
    get_contents()
    {
        var str="";
        for(i=1; i<this.csv_records.length; i++)
            str+="<tr><td>" + this.csv_records[i].replace(",", "</td><td>") + "</td></tr>"; 
        return str;
    }

}

class Superclasses
{
    constructor(str)
    {
        this.is_null = (str == null);
        str = this.is_null ? "" : str;
        this.strs = eval("['"+str.replace(/[ \t\r\n]*,[ \t\r\n]*/g, "','")+"']");//配列になる
    }
    
    put_link()
    {
        var rtn = "";
        if(this.is_null) return "(なし)";
        for(i = 0; i < this.strs.length-1; i++)
            rtn += "<a href='" + this.strs[i] + ".html'>" + this.strs[i] + "</a>, ";
        rtn += "<a href='" + this.strs[i] + ".html'>" + this.strs[i] + "</a>";
        return rtn;
    }
}