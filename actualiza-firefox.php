<?php
/*
Plugin Name: Actualiza Firefox
Plugin URI: https://github.com/mozillahispano/wp-fxupdate
Description: Alerta a los usarios que est&aacute;n utilizando una versi&oacute;n desactualizada de Firefox y m&aacute;s.
Version: 0.4
Author: Yunier Sosa V&aacute;zquez
Author URI: http://firefoxmania.uci.cu
Contributor: Erick Le�n Bolinaga, Roberto Nu�ez
*/

/* Copyright 2012-2013  Yunier Sosa V�zquez (email: yjsosa@estudiantes.uci.cu)
	
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

For developed of the plugin has been used the libraries ChooseLocale and PropertiesParser of Pascal Chevrel, Mozilla <pascal@mozilla.com>, Mozilla
ChooseLocale v0.6 (2012-12-09)
PropertiesParser v0.1 (2012-12-05)
*/

if(!isset($_COOKIE['actualiza_firefox'])){
    $_COOKIE['actualiza_firefox'];
    setcookie('actualiza_firefox', 'on', time()+60*60*24*120, '/', $_SERVER['SERVER_NAME']); //Creando la cookie con 120 dias de duracion para el dominio donde esta instalado Actualiza Firefox
}

// Pre-2.6 compatibility
if(!defined('WP_CONTENT_URL'))
	define('WP_CONTENT_URL', get_option('siteurl').'/wp-content');
if(!defined('WP_CONTENT_DIR'))
	define('WP_CONTENT_DIR', ABSPATH.'wp-content');
if(!defined('WP_PLUGIN_URL'))
	define('WP_PLUGIN_URL', WP_CONTENT_URL.'/plugins');
if(!defined('WP_PLUGIN_DIR'))
	define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins');

add_action('activate_actualiza-firefox/actualiza-firefox.php', 'actualiza_firefox_install'); 
add_action('wp_footer', 'actualiza_firefox'); //Incrustando la funci�n en el pie de pagina del sitio
add_action('plugins_loaded', 'actualiza_firefox_textdomain'); //Para la localizacion
register_uninstall_hook(__FILE__, 'actualiza_firefox_clean_uninstall'); //Desintalacion limpia
wp_register_style('af_style', WP_PLUGIN_URL . '/actualiza-firefox/style.css'); //Los estilos
wp_enqueue_style('af_style'); //Los estilos

function actualiza_firefox_install(){ //Activando el plugin por primera vez
	$af_firefox=get_option('af_firefox'); //Obtener la opcion (si existe)
	if(!$af_firefox){
		update_option('af_firefox', '19.0');
		update_option('af_firefox_esr', '17.0');
		update_option('af_url', 'http://mozilla.org/firefox');
	}	}
		
//Adicionando la p�gina de configuracion al menu de WP
add_action('admin_menu', 'adicionar_pagina_opcion'); 
function adicionar_pagina_opcion(){
	add_options_page('Actualiza Firefox', 'Actualiza Firefox', 'manage_options','actualiza-firefox/actualiza-firefox-options.php');
}

//Internacionalizacion para WP
function actualiza_firefox_textdomain(){
	load_plugin_textdomain('actualiza-firefox', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
}

//Localizacion para el idioma del usuario
require_once ('lib/ChooseLocale.class.php');
require_once ('lib/PropertiesParser.class.php');
$af_locale=new tinyL10n\ChooseLocale(array('ar', 'es', 'en', 'el', 'ff', 'fr', 'ga', 'id', 'sq', 'pt', 'lij', 'zh-TW', 'ms', 'bn-IN', 'nl', 'bn-BD'));

$af_locale->setDefaultLocale('en');
$af_locale->mapLonglocales = true;

// Bypass locale detection by $_SERVER
$af_lang=$_SERVER['HTTP_ACCEPT_LANGUAGE'];
$af_locale->setCompatibleLocale($lang);
$af_lang=$af_locale->getDetectedLocale();

$af_lang_file=tinyL10n\PropertiesParser::propertiesToArray(__DIR__ . '/lang/' . $af_lang . '.properties');

//Borrando las opciones del plugin cuando se elimine desde la administracion de WP
function actualiza_firefox_clean_uninstall(){
	$option=get_option('af_firefox');
	if($option){// Borrando todas las opciones del plugin
		delete_option('af_firefox');
		delete_option('af_firefox_esr');
		delete_option('af_url');
	}	}

//Adicionando un vinculo hacia la configuracion del plugin
$plugin=plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'af_mi_vinculo_al_plugin'); 
function af_mi_vinculo_al_plugin($link){ 
	// Adicionando un vinculo hacia la configuracion del plugin
	$af_con_link='<a href="options-general.php?page=actualiza-firefox/actualiza-firefox-options.php">'. __('Settings', 'actualiza-firefox').'</a>'; 
	array_unshift($link, $af_con_link); 
	return $link; 
}

//Plugin Options
$af_firefox=get_option('af_firefox');
$af_firefox_esr=get_option('af_firefox_esr');
$af_url=get_option('af_url');

//Obtener la version de Firefox del usuario
function af_detectar_version_firefox($title){
	global $useragent;
	$start=$title;
	//Grab the browser version if its present
	preg_match('/'.$start.'[\ |\/]?([.0-9a-zA-Z]+)/i', $useragent, $regmatch);
	$version=$regmatch[1];

	return $version;   }

//Detectar navegador web
function af_detectar_navegador(){
	global $useragent;
	if(preg_match('/Firefox/i', $useragent))
		$title="Firefox";
	else
		$title="Otro";
			
	return $title; }

//Funci�n para comparar las versiones de Firefox
function af_comparar_versiones($ver){
	global $af_firefox, $af_firefox_esr, $ver;
	$obsoleta=false;  //Vamos a esperar que el usuario siempre est� actualizado
	$af_estable=explode('.', $af_firefox); //Dividiendo el n�mero de versi�n en arreglos para poder compararlos
	$af_esr=explode('.', $af_firefox_esr);
	$af_usuario=explode('.', $ver);
	/*Analizamos que versi�n usa el usuario. Si la versi�n estable es mayor que la del usuario y el usuario
	no est� usando una versi�n ESR entonces obsoleta=true */
    if ((((int)$af_estable[0])>((int)$af_usuario[0])) && (((int)$af_esr[0])!==((int)$af_usuario[0])))
		$obsoleta=true;
	/* C�digo obsoleto posterior a Firefox 16 pues ya no se env�a la cadena Firefox 16.*.* s�lo 16.0 (se necesita ayuda para obtener la versi�n real de Firefox)
	//Comprobamos si es un Firefox Estable
	elseif((((int)$af_estable[0])==((int)$af_usuario[0]))){
	   //Analizamos el tama�o de los arreglos para ver si es *.0 o *.0.*  
	   if((count($af_usuario))==(count($af_estable))){ 
	      if (((int)$af_estable[2])>((int)$af_usuario[2]))
	         $obsoleta=true;
        }    }
	//Si arriba no se termina entonces es un Firefox ESR
	elseif ((((int)$af_esr[0])==((int)$af_usuario[0]))){
	   //Analizamos el tama�o de los arreglos para ver si es *.0 o *.0.* 
	   if((count($af_usuario))==(count($af_esr))){ 
	       if (((int)$af_esr[2])>((int)$af_usuario[2]))
	           $obsoleta=true;
	   }   } */
	
	return $obsoleta;  }

//Funcion para mostrar los mensajes de actualizacion
function actualiza_firefox(){
    global $useragent, $af_firefox, $af_firefox_esr, $af_url, $ver, $af_lang_file;
    if(isset($_COOKIE['actualiza_firefox'])){
        if($_COOKIE['actualiza_firefox']=='on'){
			$useragent=$_SERVER['HTTP_USER_AGENT'];
            $webbrowser=af_detectar_navegador();
			$inicio='<div class="af_actualiza_firefox">';
            $jquery="<script type=\"text/javascript\">
            jQuery(document).ready(function($){
                $('#btnCerrarFirefox').click(function(){
					$('.af_actualiza_firefox').slideUp();
					$('body').css({\"margin-top\":\"0 !important\"});
					writeCookie('actualiza_firefox', 'off', '1440', '/', document.domain);
                    });
				$('#btnCerrarOtro').click(function(){
					$('.af_actualiza_firefox').slideUp();
					$('body').css({\"margin-top\":\"0 !important\"});
					writeCookie('actualiza_firefox', 'off', '2880', '/', document.domain);
				});
			});
        	function writeCookie(name, value, hours, path, domain){
        	    var expire ='';
				if(hours != null){
					expire = new Date((new Date()).getTime() + hours * 3600000);
					expire = \"; expires=\" + expire.toGMTString();
				}
				if(path){
					path=\"; path=\"+path;
				}else path=\"\";
				if(domain){
					domain=\"; domain=\"+domain;
				}else domain=\"\";
				
				document.cookie = name + \"=\" + escape(value) + expire + path + domain;
        	}
            </script>";
		  	if(strpos($useragent,"Firefox")){
				$ver=af_detectar_version_firefox($webbrowser);
                if(af_comparar_versiones($ver))
					echo $jquery.$inicio.$af_lang_file['closeFirefox'].$af_lang_file['downloadFirefox1'].$af_url.$af_lang_file['downloadFirefox2'].$af_lang_file['alertFirefoxNoUpdated'];
			}else
				echo $jquery.$inicio.$af_lang_file['closeOther'].$af_lang_file['downloadOther1'].$af_url.$af_lang_file['downloadOther2'].$af_lang_file['alertNoFirefox'];
        }   }  }
?>