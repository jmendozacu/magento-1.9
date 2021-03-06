<?php
/****
*
*  Autor  : Guillermo de Cáceres
*  Cliente: ELITE STORE
*  Fecha  : 01-03-2014 
*  Notas  : 
*			DELETE PRODUCTS  mysql -u elite_dev -pepsi0506xx elitestore_dev </var/www/vhosts/elitestore.es/dev/magmi/delete_all_productos.txt
*			SE EJECUTA CADA ENPUNTO Y CADA Y CUARTO
*
*****/
	set_time_limit(0); 
	ini_set('memory_limit', '2048M');
	
	//import MAGMI
	require_once("./inc/magmi_defs.php");
	require_once("./integration/inc/magmi_datapump.php"); 

	//import MAGE
	require_once "../app/Mage.php";
	Mage::app();
	umask(0);
		
inicio(); //star program

function inicio(){
	
	Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID); 
	
	try{
		$inicio = microtime(true);
		//ASEGURARSE DE LOS PERMISOS DEL DIRECTORIO 777 /magmi/files/
		$dir = "./files/";
		$dir_processing = $dir.'processing_update/';
		$dir_processed  = $dir.'processed/';
	
	
		//READ FOLDER
		$files = scandir($dir_processing);
		$files = array_diff(scandir($dir_processing), array('..', '.'));
		foreach($files as $file){
				Mage::log("trabajamos con ".$dir_processing.$file, null, 'direct-sql-'.date("Ymd").'.log');
				echo "<br/>Procesamos ".$dir_processing.$file."<br>"; 
				
				//import elitestore ES
				Mage::log("Comienzo importacion basica", null, 'direct-sql-'.date("Ymd").'.log');
				$importacion 		= importar_fichero($dir_processing,$file);
				
				//move file to processed folder
				rename($dir_processing.$file,$dir_processed.date(now())."-".$file) or die(alertOnError("Unable to rename ".$dir_processing.$file." to ".$dir_processed.$file));
				
		}

		$final = microtime(true);
		$segundos= round($final- $inicio,4);
		$minutos= round(($final- $inicio) / 60,4);
		Mage::log("Final de importacion, duración: ".$minutos." minutos  / ".$segundos." segundos", null, 'direct-sql-'.date("Ymd").'.log');


	} catch (Exception $ex){ 
			Mage::log("ERROR EN LECTURA DE FICHEROS ".$ex->getMessage(), null, 'direct-sql-'.date("Ymd").'.log');
			alertOnError("IMPORTAR CSV",$ex->getMessage());
	} 
	
	echo "<hr>Proceso finalizado"; 

}

//IMPORT FILE CREATE PRODUCTS
function importar_fichero($dir,$file){
	$file = $dir.$file; //contact folder + file
	$iniciofichero = microtime(true);
	$read  = Mage::getSingleton('core/resource')->getConnection('core_read');
	

	$dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport"); // create a Product import Datapump using Magmi_DatapumpFactory
	$dp->beginImportSession("default","create"); // Available modes: "create" creates and updates items, "update" updates only, "xcreate creates only. || Important: for values other than "default" profile has to be an existing magmi profile 
	
	$handle = fopen($file,"r")  or die("no puedo leer el fichero ->".$file);
	$valoresLinea = array(); //donde guardaremos los datos
	
	$acumulador_asociados 	= array(); //solo procesamos una vez los asociados
	$valor_asociado			= array(); //acumulamos los asociados con la clave
	
	$acumulador_sites 		= array(); //los configurables no tienen visibilidad
		
		if($handle){
			$contaLineas = 0;
			$cabecerasLinea = array();

			while (($data = fgetcsv($handle, 6000, ';')) !== FALSE) {

				if($contaLineas==0):
					$cabecerasLinea = $data;
					
					array_walk($cabecerasLinea, 'quitar_dobles_comillas');
					
					//procesamos la posicion de los elementos
					$posicion_sku = array_search('sku',$cabecerasLinea);
					$posicion_price			= array_search('price',$cabecerasLinea);
					$posicion_special_price	= array_search('special_price',$cabecerasLinea);
					
					//from to special price
					$posicion_special_from 	= array_search('special_from_date',$cabecerasLinea);
					$posicion_special_to 	= array_search('special_to_date',$cabecerasLinea);

					if(!is_numeric($posicion_sku)):
						exit('<br>no tenemos posicion sku ->'.$posicion_sku.print_r($data));
						alertOnError("LECTURA FICHERO","NO TENEMOS SKU EN LAS CABECERAS");
					endif;
										
				else:
					$contaCampos = 0;

					foreach($data as $valor){
						if($contaCampos == $posicion_sku):
							echo "<br>Procesamos SKU ->".$valor."<br>";
						endif;
						
						$item[$cabecerasLinea[$contaCampos]] = $valor; //asigno los valores a sus cabeceras por linea	
						
						$contaCampos++; //increment position of line
					}
						
						//reasignamos o recalculamos valores
						$sku 		= $data[$posicion_sku];
						
						$item['special_price'] 	   = getPrecioEspecial($data[$posicion_price],$data[$posicion_special_price]);
						
						if(trim($data[$posicion_special_from])!=''):
							$item['special_from_date'] = getFechaReal($data[$posicion_special_from]);
						endif;
						
						if(trim($data[$posicion_special_to])!=''):
							$item['special_to_date']   = getFechaReal($data[$posicion_special_to]);
						endif;
						
						try{
							//print_r($item);
							//echo "<hr>";
							$dp->ingest($item); //insert item 
							unset($item); 		//clean item
							unset($sku);
						} catch (Exception $ex){ 
							alertOnError("CREAR ITEM",$ex->getMessage());
						}
						
				endif;
				echo "<hr>";
				$contaLineas++;		//increment line
			
			} //end while bucle for line
			fclose($handle);		//close file
		
		}

		$finalfichero 	= microtime(true);
		$segundosfichero= round($finalfichero- $iniciofichero,4);
		$minutosfichero = round(($finalfichero- $iniciofichero) / 60,4);
		
		Mage::log("Final de importacion fichero ".$name.", duración: ".$minutosfichero." minutos  / ".$segundosfichero." segundos", null, 'direct-sql-'.date("Ymd").'.log');
		
		
		unset($id_producto);
		unset($skus);
		unset($sku);
		unset($acumulador_asociados);
		
			
	// End import Session
	$dp->endImportSession();

	return "1";

}

function importar_fotos($dir,$file){
	$file = $dir.$file; //contact folder + file
	
	$dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport"); // create a Product import Datapump using Magmi_DatapumpFactory
	$dp->beginImportSession("default","create"); // Available modes: "create" creates and updates items, "update" updates only, "xcreate creates only. || Important: for values other than "default" profile has to be an existing magmi profile 
	
	$handle = fopen($file,"r")  or die("no puedo leer el fichero ->".$file);
	$procesados = array();
	$almacen	= array();

	$item['sku'] = ""; //empty for first element
		if($handle){
			$contaLineas = 0;
			$cabecerasLinea = array();

			while (($data = fgetcsv($handle, 6000, ';')) !== FALSE) {
				
				if($contaLineas==0):
					$cabecerasLinea = $data;
					array_walk($cabecerasLinea, 'quitar_dobles_comillas');
					$posicion_sku = array_search('sku',$cabecerasLinea);
					$posicion_imagen		= array_search('rutaimagen',$cabecerasLinea);
					$posicion_imagen2		= array_search('campo_cuatro',$cabecerasLinea);
					$posicion_imagen3		= array_search('campo_cinco',$cabecerasLinea);
				else:
					//https://www.blinkdata.com/magmi-image-attributes-processor/
					$item['sku'] = $data[$posicion_sku];
					
					
					
					$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$data[$posicion_sku]);
					
					$mediaApi = Mage::getModel("catalog/product_attribute_media_api");
		    		
					if($product):
						$imagenes = $mediaApi->items($product->getId(),1);
						
						if(count($imagenes)==0){ //procesamos imagenes si el producto no las tiene asociadas
							
							if(!in_array($item['sku'],$procesados)):
								$procesados[] = $item['sku'];
						
								$almacen[$item['sku']][] 	= str_replace("/media/import","",$data[$posicion_imagen]);
								$almacen[$item['sku']][] 	= str_replace("/media/import","",$data[$posicion_imagen]); //"+/prueba.jpg";
								$almacen[$item['sku']][] 	= str_replace("/media/import","",$data[$posicion_imagen]);
						
								if($data[$posicion_imagen2]!=''):
									$almacen[$item['sku']][] = str_replace("/media/import","",$data[$posicion_imagen2]);
								endif;
						
								if($data[$posicion_imagen3]!=''):
									$almacen[$item['sku']][] = str_replace("/media/import","",$data[$posicion_imagen3]);
								endif;
							else:
								$almacen[$item['sku']][] =str_replace("/media/import","",$data[$posicion_imagen]);
							endif;
						} else {
							echo "<br>No proceso imagenes para ->".$item['sku']; 
						}
					else:
						echo "<br>Producto no existe";
					endif; //si producto exist
						
				endif;
				
				$contaLineas++;		//increment line
			} //end while bucle for line
			
			
				$cadena_fotos = "";
				
				//$almacen =  array_reverse($almacen);  //ultima modificacion para la ordenacion de las fotos de los configurables
				
				foreach ($almacen as $clave => $valor){
   					$item['sku'] = $clave; 
   					$valor = array_unique($valor);
   					
   					$ultima_foto = count($valor)-1;
   					
   					$contafotos = 0;
   					$contagalery = 0;
   					foreach($valor as $fotos){
   						if($contafotos==$ultima_foto): //ordenamos las fotos DESC
   							$item['image']		= "+".$fotos;
							$item['small_image']= "+".$fotos;
							$item['thumbnail']	= "+".$fotos;
   						else:
   							if($contagalery==0):
	   							$item['media_gallery']= $fotos.";";
	   						elseif($contagalery==1):
								$item['media_gallery'].= $fotos."::back;";	   						
	   						endif;
   							$contagalery++;
   						endif; 
   						
   						
   						//limpiamos sino viene nada
   						if($item['media_gallery']=='::back;'){
   							unset($item['media_gallery']);
   						}
   						
   						$item['media_gallery_reset'] = "0"; 
   						$contafotos++;
   					}

					//reordeno media galery
					$cadena = explode(";",$item['media_gallery']);
					$cadena = array_reverse($cadena);
					//echo $cadena; 
					
					$item['media_gallery'] = implode(";",$cadena);

					print_r($item);
					
   					$dp->ingest($item);
   					
					unset($item);
										
				}
				

			
			fclose($handle);		//close file
		}
		
		
	// End import Session
	$dp->endImportSession();
	echo "fin de importar fotos";
	return "1";
	
}

function importar_extra($dir,$file,$store_id){

	$file = $dir.$file; //contact folder + file
	$iniciofichero = microtime(true);
	

	$read  = Mage::getSingleton('core/resource')->getConnection('core_read');
	
	
	$dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport"); // create a Product import Datapump using Magmi_DatapumpFactory
	$dp->beginImportSession("default","create"); // Available modes: "create" creates and updates items, "update" updates only, "xcreate creates only. || Important: for values other than "default" profile has to be an existing magmi profile 

	$handle = fopen($file,"r")  or die("no puedo leer el fichero ->".$file);
	$valoresLinea = array(); //donde guardaremos los datos
	
	
		if($handle){
			$contaLineas = 0;
			$cabecerasLinea = array();

			while (($data = fgetcsv($handle, 6000, ';')) !== FALSE) {
				if($contaLineas==0):
					$cabecerasLinea = $data;
					array_walk($cabecerasLinea, 'quitar_dobles_comillas');

					//procesamos la posicion de los elementos
					$posicion_sku = array_search('sku',$cabecerasLinea);
					$posicion_campo_uno		=  array_search('campo_uno',$cabecerasLinea);
					$posicion_campo_seis	=  array_search('campo_seis',$cabecerasLinea);
					$posicion_status 		= array_search('status',$cabecerasLinea);
					$posicion_stock_price	=  array_search('stock_price',$cabecerasLinea);
					$posicion_price			=  array_search('price',$cabecerasLinea);
					$posicion_special_stockprice	=  array_search('special_stockprice',$cabecerasLinea);
					$posicion_visibleweb	= array_search('visibleweb',$cabecerasLinea);
					
					if(!is_numeric($posicion_sku)):
						exit('no tenemos posicion sku '.$posicion_sku);
						alertOnError("LECTURA FICHERO","NO TENEMOS SKU EN LAS CABECERAS");
					endif;
				
				else:
					//Añadimos los datos especificos para ingles
					$item['sku']				= $data[$posicion_sku];
					$item['status'] 			= getEstado($data[$posicion_status]);
					$store_asociados = getTiendas($item['sku'],$data[$posicion_visibleweb]);
					
					if($data[$posicion_visibleweb]=='F' and ($store_id=='e_stock_es' or $store_id=='e_stock_en')):
						continue;
					endif;
					
					//echo "<br>queremos (".$data[$posicion_visibleweb].") ".$store_asociados;
					//echo "<br>Paso tienda->".	
					$item['store']		  		= $store_id; //aqui el codigo de la vista de tienda
					
				
					//solo tiendas en ingles
					if(strstr($store_id,'en')){
					
						$item['name'] 				= $data[$posicion_campo_uno];
						$item['description']		= $data[$posicion_campo_uno];
						$item['url_key'] 			= getUrl($data[$posicion_campo_uno]);
					
						$item['meta_title']			= $data[$posicion_campo_uno];
						$item['meta_keyword']		= str_replace(' ',',',strtolower($data[$posicion_campo_uno]));
						$item['meta_description']	= $data[$posicion_campo_seis];
						$item['short_description']	= $data[$posicion_campo_seis];
					}
					
					if($store_id=='e_stock_es'){
						$item['special_price'] 		= getPrecioEspecial($data[$posicion_price],$data[$posicion_special_stockprice]);
						
						$item['news_from_date']		= getFechaNovedad($sku); //@jesus esto viene en el fichero porque se recalcula????
						$item['news_to_date']		= getFechaFinNovedad($sku); //@jesus esto viene en el fichero porque se recalcula????
					}
						
					try{
						print_r($item);
						echo "<hr>";
						$dp->ingest($item); //insert item
						unset($item); 		//clean item
					} catch (Exception $ex){ 
						alertOnError("CREAR ITEM INGLES",$ex->getMessage());
					} 
					
				endif;
				
				$contaLineas++;		//increment line
			} //end while bucle for line
			fclose($handle);		//close file
		}

		$finalfichero 	= microtime(true);
		$segundosfichero= round($finalfichero- $iniciofichero,4);
		$minutosfichero = round(($finalfichero- $iniciofichero) / 60,4);
		
		Mage::log("Final de importacion store_id.".$store_id." ->fichero ".$name.", duración: ".$minutosfichero." minutos  / ".$segundosfichero." segundos", null, 'direct-sql-'.date("Ymd").'.log');
		
		
		unset($id_producto);
		unset($skus);
		unset($sku);
		
		
			
	// End import Session
	$dp->endImportSession();

	return 1;

}

function importar_stock($dir,$file){
	$file = $dir.$file; //contact folder + file
	$iniciofichero = microtime(true);
	$read  = Mage::getSingleton('core/resource')->getConnection('core_read');
	

	$dp = Magmi_DataPumpFactory::getDataPumpInstance("productimport"); // create a Product import Datapump using Magmi_DatapumpFactory
	$dp->beginImportSession("default","create"); // Available modes: "create" creates and updates items, "update" updates only, "xcreate creates only. || Important: for values other than "default" profile has to be an existing magmi profile 
	
	$handle = fopen($file,"r")  or die("no puedo leer el fichero ->".$file);
	$valoresLinea = array(); //donde guardaremos los datos
	
	$acumulado_skus = array(); //repiten skus asi que solo procesamos 1 vez por item para no marear el proceso
		
		if($handle){
			$contaLineas = 0;
			$cabecerasLinea = array();

			while (($data = fgetcsv($handle, 6000, ';')) !== FALSE) {

				if($contaLineas==0):
					$cabecerasLinea = $data;
					
					array_walk($cabecerasLinea, 'quitar_dobles_comillas');
					
					//procesamos la posicion de los elementos
					$posicion_sku 		= array_search('sku',$cabecerasLinea);
					$posicion_type		= array_search('type',$cabecerasLinea);
					$posicion_associated= array_search('associated',$cabecerasLinea);

					if(!is_numeric($posicion_sku)):
						exit('<br>no tenemos posicion sku ->'.$posicion_sku.print_r($data));
						alertOnError("LECTURA FICHERO","NO TENEMOS SKU EN LAS CABECERAS");
					endif;
					
				else:
						if($data[$posicion_type]=='configurable'):
							$item['sku'] = $data[$posicion_sku];
							
							if(!in_array($item['sku'],$acumulado_skus)):
								$acumulado_skus[]=$item['sku'];							
								
								$almacen=0; //ponemos a 0 el stock
								$cadena_skus = getAssociated($item['sku'], $data[$posicion_associated]);
								$skus	     = explode(",",$cadena_skus);
								
								echo "tengo ".count($skus)." hijos <br>";
								
								if(count($skus)>0):
									foreach($skus as $sku){
										try{
											$_product 	= Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
											if($_product):
												$stock 		= Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product);
												$almacen 	= $stock->getQty() + $almacen;
											endif; 
										} catch (Exception $ex){}
									}
								else:
									$almacen = 0;
								endif;
							
								if($almacen >0):
									$item['is_in_stock'] = "1";
								else:
									$item['is_in_stock'] = "0";
								endif;
								
								
								try{
									print_r($item);
									echo "<hr>";
									$dp->ingest($item); //insert item 
									unset($item); 		//clean item
								} catch (Exception $ex){ 
									alertOnError("CREAR ITEM",$ex->getMessage());
								}
							
							endif;
							
						endif;
						
				endif;
			
				$contaLineas++;		//increment line
			
			} //end while bucle for line
			fclose($handle);		//close file
		
		}

	// End import Session
	$dp->endImportSession();

	return true;
}

#
# FUNCTION SPECIFIC FROM ELITESTORE
#
//return mapping from categories
function getCategorias($sku,$departamento,$seccion,$familia){
			$catalogo[1][1][1]=array(25);
			$catalogo[1][1][2]=array(26);
			$catalogo[1][1][3]=array(27);
			$catalogo[1][1][4]=array(36);
			$catalogo[1][1][5]=array(30);
			$catalogo[1][1][6]=array(35);
			$catalogo[1][1][7]=array(31);
			$catalogo[1][1][8]=array(29);
			$catalogo[1][1][9]=array(37);
			$catalogo[1][1][10]=array(38);
			$catalogo[1][1][11]=array(155);
			$catalogo[1][1][12]=array(32);
			$catalogo[1][1][13]=array(28);
			$catalogo[1][1][14]=array(40);
			$catalogo[1][1][15]=array(32);
			$catalogo[1][1][16]=array(42);
			$catalogo[1][1][17]=array(37);
			$catalogo[1][2][1]=array(49);
			$catalogo[1][2][2]=array(47);
			$catalogo[1][2][3]=array(54);
			$catalogo[1][2][4]=array(54);
			$catalogo[1][2][5]=array(53);
			$catalogo[1][2][6]=array(48);
			$catalogo[1][2][7]=array(156);
			$catalogo[1][2][8]=array(57);
			$catalogo[1][3][1]=array(60);
			$catalogo[1][3][2]=array(176);
			$catalogo[1][3][3]=array(177);
			$catalogo[1][3][4]=array(178);
			$catalogo[1][3][5]=array(179);
			$catalogo[1][3][6]=array(180);
			$catalogo[1][3][7]=array(181);
			$catalogo[1][3][8]=array(182);
			$catalogo[1][4][1]=array(152);
			$catalogo[1][4][2]=array(183);
			$catalogo[1][4][3]=array(184);
			$catalogo[1][4][4]=array(185);
			$catalogo[1][4][5]=array(186);
			$catalogo[1][4][6]=array(187);
			$catalogo[1][4][7]=array(188);
			$catalogo[1][4][8]=array(189);
			$catalogo[1][4][9]=array(190);
			$catalogo[1][5][1]=array(207);
			$catalogo[1][5][2]=array(208);
			$catalogo[1][5][3]=array(209);
			$catalogo[1][5][4]=array(210);
			$catalogo[1][5][5]=array(211);
			$catalogo[1][5][6]=array(211);
			$catalogo[1][5][7]=array(213);
			$catalogo[1][5][8]=array(214);
			$catalogo[2][1][1]=array(64);
			$catalogo[2][1][2]=array(65);
			$catalogo[2][1][3]=array(66);
			$catalogo[2][1][4]=array(67);
			$catalogo[2][1][5]=array(68);
			$catalogo[2][1][6]=array(70);
			$catalogo[2][1][7]=array(71);
			$catalogo[2][1][8]=array(69);
			$catalogo[2][1][9]=array(72);
			$catalogo[2][1][10]=array(73);
			$catalogo[2][1][11]=array(77);
			$catalogo[2][2][1]=array(84);
			$catalogo[2][2][2]=array(81);
			$catalogo[2][2][3]=array(87);
			$catalogo[2][2][4]=array(87);
			$catalogo[2][2][5]=array(82);
			$catalogo[2][2][6]=array(83);
			$catalogo[2][2][7]=array(153);
			$catalogo[2][2][8]=array(90);
			$catalogo[2][2][9]=array(79);
			$catalogo[2][3][1]=array(93);
			$catalogo[2][3][2]=array(191);
			$catalogo[2][3][3]=array(192);
			$catalogo[2][3][4]=array(193);
			$catalogo[2][3][5]=array(194);
			$catalogo[2][4][1]=array(95);
			$catalogo[2][4][2]=array(195);
			$catalogo[2][4][3]=array(196);
			$catalogo[2][4][4]=array(197);
			$catalogo[2][4][5]=array(198);
			$catalogo[2][4][6]=array(199);
			$catalogo[2][4][7]=array(200);
			$catalogo[2][4][8]=array(201);
			$catalogo[2][5][1]=array(216);
			$catalogo[2][5][2]=array(217);
			$catalogo[2][5][3]=array(218);
			$catalogo[2][5][4]=array(219);
			$catalogo[2][5][5]=array(220);
			$catalogo[2][5][6]=array(220);
			$catalogo[2][5][7]=array(222);
			$catalogo[2][5][8]=array(223);
			$catalogo[4][1][1]=array(102);
			$catalogo[4][1][4]=array(120);
			$catalogo[4][1][5]=array(121);
			$catalogo[4][1][6]=array(206, 215);
			$catalogo[4][1][7]=array(115);
			$catalogo[4][1][8]=array(119);
			$catalogo[4][1][9]=array(112);
			$catalogo[4][1][10]=array(167);
			$catalogo[4][1][11]=array(168);
			$catalogo[4][1][12]=array(111);
			$catalogo[4][1][13]=array(110);
			$catalogo[4][1][14]=array(117);
			$catalogo[4][1][15]=array(113);
			$catalogo[4][1][16]=array(118);
			$catalogo[4][2][1]=array(226);
			$catalogo[4][2][2]=array(227);
			$catalogo[4][3][1]=array(228);
			$catalogo[4][3][2]=array(229);
			$catalogo[4][4][1]=array(169);
			$catalogo[4][4][2]=array(170);
			$catalogo[4][4][3]=array(108);
			$catalogo[4][4][4]=array(109);
			$catalogo[4][4][5]=array(205);
			$catalogo[4][5][1]=array(164);
			$catalogo[4][5][2]=array(97);
			$catalogo[4][5][3]=array(250);
			$catalogo[4][5][4]=array(165);
			$catalogo[4][5][5]=array(99);
			$catalogo[4][6][1]=array(246);
			$catalogo[4][6][2]=array(247);
			$catalogo[4][6][3]=array(248);
			$catalogo[7][1][1]=array(124);
			$catalogo[7][1][2]=array(125);
			$catalogo[7][1][3]=array(126);
			$catalogo[7][1][4]=array(127);
			$catalogo[7][1][5]=array(128);
			$catalogo[7][1][6]=array(129);
			$catalogo[7][1][7]=array(130);
			$catalogo[7][1][8]=array(132);
			$catalogo[7][1][9]=array(133);
			$catalogo[7][1][10]=array(134);
			$catalogo[7][1][11]=array(135);
			$catalogo[7][1][12]=array(136);
			$catalogo[7][1][13]=array(137);
			$catalogo[7][1][14]=array(138);
			$catalogo[7][1][15]=array(140);
			$catalogo[7][2][1]=array(144);
			$catalogo[7][2][2]=array(145);
			$catalogo[7][3][1]=array(149);
			$catalogo[7][4][1]=array(151);
	



    if ($departamento && $seccion && $familia){	
			$categorias=$catalogo[$departamento][$seccion][$familia];

		}
		$categoriasAntiguas=array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120,121,122,123,124,125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,141,142,143,144,145,146,147,148,149,150,151,152,153,155,156,158,159,160,161,162,163,164,165,166,167,168,169,170,171,174,175,176,177,178,179,180,181,182,183,184,185,186,187,188,189,190,191,192,193,194,195,196,197,198,199,200,201,205,206,207,208,209,210,211,212,213,214,215,216,217,218,219,220,221,222,223,224,225,226,227,228,229,246,247,248,249,);
		$catExist = CategoriesFromSku($sku);
		foreach ($catExist as $cat):
			if (!in_array($cat,$categoriasAntiguas)):
				$catMantener[]=$cat;
			endif;
		endforeach;	
		if (!empty($catMantener)){
			$categorias = array_merge($categorias, $catMantener);
			$categorias = array_unique($categorias);
		}
		 foreach($categorias as $categor):
			$cats .= $categor.",";
		 endforeach;
		 $cats = substr($cats,0,-1);
		 return $cats;

		
}
function CategoriesFromSku($sku){
	try{
		$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
		if($product){
			$categories = $product->getCategoryIds();
			return $categories;
		} else {
			return;
		}
	} catch (Exception $ex){
		alertOnError('sacando categorias de producto '.$sku,$ex->getMessage());
	}
}
// Devuelve la fecha de hoy si es la primera vez que se importa dicho artículo @TODO REVISAR CON JESUS
function getFechaNovedad($sku){
	$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
	if(!$product){  //Si no existia antes
		$fecha_final = Mage::getModel('core/date')->timestamp(time()); //revisar esta fecha esta dado algo raro @JESUS
	}else{
		$fecha_final = $product->getData('news_from_date');
	}
	$fecha_final = date('Y-m-d', $fecha_final); //formato magento
	return $fecha_final;
}
// Devuelve la fecha de dentro de 7 dias si es la primera vez que se importa dicho artículo
function getFechaFinNovedad($sku){
	$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);	
	if(!$product){  //Si no existia antes
		$fecha_final = Mage::getModel('core/date')->timestamp(time() + (2 * 24 * 60 * 60));;
	}else{
		$fecha_final = $product->getData('news_to_date');
	}
	$fecha_final = date('Y-m-d', $fecha_final); //formato magento
	return $fecha_final;
}
function getEstado($status){
	if($status == 'Deshabilitado'):
		return 2;
	else:
		return 1;
	endif;
}
function getVisibilidad($visibility){
	if($visibility == 'Not Visible Individually'):
		return 1;
	else:
		return 4;
	endif;
}
// Devuelve el valor "campo_tres" o "name" si éste no existe
function getDescripcionCorta($descripcion,$nombre){
	if($descripcion!=''){
		return $descripcion;
	}else{
		return $nombre;
	}
}
// Devuelve el precio especial si es distinto de 0 e inferior al precio
function getPrecioEspecial($precio,$precioespecial){
	if (($precioespecial!='.00') && ((int)$precioespecial<(int)$precio)):
		return $precioespecial;
	else:
		return '';	
	endif;
}
// Devuelve el peso correspondiente en función de la categoría.
function getPeso($departamento,$seccion,$familia){
	$catalogo[1][2][5]='0,48';
	$catalogo[1][5][3]='0,48';
	$catalogo[1][5][4]='0,48';
	$catalogo[1][5][7]='0,48';
	$catalogo[4][1][4]='0,48';
	$catalogo[4][3][1]='0,48';
	$catalogo[4][3][2]='0,48';
	$catalogo[4][4][1]='0,48';
	$catalogo[4][4][2]='0,48';
	$catalogo[1][1][1]='0,68';
	$catalogo[1][1][2]='0,68';
	$catalogo[1][1][3]='0,68';
	$catalogo[1][1][5]='0,68';
	$catalogo[1][1][10]='0,68';
	$catalogo[1][1][11]='0,68';
	$catalogo[1][1][13]='0,68';
	$catalogo[1][1][14]='0,68';
	$catalogo[1][2][1]='0,68';
	$catalogo[1][2][3]='0,68';
	$catalogo[1][2][4]='0,68';
	$catalogo[1][2][6]='0,68';
	$catalogo[1][2][7]='0,68';
	$catalogo[1][2][8]='0,68';
	$catalogo[1][5][1]='0,68';
	$catalogo[1][5][2]='0,68';
	$catalogo[1][5][5]='0,68';
	$catalogo[1][5][6]='0,68';
	$catalogo[1][5][8]='0,68';
	$catalogo[2][1][1]='0,68';
	$catalogo[2][1][2]='0,68';
	$catalogo[2][1][3]='0,68';
	$catalogo[2][1][5]='0,68';
	$catalogo[2][1][9]='0,68';
	$catalogo[2][1][10]='0,68';
	$catalogo[2][2][1]='0,68';
	$catalogo[2][2][3]='0,68';
	$catalogo[2][2][4]='0,68';
	$catalogo[2][2][5]='0,68';
	$catalogo[2][2][6]='0,68';
	$catalogo[2][2][7]='0,68';
	$catalogo[2][2][8]='0,68';
	$catalogo[2][2][9]='0,68';
	$catalogo[2][5][1]='0,68';
	$catalogo[2][5][2]='0,68';
	$catalogo[2][5][3]='0,68';
	$catalogo[2][5][4]='0,68';
	$catalogo[2][5][5]='0,68';
	$catalogo[2][5][6]='0,68';
	$catalogo[2][5][7]='0,68';
	$catalogo[2][5][8]='0,68';
	$catalogo[4][1][1]='0,68';
	$catalogo[4][1][5]='0,68';
	$catalogo[4][1][6]='0,68';
	$catalogo[4][1][7]='0,68';
	$catalogo[4][1][10]='0,68';
	$catalogo[7][1][1]='0,68';
	$catalogo[7][1][2]='0,68';
	$catalogo[7][1][3]='0,68';
	$catalogo[7][1][4]='0,68';
	$catalogo[7][1][5]='0,68';
	$catalogo[7][1][6]='0,68';
	$catalogo[7][1][7]='0,68';
	$catalogo[7][1][8]='0,68';
	$catalogo[7][1][12]='0,68';
	$catalogo[7][1][13]='0,68';
	$catalogo[1][1][8]='2';
	$catalogo[1][2][2]='2';
	$catalogo[2][1][4]='2';
	$catalogo[2][2][2]='2';
	$catalogo[4][1][11]='2';
	$catalogo[4][1][15]='2';
	$catalogo[4][1][16]='2';
	$catalogo[4][2][1]='2';
	$catalogo[4][2][2]='2';
	$catalogo[4][4][3]='2';
	$catalogo[7][1][14]='2';
	$catalogo[7][1][15]='2';
	$catalogo[7][2][1]='2';
	$catalogo[7][2][2]='2';
	$catalogo[7][4][1]='2';
	$catalogo[1][1][15]='2,6';
	$catalogo[1][3][5]='2,6';
	$catalogo[1][3][6]='2,6';
	$catalogo[1][4][3]='2,6';
	$catalogo[1][4][4]='2,6';
	$catalogo[1][4][5]='2,6';
	$catalogo[1][4][6]='2,6';
	$catalogo[1][4][7]='2,6';
	$catalogo[1][4][8]='2,6';
	$catalogo[2][3][3]='2,6';
	$catalogo[2][3][4]='2,6';
	$catalogo[2][4][4]='2,6';
	$catalogo[2][4][5]='2,6';
	$catalogo[2][4][6]='2,6';
	$catalogo[2][4][7]='2,6';
	$catalogo[2][4][8]='2,6';
	$catalogo[4][1][8]='2,6';
	$catalogo[4][1][9]='2,6';
	$catalogo[4][4][5]='2,6';
	$catalogo[7][3][1]='2,6';
	$catalogo[1][1][16]='3,6';
	$catalogo[1][3][1]='3,6';
	$catalogo[1][3][7]='3,6';
	$catalogo[1][3][8]='3,6';
	$catalogo[2][3][1]='3,6';
	$catalogo[4][1][12]='2';
	$catalogo[4][1][13]='3,6';
	$catalogo[4][1][14]='3,6';
	$catalogo[1][1][7]='5,4';
	$catalogo[1][1][17]='5,4';
	$catalogo[1][3][2]='5,4';
	$catalogo[1][3][3]='5,4';
	$catalogo[1][3][4]='5,4';
	$catalogo[1][4][1]='5,4';
	$catalogo[1][4][2]='5,4';
	$catalogo[1][4][9]='5,4';
	$catalogo[2][1][11]='5,4';
	$catalogo[2][3][2]='5,4';
	$catalogo[2][3][5]='5,4';
	$catalogo[2][4][1]='5,4';
	$catalogo[2][4][2]='5,4';
	$catalogo[2][4][3]='5,4';
	$catalogo[4][4][4]='5,4';
	$catalogo[7][1][9]='5,4';
	$catalogo[1][1][4]='6';
	$catalogo[1][1][6]='6';
	$catalogo[2][1][6]='6';
	$catalogo[7][1][10]='6';
	$catalogo[1][1][9]='9,6';
	$catalogo[1][1][12]='9,6';
	$catalogo[2][1][7]='9,6';
	$catalogo[2][1][8]='9,6';
	$catalogo[4][5][1]='9,6';
	$catalogo[4][5][2]='9,6';
	$catalogo[4][5][3]='9,6';
	$catalogo[4][5][4]='9,6';
	$catalogo[4][5][5]='9,6';
	$catalogo[7][1][11]='9,6';
	$weight='5';
	if ($departamento && $seccion && $familia){	
		$weight=$catalogo[$departamento][$seccion][$familia];		
	}
	return $weight;
}
// Devuelve los ids de las tiendas a las que debe ir dicho artículo en función del campo "Visibleweb"
function getTiendas($sku,$visibleweb){
	$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
	/*if($product){
		$tiendas = $product->getStoreIds();
		if(count($tiendas)>1):
			$write  = Mage::getSingleton('core/resource')->getConnection('core_write');
			$delete_stores = "delete from `catalog_product_website`  where product_id=".$product->getId();
			$write->query($delete_stores);
		endif;
	} 
	*/
	if ($visibleweb=='T'){
		$websites = "e_stock_es";
	}elseif ($visibleweb=='F'){
		$websites = "es";
		/*	if($product){
				$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
				$tiendas = $product->getStoreIds();
				if($sku=='PRUEBA44BIANCOTTIC'):
					echo "--- tengo -->".count($tiendas)."<<<-<br>";
				endif;
				if (count($tiendas)>2){ //En el caso en que se hubiese puesto en las dos tiendas desde magento.
					$websites = "es,e_stock_es";
				}else{
					$websites= "es";
				}
			} else {
				$websites= "es"; //añadido nuevo no tengo producto es nuevo y no estaba asignado @todo: preguntar como funcionaba esto 
			}					
		*/
	//}else{
		//$websites=array(1,2); //e_stock
		//$websites = "es,e_stock_es";
	}
	
	return $websites;
}
// @todo revisar con fotos reale CON JESUS // Devuelve la ruta correspondiente a la imagen si procede subirla
function getImagenes($product, $importData){
	$foto1=$importData['rutaimagen'];
	$foto2=$importData['campo_cuatro'];
	$foto3=$importData['campo_cinco'];
	$ruta = Mage :: getBaseDir( 'media' ) ."/". 'import' . str_replace("/media/import","",$foto1);
	$ruta = strtolower($ruta);
	$finrutaimagen = substr($importData['rutaimagen'],strlen(strtolower($foto1))-4);
	$is_file = ((file_exists($ruta)) && $finrutaimagen=='.jpg');

	$ruta2 = Mage :: getBaseDir( 'media' ) ."/". 'import' . str_replace("/media/import","",$foto2);
	$ruta2 = strtolower($ruta2);			
	$finrutaimagen2 = substr($importData['campo_cuatro'],strlen(strtolower($foto2))-4);
	$is_file2 = ((file_exists($ruta2)) && $finrutaimagen2=='.jpg'); 			

	$ruta3 = Mage :: getBaseDir( 'media' ) ."/". 'import' . str_replace("/media/import","",$foto3);
	$ruta3 = strtolower($ruta3);			
	$finrutaimagen3 = substr($importData['campo_cinco'],strlen(strtolower($foto3))-4);
	$is_file3 = ((file_exists($ruta3)) && $finrutaimagen3=='.jpg');
			
	if ($is_file){
		$subir= false;
		if ($is_file2 && $is_file3){
			if (!($product->getMediaGalleryImages()) || ($product -> getMediaGalleryImages() -> getSize()<3)){
				$subir=true;
			}
		}else if ($is_file2 && (!$is_file3)){
			if (!($product->getMediaGalleryImages()) || ($product -> getMediaGalleryImages() -> getSize()<2)){
				$subir=true;
			}
		}else if ((!$is_file2) && (!$is_file3)){
			if (!($product->getMediaGalleryImages()) || ($product -> getMediaGalleryImages() -> getSize()<1)){
				$subir=true;
			}
		}
	}
	if ($subir){
		return $ruta;	
	}else{
		return '';
	}
}
//create URL key correct
function getUrl($url,$type = NULL,$visibility = NULL,$sku = NULL){
	if($type=='simple' and $visibility==1):
		$url = $url."-".$sku;
	endif;
	$find = array('á', 'é', 'í', 'ó', 'ú', 'ñ');
	$repl = array('a', 'e', 'i', 'o', 'u', 'n');
	$url = str_replace ($find, $repl, trim(strtolower($url)));
	$find = array(' ', '&', '\r\n', '\n', '+'); 
	$url = str_replace ($find, '-', $url);
	$find = array('/[^a-z0-9\-<>]/', '/[\-]+/', '/<[^>]*>/');
	$repl = array('', '-', '');
	$url = preg_replace ($find, $repl, $url);
	return $url;
}
//transform correct format date 31/12/50  => 2050-13-31
function getFechaReal($date){
	$dia 	= substr($date,0,2);
	$mes 	= substr($date,3,2);
	$anno 	= "20".substr($date,6,2);
	return $anno."-".$mes."-".$dia;
}
//RETRIEVE INFO ATT
function att($att,$type){
	try{
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');		
		$query ="SELECT attribute_id from eav_attribute as eav WHERE eav.entity_type_id = ".$type." AND eav.attribute_code = '".$att."'";
		$attributo = $read->fetchOne($query);
		return $attributo;
	}catch(Exception $ex){
		alertOnError("ATRIBUTO ID",$ex->getMessage());
	}
}
//REINDEX PROCESS
function reindex(){
	$inicioReindex = microtime(true); 
	
	if(date("i")>0 and date("i")<16):
		$reindexar[] = 'catalog_product_price';
		$reindexar[] = 'catalog_product_attribute';
		$reindexar[] = 'cataloginventory_stock';
		$reindexar[] = 'catalog_category_product';
		$reindexar[] = 'catalog_category_flat';
		$reindexar[] = 'catalog_product_flat';
	else:
		$reindexar[] = 'catalog_product_price';
		$reindexar[] = 'cataloginventory_stock';
	endif;
	//$reindexar[] = 'catalog_url';

	for($i=0;$i<sizeof($reindexar);$i++):
		try{
			exec('php ../shell/indexer.php -reindex '.$reindexar[$i]);
		} catch (Exception $ex){
			Mage::log("No Puedo reindexar: ".$ex->getMessage(), null, 'direct-sql-'.date("Ymd").'.log');
		}
	endfor;
	
	$finalReindex = microtime(true);
	$segundosReindex= round($finalReindex- $inicioReindex,4);
	$minutosReindex= round(($finalReindex- $inicioReindex) / 60,4);
	Mage::log("Reindexado duración: ".$minutosReindex." minutos  / ".$segundosReindex." segundos", null, 'direct-sql-'.date("Ymd").'.log');

}
//SEND ERROR TO IT
function alertOnError($process,$error){
	exit($error);
		try{
			$mail = new Zend_Mail();
			$mail->setFrom('gcaceres@magento-spain.com','MG SPAIN')
			->addTo('gcaceres@magentos-spain.com','MG SPAIN')
			->setSubject($_SERVER['HOSTNAME'].'- Error proceso de importacion '.date("Y-m-d H:i:s"))
			->setBodyText('Sección: '.$process.'<br>Detalle: <br>'.$error)
			->send();	
		} catch (Exception $ex){
				Mage::log("No Puedo enviar email: ".$ex->getMessage(), null, 'direct-sql-'.date("Ymd").'.log');
		}
}
//QUITAR QUOTES
function quitar_dobles_comillas(&$value){
	$value = str_replace('"','',trim($value,$character_mask = " \t\n\r\0\x0B"));
	$value = trim($value,chr(0xC2).chr(0xA0));
	$value = trim($value,chr(173));
}

//productos asociados
function getAssociated($sku,$ids_csv){
	Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
		
	$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
	//echo "cargo ".$sku."--".$product->getName()."<br>";
	
    if($product){
		$childProducts  = $product->getTypeInstance()->getUsedProductIds();
		
		//echo "tengo ".count($childProducts);
		
		foreach($childProducts as $child_id){
			$product_child = Mage::getModel('catalog/product')->load($child_id);
			if($product_child->getSku()!=''):
				$ids_csv.= ",".$product_child->getSku();
			endif;
			unset($product_child);
		}
		
		//pasamos a array
		$items 		= explode(",",$ids_csv);
		$items 		= array_unique($items);
		$ids_csv 	= implode(",",$items);
    }
    
   	return $ids_csv; //contact news and olds products ids

}

//reordenamos la posicion de los atributos configurables
function reorderConfigAttributes($cadena){
	$items 	= explode(",",$cadena);
	$items 	= array_reverse($items);
	$items 	= implode(",",$items);
	return $items;
}