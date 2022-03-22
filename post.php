<?php

	class Post
	{
		private $uri;
		private $long_uri;
		private $direccion;
		private $recurso;
		private $param;
		private $cnbd;
		private $sbd;
		private $cbd;
		private $cbdu;
		private $vbdu;
		private $ru;
		private $pos_interrogante;
		private $antrec;
		private $posrec;
		private $chrec;
		private $tablas;
		private $trec;
		private $plimit;
		private $tparam;
		private $pram;
		private $cad;
		private $cmp;
		private $campos;
		private $valores;
		
		function get_param() //para obtener los parametros de la url y guarda los parámetros en postrec y tparam
		{
			$this -> uri = "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$this -> direccion = explode('/', $this -> uri);
			$this -> long_uri = count($this -> direccion) - 1;
			
			//localiza los parámetros y los recursos de la url
			$this -> param =  $this -> direccion[$this -> long_uri];
			$this -> recurso = $this -> direccion[$this -> long_uri - 1];
			
			$this -> pos_interrogante = strpos($this -> param, '?');
			
			//comprueba si en param hay un ?
			if ($this -> pos_interrogante !== false)
			{
				//guarda la parte de la izquierda del ?. Ejemplo: productos?nombre="hola", guarda productos
				$this -> antrec = stristr($this -> param, '?', true);
				echo " <br />antrec es" . $this -> antrec . "<br />";
				$this -> pram = strpos($this -> param, '=');
				//si hay igual en el parametro se obtiene el parametro y el valor
				if($this -> pram !== false)
				{
					//para separar el parámetro de su valor
					//se obtiene la parte derecha del ? con el propio ?
					//el ? se quita en comprovar_parametro()
					$this -> posrec = stristr($this -> param, '?');
					echo "postrec es " . $this -> posrec;
				}
				
				//comprueba si la tabla es la correcta
				$this -> chrec = $this -> comprobar_tabla($this -> antrec);
				
				//si el recurso es una tabla que existe
				if($this -> chrec !== false) 
				{
					//echo "<br />Se comrpueba el parametro";
					//el recurso o tabla está guardada en antrec
					$this -> comprobar_parametro($this -> posrec);
					$this -> get_datos();
				}
				else echo "No se encuentra la tabla " . $this -> antrec;
			}
			else //si no hay ? significa que se ha introducido mal la url
			{
				echo "Faltan par&aacute;metros";
			}	
		}
		
		//comprueba si la tabla guardada en r està en la base de datos
		function comprobar_tabla($r)
		{
			//array con las tablas de la base de datos
			/*$this -> tablas = array('productos', 'tiendas');

			if (in_array($r, $this -> tablas) === true) return true;
			else return false;*/
			//echo "<br />r es "; var_dump($r);
			//conecta con la base de datos
			$this -> conectar_bd();
			
			$qry = "SHOW TABLES";
			$result=mysqli_query($this -> cnbd, $qry);
			
			$idt = 0; //guarda las tablas de la base de datos en tablas
			while ($fdb = $result -> fetch_array(MYSQLI_NUM)) 
			{
				$tablas[$idt] = $fdb[0]; //guarda la primera posición del array que es el nombre de la tabla
				$idt++;
			}
			
			//echo "<br />Tablas es "; var_dump($tablas);
			//echo "<br />tparam es "; var_dump($this -> tparam);
			
			//Si la tabla está en el array tablas se pasa a comprobar si los campos introducidos son correctos
			if (in_array($r, $tablas)) 
			{
				//guarda en el array cmp los campos de la tabla guardada en antrec
				$qry = "SHOW COLUMNS FROM " . $this -> antrec;
				$result=mysqli_query($this -> cnbd, $qry);
				
				$idt = 0;
				while ($fdb = $result -> fetch_array(MYSQLI_NUM)) 
				{
					$this -> cmp[$idt] = $fdb[0]; //guarda la primera posición del array que es el nombre del campo
					$idt++;
				}
				
				$result -> free();
				
				return true;
			}
			else return false;
		}
		
		//comprueba si después del ? hay algún otro parámetro con &. Tanto si lo hay como sino se separan los valores de los campos
		function comprobar_parametro($p)
		{
			//determina si hay & o no, es decir, como mínimo otro parámetro.
			$amper = strpos($p, '&');
			if($amper !== false) //si hay otro parámetro
			{
				//echo "<br />p es " . $p; echo "<br />amper es " . $amper;
				
				//se guarda el parámetro nombre y su valor. Para ello se quita el &
				$pn = explode('&', $p);
				
				//borra el interrogante del primer parámetro del array pn
				$pn[0] = $this -> borrar_interrogante($pn[0]);
				
				//echo "<br />pn es "; var_dump($pn);
				
				//convierte a string el array pn para usarlo en el explode de separar_valores
				$spn = implode("&",$pn);
				
				//separa los campos de los valores en el array pn para guardarlos en campos y valores respectivamente
				$this -> separar_valores($spn);
			}
			else //si solo hay un parámetro después del ?
			{
				//echo "Solo hay un parametro";
				
				//borra el ? de la primera posición del parámetro
				$bprm = substr($p, 1);
				//separa el parámetro de su valor
				$tprm = explode('=', $bprm);
				
				//echo "<br />tprm es "; var_dump($tprm);
				
				//guarda el nombre de la tabla y de los parámetros en tparam
				$this -> guardar_parametros($tprm);
			}	
		}
		
		//separa parámetros y valores para guardarlos en tparam
		function separar_valores($pt)
		{
			//echo "<br />pt es "; var_dump($pt);
			
			//guarda el nombre de la tabla en la posición 0
			$this -> tparam[0] = $this -> antrec;
			
			//separa los valores de los parámetros
			$prt = explode('&', $pt);
						
			//para recorrer todo el array prt
			for($i = 0; $i<=count($prt) - 1; $i++)
			{
				//determina en que posición de prt está el =
				$igual = stripos($prt[$i],'=');
				
				//si el igual existe
				if($igual !== false)
				{
					//obtiene el campo para guardarlo en campos
					$this -> campos[$i] = substr($prt[$i],0,$igual); 
					
					//comprueba si se ha escrito algo como nombre de campo
					if(isset($this -> campos[$i]) === true && ($this -> campos[$i] === false))
						echo "Nombre de campo vac&iacute;o";	
					
					//obtiene el valor para guardarlo en valores. Para ello primero se calcula el inicio de la cadena del valor
					$postigual = $igual + 1;
					
					//se guarda el valor del campo en valores
					$this -> valores[$i] = substr($prt[$i],$postigual);
					//echo "<br />valores es "; var_dump($this -> valores);
				}
				else
				{
					echo "Par&aacute;metro incorrecto. Falta el valor";
					break;
				}
			}
		}
		
		//elimina el ? del parámetro
		function borrar_interrogante($pri)
		{
			$pri = ltrim($pri,"?");
			//echo "<br />pri es " . $pri;
			
			return $pri;
		}
		
		//guarda el recurso o tabla y el parámetro después del ?
		function guardar_parametros($pt)
		{
			//se guarda el recurso o tabla 
			$this -> tparam[0] = $this -> antrec;
			
			//se guarda el nombre del parámetro y su valor en campos y valores respectivamente
			$this -> campos = $pt[0];
			$this -> valores = $pt[1];
			
			echo "<br />pt es "; var_dump($pt);
			echo "<br />campos es "; print_r($this -> campos);
			echo "<br />valores es "; print_r($this -> valores);
		}
		
		function conectar_bd()
		{
			$this -> cnbd = mysqli_connect("localhost","root","","globalfw");
			
			if (mysqli_connect_errno())
				echo "Error de conexi&oacute;n en la base de datos " . mysqli_connect_error();
		}
		
		function get_datos() //para obtener los datos de la base de datos y hacer la consulta
		{
			//conexión con la base de datos y selección de de las tablas
			$this -> conectar_bd();
			
			//para saber cuántos parámetros hay en campos
			$tp = count($this -> campos);
			
			//si la consulta tiene un solo campo
			if($tp === 1)
			{
				for($i = 0; $i < $tp; $i++)
				{
					//si solo hay un valor en campos y valores php lo trata como string. Si hay más de un valor lo trata como array
					$this -> cbdu = "INSERT INTO " . $this -> tparam[0] . "(" . $this -> campos . ")" . "VALUES ('" . $this -> valores . "')";
					echo "<br />" . $i;
					echo "<br />La inserci&oacute;n es " . $this -> cbdu;
					//$this -> cbdu = "SELECT * FROM " . $this -> tparam[0];
				}	
			}
			
			//si la consulta tiene más de un campo
			if($tp > 1)
			{
				$this -> cadcmps = implode(",", $this -> campos);
				$this -> cadvls = implode(",", $this -> valores);
				
				echo "<br />cadcmps es "; var_dump($this -> cadcmps);
				echo "<br />cadvls es "; var_dump($this -> cadvls);
				
				$this -> cbdu = "INSERT INTO " . $this -> tparam[0] . "(" . $this -> cadcmps . ")" . "VALUES ('" . $this -> cadvls . "')";
				echo "<br />La consulta es "; var_dump($this -> cbdu);
			}
						
			//codifica los datos en json y los muestra
			$this -> mostrar_datos();
		}
		
		//codifica los datos en json y los muestra por pantalla
		function mostrar_datos()
		{
			$this -> vbdu = mysqli_query($this -> cnbd, $this -> cbdu) or die(mysqli_error($this -> cnbd));
			
			//para comprobar el error que se produce en la consulta si se produce alguno
			if($this -> vbdu)
			{
				echo "Error de consulta</br />" . mysqli_error($this -> cnbd);
			}
			
			if($this -> vbdu == false) echo "Error en la consulta";
			else 
			{
				echo "consulta correcta";
				$tdatos = array(); 
				
				//los datos de la consulta se guardan en ru y se muestran en json
				if(mysqli_affected_rows($this -> cnbd) > 0)
				{ 
					while($this -> ru = mysqli_fetch_assoc($this -> vbdu))
				    {
						//codifica los datos en utf-8 para que puedan ser codificados en json
						$this -> cad = implode($this -> ru);
						$this -> cad = utf8_encode($this -> cad);
						
						//los datos codificados se guardan en el array tdatos
						$tdatos[] = $this -> cad;
					}	
					
					/*switch (json_last_error()) 
					{
						case JSON_ERROR_NONE:
							echo ' - No errors';
						break;
						case JSON_ERROR_DEPTH:
							echo ' - Maximum stack depth exceeded';
						break;
						case JSON_ERROR_STATE_MISMATCH:
							echo ' - Underflow or the modes mismatch';
						break;
						case JSON_ERROR_CTRL_CHAR:
							echo ' - Unexpected control character found';
						break;
						case JSON_ERROR_SYNTAX:
							echo ' - Syntax error, malformed JSON';
						break;
						case JSON_ERROR_UTF8:
							echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
						break;
						default:
							echo ' - Unknown error';
						break;
					}*/
					
					echo json_encode($tdatos);
					//echo "<br />"; print_r($tdatos);
				}
				else echo "Ninguna fila retornada";		
			}
		}
	}
	
	$r = new Post;
	$r->get_param();
	
?>