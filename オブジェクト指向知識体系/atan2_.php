<?php function atan2_(float $x, float $y):float{ return (($rtn=(atan2( $y , $x )/M_PI)*180)>=0)?$rtn:360+$rtn;} ?>