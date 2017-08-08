<?php
/*
 Author: Andrews54757
 License: MIT (https://github.com/ThreeLetters/SuperSQL/blob/master/LICENSE)
 Source: https://github.com/ThreeLetters/SQL-Library
 Build: v2.5.0
 Built on: 07/08/2017
*/

// lib/connector/index.php
class Response{public$result;public$affected;public$ind;public$error;public$errorData;function __construct($a,$b){$this->error=!$b;if(!$b){$this->errorData=$a->errorInfo();}else{$this->result=$a->fetchAll();$this->affected=$a->rowCount();}$this->ind=0;$a->closeCursor();}function error(){return$this->error ?$this->errorData : false;}function getData(){return$this->result;}function getAffected(){return$this->affected;}function next(){return$this->result[$this->ind++];}function reset(){$this->ind=0;}}class Connector{public$queries=array();public$db;public$log=array();public$dev=false;function __construct($c,$d,$e){$this->db=new \PDO($c,$d,$e);$this->log=array();}function query($f,$g=null){$h=$this->db->prepare($f);if($g)$i=$h->execute($g);else$i=$h->execute();if($this->dev)array_push($this->log,array($f,$g));return new Response($h,$i);}function _query($j,$k,$l,$m){if(isset($this->queries[$j."|".$m])){$n=$this->queries[$j."|".$m];$h=$n[1];$o=&$n[0];foreach($k as$p=>$q){$o[$p][0]=$q[0];}if($this->dev)array_push($this->log,array("fromcache",$j,$m,$k,$l));}else{$h=$this->db->prepare($j);$o=$k;foreach($o as$p=>&$r){$h->bindParam($p + 1,$r[0],$r[1]);}$this->queries[$j."|".$m]=array(&$o,$h);if($this->dev)array_push($this->log,array($j,$m,$k,$l));}if(count($l)==0){$i=$h->execute();return new Response($h,$i);}else{$s=array();$i=$h->execute();array_push($s,new Response($h,$i));foreach($l as$p=>$t){foreach($t as$u=>$v){$o[$u][0]=$v;}$i=$h->execute();array_push($s,new Response($h,$i));}return$s;}}function close(){$this->db=null;$this->queries=null;}function clearCache(){$this->queries=array();}}
// lib/parser/Simple.php
class SimpleParser{public static function WHERE($a,&$b,&$c){if(count($a)!=0){$b.=" WHERE ";$d=0;foreach($a as$e=>$f){if($d!=0){$b.=" AND ";}$b.="`".$e."` = ?";array_push($c,$f);$d++;}}}public static function SELECT($g,$h,$a,$i){$b="SELECT ";$c=array();$j=count($h);if($j==0){$b.="*";}else{for($d=0;$d<$j;$d++){if($d!=0){$b.=", ";}$b.="`".$h[$d]."`";}}$b.="FROM `".$g."`";self::WHERE($a,$b);$b.=" ".$i;return array($b,$c);}public static function INSERT($g,$k){$b="INSERT INTO `".$g."` (";$l=") VALUES (";$c=array();$d=0;foreach($k as$e=>$f){if($d!=0){$b.=", ";$l.=", ";}$b.="`".$e."`";$l.="?";array_push($c,$f);$d++;}$b.=$l;return array($b,$c);}public static function UPDATE($g,$k,$a){$b="UPDATE `".$g."` SET ";$c=array();$d=0;foreach($k as$e=>$f){if($d!=0){$b.=", ";}$b.="`".$e."` = ?";array_push($c,$f);$d++;}self::WHERE($a,$b,$c);return array($b,$c);}public static function DELETE($g,$a){$b="DELETE FROM `".$g."`";$c=array();self::WHERE($a,$b,$c);return array($b,$c);}}
// lib/parser/Advanced.php
class AdvancedParser{private static function parseArg(&$a){if(substr($a,0,1)=="[" && substr($a,3,1)=="]"){$b=substr($a,1,2);$a=substr($a,4);return$b;}else{return false;}}private static function append(&$c,$d,$e){if(gettype($d)=="array"){$f=count($d);for($g=1;$g<$f;$g++){if(!isset($c[$g]))$c[$g]=array();$c[$g][$e]=$d[$g];}}}private static function append2(&$h,$i,$j){function stripArgs(&$k){if(substr($k,-1)=="]"){$l=strrpos($k,"[",-1);$k=substr($k,0,$l);}$l=strrpos($k,"]",-1);if($l!==false)$k=substr($k,$l + 1);}function recurse(&$m,$d,$i,$n){foreach($d as$g=>$o){if(gettype($o)=="array"){$p=substr($g,0,4);stripArgs($g);if($p!="[||]" &&$p!="[&&]"){if(isset($i[$g."#".$n."*"]))$q=$i[$g."#".$n."*"];else$q=$i[$g."*"];foreach($o as$r=>$s){$m[$q +$r]=$s;}}else{recurse($m,$o,$i,$n."/".$g);}}else{stripArgs($g);if(isset($i[$g."#".$n]))$q=$i[$g."#".$n];else$q=$i[$g];$m[$q]=$o;}}}$f=count($j);for($k=1;$k<$f;$k++){$d=$j[$k];if(!isset($h[$k]))$h[$k]=array();recurse($h[$k],$d,$i,"");}}private static function quote($a){$a=explode(".",$a);$b="";for($r=0;$r<count($a);$r++){if($r!=0)$b.=".";$b.="`".$a[$r]."`";}return$b;}private static function table($t){if(gettype($t)=="array"){$u="";for($r=0;$r<count($t);$r++){$v=self::getType($t[i]);if($r!=0)$u.=", ";$u.=self::quote($t[$r]);if($v)$u.=" AS ".self::quote($v);}return$u;}else{return self::quote($t);}}private static function value($w,$x,&$y){$z=strtolower($w);if(!$z)$z=strtolower(gettype($x));$w=\PDO::PARAM_INT;if($z=="boolean" ||$z=="bool"){$w=\PDO::PARAM_BOOL;$x=$x ? "1" : "0";$y.="b";}else if($z=="integer" ||$z=="int"){$y.="i";}else if($z=="string" ||$z=="str"){$w=\PDO::PARAM_STR;$y.="s";}else if($z=="double" ||$z=="doub"){$x=(int)$x;$y.="i";}else if($z=="resource" ||$z=="lob"){$w=\PDO::PARAM_LOB;$y.="l";}else if($z=="null"){$w=\PDO::PARAM_NULL;$x=null;$y.="n";}return array($x,$w);}private static function getType(&$a){if(substr($a,-1)=="]"){$aa=strpos($a,"[");if($aa===false){return "";}$b=substr($a,$aa + 1,-1);$a=substr($a,0,$aa);return$b;}else return "";}private static function conditions($j,&$ba=false,&$ca=false,&$y="",&$e=0){$da=function(&$da,$j,&$ca,&$e,&$ba,&$y,$ea=" AND ",$fa=" = ",$ga=""){$ha=0;$u="";foreach($j as$k=>$d){if(substr($k,0,1)==="#"){$ia=true;$k=substr($k,1);}else{$ia=false;}$ja=self::parseArg($k);$ka=$ja ? self::parseArg($k): false;$la=gettype($d);$ma=!isset($d[0]);$na=$ea;$oa=$fa;switch($ja){case "||":$ja=$ka;$na=" OR ";break;case "&&":$ja=$ka;$na=" AND ";break;}switch($ja){case ">>":$oa=" > ";break;case "<<":$oa=" < ";break;case ">=":$oa=" >= ";break;case "<=":$oa=" <= ";break;case "!=":$oa=" != ";break;default: if(!$ma)$oa=" = ";break;}if($ha!=0)$u.=$ea;if($la=="array"){if($ma){$u.="(".$da($da,$d,$ca,$e,$ba,$na,$oa,$ga."/".$k).")";}else{$w=self::getType($k);if($ca!==false &&!$ia){$ca[$k."*"]=$e;$ca[$k."#".$ga."*"]=$e++;}foreach($x as$g=>$o){if($g!=0)$u.=$na;$u.="`".$k."`".$oa;$e++;if($ia){$u.=$o;}else if($ba!==false){$u.="?";array_push($ba,self::value($w,$o,$y));}else{if(gettype($o)=="integer"){$u.=$o;}else{$u.=self::quote($o);}}}}}else{if($ia){$u.=$d;}else{if($ba!==false){$u.="`".$k."`".$oa."?";array_push($ba,self::value(self::getType($k),$d,$y));}else{$u.=self::quote($k).$oa;if(gettype($d)=="integer"){$u.=$d;}else{$u.=self::quote($d);}}if($ca!==false){$ca[$k]=$e;$ca[$k."#".$ga]=$e++;}}}return$u;}$ha++;};return$da($da,$j,$ca,$e,$ba,$y);}static function SELECT($t,$pa,$qa,$ea,$ra){$u="SELECT ";$f=count($pa);$ba=array();$h=array();if($f==0){$u.="*";}else{$r=0;$sa=0;$ta="";if($pa[0]=="DISTINCT"){$r=1;$sa=1;$u.="DISTINCT ";}else if(substr($pa[0],0,11)=="INSERT INTO"){$r=1;$sa=1;$u=$pa[0]." ".$u;}else if(substr($pa[0],0,4)=="INTO"){$r=1;$sa=1;$ta=" ".$pa[0]." ";}if($f>$sa){for(;$r<$f;$r++){$v=self::getType($pa[$r]);if($r>$sa){$u.=", ";}$u.=self::quote($pa[$r]);if($v)$u.=" AS `".$v."`";}}else$u.="*";$u.=$ta;}$u.=" FROM ".self::table($t);if($ea){foreach($ea as$k=>$d){if(substr($k,0,1)==="#"){$ia=true;$k=substr($k,1);}else{$ia=false;}$ja=self::parseArg($k);switch($ja){case "<<":$u.=" RIGHT JOIN ";break;case ">>":$u.=" LEFT JOIN ";break;case "<>":$u.=" FULL JOIN ";break;default:$u.=" JOIN ";break;}$u.=self::quote($k)." ON ";if($ia){$u.="val";}else{$u.=self::conditions($d);}}}$y="";if(count($qa)!=0){$u.=" WHERE ";$e=array();if(isset($qa[0])){$u.=self::conditions($qa[0],$ba,$e,$y);self::append2($h,$e,$qa);}else{$u.=self::conditions($qa,$ba,$e,$y);}}if($ra)$u.=" LIMIT ".$ra;return array($u,$ba,$h,$y);}static function INSERT($t,$ua){$u="INSERT INTO ".self::table($t)." (";$ba=array();$h=array();$y="";$va="";$r=0;$l=0;if(isset($ua[0])){$i=array();foreach($ua[0]as$k=>$d){if(substr($k,0,1)==="#"){$ia=true;$k=substr($k,1);}else{$ia=false;}if($l!=0){$u.=", ";$va.=", ";}$w=self::getType($k);$u.="`".$k."`";if($ia){$va.=$d;}else{$va.="?";array_push($ba,self::value($w,$d,$y));$i[$k]=$r++;}$l++;}self::append2($h,$i,$ua);}else{foreach($ua as$k=>$d){if(substr($k,0,1)==="#"){$ia=true;$k=substr($k,1);}else{$ia=false;}if($l!=0){$u.=", ";$va.=", ";}$w=self::getType($k);$u.="`".$k."`";if($ia){$va.=$d;}else{array_push($ba,self::value($w,$d,$y));$va.="?";self::append($h,$d,$r++);}$l++;}}$u.=") VALUES (".$va.")";return array($u,$ba,$h,$y);}static function UPDATE($t,$ua,$qa){$u="UPDATE ".self::table($t)." SET ";$ba=array();$h=array();$y="";$r=0;$l=0;$i=array();$wa=isset($ua[0]);$j=$wa ?$ua[0]:$ua;foreach($j as$k=>$d){if(substr($k,0,1)==="#"){$ia=true;$k=substr($k,1);}else{$ia=false;}if($l!=0){$u.=", ";}if($ia){$u.="`".$k."` = ".$d;}else{$ja=self::parseArg($k);$u.="`".$k."` = ";switch($ja){case "+=":$u.="`".$k."` + ?";break;case "-=":$u.="`".$k."` - ?";break;case "/=":$u.="`".$k."` / ?";break;case "*=":$u.="`".$k."` * ?";break;default:$u.="?";break;}$w=self::getType($k);array_push($ba,self::value($w,$d,$y));if($wa){$i[$k]=$r++;}else{self::append($h,$d,$r++);}}$l++;}if($wa)self::append2($h,$i,$ua);if(count($qa)!=0){$u.=" WHERE ";$e=array();if(isset($qa[0])){$u.=self::conditions($qa[0],$ba,$e,$y,$r);self::append2($h,$e,$qa);}else{$u.=self::conditions($qa,$ba,$e,$y,$r);}}return array($u,$ba,$h,$y);}static function DELETE($t,$qa){$u="DELETE FROM ".self::table($t);$ba=array();$h=array();$y="";if(count($qa)!=0){$u.=" WHERE ";$e=array();if(isset($qa[0])){$u.=self::conditions($qa[0],$ba,$e,$y);self::append2($h,$e,$qa);}else{$u.=self::conditions($qa,$ba,$e,$y);}}return array($u,$ba,$h,$y);}}
// index.php
class SuperSQL{public$connector;function __construct($a,$b,$c){$this->connector=new Connector($a,$b,$c);}function SELECT($d,$e=array(),$f=array(),$g=null,$h=false){if(gettype($g)=="integer"){$h=$g;$g=null;}$i=AdvancedParser::SELECT($d,$e,$f,$g,$h);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function INSERT($d,$j){$i=AdvancedParser::INSERT($d,$j);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function UPDATE($d,$j,$f=array()){$i=AdvancedParser::UPDATE($d,$j,$f);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function DELETE($d,$f=array()){$i=AdvancedParser::DELETE($d,$f);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function sSELECT($d,$e=array(),$f=array(),$k=""){$i=SimpleParser::SELECT($d,$e,$f,$k);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function sINSERT($d,$j){$i=SimpleParser::INSERT($d,$j);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function sUPDATE($d,$j,$f=array()){$i=SimpleParser::UPDATE($d,$j,$f);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function sDELETE($d,$f=array()){$i=SimpleParser::DELETE($d,$f);return$this->connector->_query($i[0],$i[1],$i[2],$i[3]);}function query($l,$m=null){return$this->connector->query($l,$m);}function close(){$this->connector->close();}function dev(){$this->connector->dev=true;}function getLog(){return$this->connector->log;}function clearCache(){$this->connector->clearCache();}}
?>