<?php

	class Mget
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
		private $rsc;
		private $antrec;
		private $posrec;
		private $chrec;
		private $tablas;
		private $trec;
		private $plimit;
		private $tparam;
		private $prall;
		private $pram;
		private $prl;
		private $plm;
		private $prsh;
		private $cad;
		
		function get_param() //para obtener los parametros de la uri
		{
			$this -> uri = "$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
			$this -> direccion = explode('/', $this -> uri);
			$this -> long_uri = count($this -> direccion) - 1;
			
			//localiza los par�metros y los recursos de la uri
			$this -> param =  $this -> direccion[$this -> long_uri];
			$this -> recurso = $this -> direccion[$this -> long_uri - 1];
			
			//comprueba si en $this -> param hay un ? lo que indicaria que hay un par�metro como m�nimo
			//puede haber un par�metro antes del ?. Ejemplo: /all?limit=4
			
			$this -> long_param = strlen($this -> param);
			//echo "Param es "; var_dump($this -> param); 
			
			$this -> pos_interrogante = strpos($this -> param, '?');
			
			//comprueba si en param hay un ?
			if ($this -> pos_interrogante !== false)
			{
				//guarda la parte de la izquierda del ?. Ejemplo: productos?nombre="hola", guarda productos
				$this -> antrec = stristr($this -> param, '?', true);
				//echo "antrec es <br />" . $this -> antrec . "<br />";
				//$this -> posrec = stristr($this -> param, '?');
				$this -> pram = strpos($this -> param, '=');
				//si hay igual en el parametro se obtiene el parametro y el valor
				if($this -> pram !== false)
				{
					//para separar el par�metro de su valor
					//se obtiene la parte derecha del ? con el propio ?
					//el ? se quita en comprovar_parametro()
					$this -> posrec = stristr($this -> param, '?');
					//echo "postrec es " . $this -> posrec;
				}
				//else "Par&aacutemetro sin valor";
				
				//comprueba si el recurso es el correcto
				$this -> chrec = $this -> comprobar_recurso($this -> antrec);
				
				if($this -> chrec !== false) 
				{
					//echo "<br />Se comrpueba el parametro";
					//el recurso o tabla est� guardada en antrec
					$this -> comprobar_parametro($this -> posrec);
				}
				else echo "No se encuentra la tabla " . $this -> antrec;
			}
			else //si no hay ? significa que puede haber el par�metro all con o sin limit
			{
				//comprueba si hay recurso o tabla
				
				$rc = $this -> comprobar_recurso($this -> recurso);
				if ($rc === true)
				{
					//echo "param es "; var_dump($this -> param);
					
					//si hay m�s de un par�metro se localiza la posici�n del &
					$this -> pram = strpos($this -> param, '&'); //echo "Pram es "; print_r($this -> pram);
					if($this -> pram !== false)
					{
						//se separan los par�metros y se guardan en prall
						$this -> prall = explode("&", $this -> param);
						
						//pasa el elemento 1 de prall a string para usarlo en el explode
						$this -> prsh = $this -> prall[1]; 
						
						//si hay un igual (limit=4 por ejemplo) se separa el par�metro del valor
						$this -> plm = strpos($this -> prsh, '='); 
						if($this -> plm !== false)
						{
							//si hay un igual se separan el par�metro del valor y se guarda en prl. Ejemplo: se separa limit de 4
							$this -> prl = explode("=", $this -> prsh);
							
							//Una vez separados el par�metro y su valor se guardan en prall dejando la primera posici�n para el par�metro all
							$this -> prall[1] = $this -> prl[0];
							$this -> prall[2] = $this -> prl[1];
							
							if (($this -> prall[0] == "all") && ($this -> prall[1] == "limit")) 
							{
								//se guarda el recurso o tabla en antrec para despu�s usarlo en guardar_parametros
								$this -> antrec = $this -> recurso;	
								$this -> guardar_parametros($this -> prall);
								
								//una vez se tienen los par�metros en tparam se hacen las consultas a la base de datos
								$this -> get_datos();
							}	
							else echo "Par&aacutemetros incorrectos. Falta par&aacutemetro all y/o hay un par&aacutemetro diferente de limit";
						}
					}	
					else //si no hay & solo se guarda el recurso o tabla y el par�metro all en tparam
						{
							//se guarda el recurso o tabla en antrec para despu�s usarlo en guardar_parametros
							$this -> antrec = $this -> recurso;
							
							//si el par�metro es all se convierte el string param en array de strings
							if($this -> param === "all")
							{
								$stparam[0] = $this -> param;
								$this -> guardar_parametros($stparam);
								
								//una vez se tienen los par�metros en tparam se hacen las consultas a la base de datos
								$this -> get_datos();
							}
							else echo "Par&aacutemetro all no encontrado";
							
						}
				}	
				else echo "Direcci&oacuten incorrecta. Falta tabla y/o par&aacutemetro o son incorrectos";
			}	
		}
		
		//separa el par�metro de su valor
		function div_param()
		{
			//echo "<br />prsh es "; var_dump($this -> prsh);
		}
		
		function comprobar_recurso($r)
		{
			//array con las tablas de la base de datos
			$this -> tablas = array('productos', 'tiendas');

			if (in_array($r, $this -> tablas) === true) return true;
			else return false;	
		}
	
		//comprueba si despu�s del ? hay alg�n otro par�metro con &
		function comprobar_parametro($p)
		{
			//determina si hay & o no, es decir, otro par�metro.
			$amper = strpos($p, '&');
			if($amper !== false) //si hay otro par�metro
			{
				//echo "<br />p es " . $p; echo "<br />amper es " . $amper;
				//se guarda el par�metro nombre y su valor. Para ello se quita el &
				$pn = explode('&', $p);
				
				//se pasa la posici�n donde est� el par�metro nombre a string
				$aux = $pn[0];
				
				//se elimina el = y se guarda
				$pnm = explode('=', $aux);
				
				//al quedar el ? se vuelve a pasar a string para eliminarlo
				$snm = $pnm[0]; //echo "snm es "; var_dump($snm);
				
				//se elimina el ? de la posici�n 0
				$snm = ltrim($snm, "?");
				
				//echo "snm es "; var_dump($snm);
				
				//una vez quitado el ? del par�metro se vuelve a guardar en la posici�n 0 de pnm
				$pnm[0] = $snm;
				
				//amper tiene la posici�n del &. Hay que ir a la siguiente posici�n
				$amper++;
				$this -> trec = substr($p, $amper);
				
				//elimina el = del par�metro con &
				$this -> plimit = explode('=', $this -> trec);
				
				//comprueba si el par�metro es limit. Si lo es guarda el recurso o tabla, los dos par�metros y sus valores en tparam
				if($this -> plimit[0] === "limit") 
				{
					//el par�metro limit y su valor se guardan en pnm para guardarlo despu�s en tparam
					$pnm[2] = $this -> plimit[0];
					$pnm[3] = $this -> plimit[1];
					
					$this -> guardar_parametros($pnm);
					
					//una vez se tienen los par�metros en tparam se hacen las consultas a la base de datos
					$this -> get_datos();
					
				}	
				else echo "Par&aacutemetro limit no encontrado";

				//echo "<br />rec es " . $this -> rec;
			}
			else //si solo hay un par�metro despu�s del ?
			{
				//borra el ? de la primera posici�n del par�metro
				//si solo se ha puesto el ?. Ejemplo: /productos?
				if($p !== NULL)
				{
					$bprm = substr($p, 1);
					
					//separa el par�metro de su valor
					$tprm = explode('=', $bprm);
					
					$this -> guardar_parametros($tprm);
					//una vez se tienen los par�metros en tparam se hacen las consultas a la base de datos
					$this -> get_datos();	
				}
				else echo "No hay par&aacutemetro o par&aacutemetro sin valor";
			}	
		}
		
		//guarda el recurso o tabla y el resto de par�metros
		function guardar_parametros($pt)
		{
			//se guarda el recurso o tabla 
			$this -> tparam[0] = $this -> antrec;
			//se guarda el nombre de lo(s) par�metro(s) y su(s) valor(es) en tparam 
			for($i=1, $j=0;$i<=count($pt);$i++, $j++)
			{
				$this -> tparam[$i] = $pt[$j];	
			}
			
			//$this -> tparam[2] = $pt[1];
			//echo "<br />tparam es"; print_r($this -> tparam);
		}
		
		function conectarbd()
		{
			$this -> cnbd = new mysqli("localhost","root","","globalfw");
			if(mysqli_connect_error())
			 {
				echo "Error en la conexi�n de la base de datos";
			 }

			$this -> sbd = "USE globalfw";
			$this -> cbd = mysqli_query($this -> cnbd,$this -> sbd);
		}
		
		function get_datos() //para obtener los datos de la base de datos
		{
			//conexi�n con la base de datos y selecci�n de de las tablas
			$this -> conectarbd();
			
			//se comprueba cu�ntos par�metros hay donde se guardan los par�metros (tparam)
			$tp = count($this -> tparam); //para saber el n�mero total de par�metros
			switch($tp)
			{
				//cuando hay solo el par�metro all (/all)
				case 2:
				{
					$this -> cbdu = "SELECT * FROM " . $this -> tparam[0];
					//echo "La consulta es " . $this -> cbdu;
					break;
				}
				//significa que hay solo un par�metro con valor (nombre=pepe)
				case 3:
				{
					$this -> cbdu = "SELECT * FROM " . $this -> tparam[0] . " WHERE " . $this -> tparam[1] . " = '" . $this -> tparam[2] . "'";
					
					//codifica los datos en json y los muestra
					//$this -> mostrar_datos();
					break;
				}
				//cuando hay el par�metro all con limit es lo mismo que si solo estuviera el all
				case 4:
				{
					//$this -> cbdu = "SELECT * FROM " . $this -> tparam[0] . " " . $this -> tparam[2] . " " . $this -> tparam[3];
					$this -> cbdu = "SELECT * FROM " . $this -> tparam[0];
					
					//codifica los datos en json y los muestra
					//$this -> mostrar_datos();
					break;
				}
				//cuando hay dos par�metros con valor (nombre=pepe&limit=4)
				case 5: 
				{
					$this -> cbdu = "SELECT * FROM " . $this -> tparam[0] . " WHERE " . $this -> tparam[1] . " = '" . $this -> tparam[2] . "' " . $this -> tparam[3] . " " . $this -> tparam[4];
					
					//codifica los datos en json y los muestra
					//$this -> mostrar_datos();
					break;
				}
			}
			
			$this -> mostrar_datos();
		}
		
		//codifica los datos en json y los muestra
		function mostrar_datos()
		{
			$this -> vbdu = mysqli_query($this -> cnbd,$this -> cbdu) or die(mysqli_error($this -> cnbd));
			
			if($this -> vbdu == false) echo "Error en la consulta";
			else 
			{
				$tdatos = array(); 
				
				//los datos de la consulta se guardan en ru y se muestran en json
				if($this -> vbdu->num_rows > 0)
				{ 
					while($this -> ru = mysqli_fetch_assoc($this -> vbdu))
				    {
						//echo $this -> ru[1];
						//codifica los datos en utf-8 para que puedan ser codificados en json
						$this -> cad = implode($this -> ru);
						$this -> cad = utf8_encode($this -> cad);
						//echo "cad es "; var_dump($this -> cad);
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
	
	$r = new Mget;
	$r->get_param();
	
?>