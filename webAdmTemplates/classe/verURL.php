<?php 
/***************************
**classe de inclusão de páginas
**METODO trocarURL($url)
**ESTA CLASSE FAZ A TROCA DE PÁGINAS NA INDEX
**VERSÃO 1.0 CURSO DE PHP VOLUME II
***************************/
class verURL {
      function trocarURL($url){
	   if(empty($url)){
                  $url = "paginas/home.php";

                    }else{
					$url = "paginas/$url.php";
                 }
			   include_once($url);
	         }
          }
?>
