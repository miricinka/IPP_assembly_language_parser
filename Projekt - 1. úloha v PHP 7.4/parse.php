<?php
/**
 * File name: parse.php
 * Description: Projekt 1 do předmětu IPP 2020, FIT VUT
 * Athor: Mirka Kolaříková (xkolar76)
 * Date: 17.2.2020
 */
/*-------------------------------------MAIN--------------------------------------*/
$comments = 0;
$loc = 0;
$jumps = 0;
$labels = 0;
$labels_array[] = "";
$instruction_counter = 0;
$filename = "";

f_xml_output_start();
$stats = f_argument_check($argc, $argv);
$in_file = f_read_stdin();
f_line_check($in_file);

f_xml_output_end(); //prints xml representation to stdout
if($stats == 1){ //prints statistic to a file if given parametr stats
	f_stats_print($argc, $argv, $filename, $comments, $loc, $jumps, $labels);}

/*----------------------------------FUNCTIONS-------------------------------------*/


/*
 * Validates arguments
 */
function f_argument_check($argc, $argv){
	global $filename;
	$options = getopt('',["help", "stats:", "loc", "comments", "labels", "jumps"]);

	if($argc == 1){
		return 0;}
	if(array_key_exists('help', $options) && $argc != 2){ 
		exit(10);}
	if($argc == 2 && $argv[1] == "--help"){ //--help -> valid argument
			echo 
			"------------------------NAPOVEDA-------------------------\n".
			"Skript typu filtr (parse.php v jazyce PHP 7.4)\n".
			"načte ze standardního vstupu zdrojový kód v IPPcode20,\n".
			"zkontroluje lexikální a syntaktickou správnost kódu\n".
			"a vypíše na standardní výstup XML reprezentaci programu.\n".
			"---------------------------------------------------------\n";
			exit(0);
		}
	if(array_key_exists('stats', $options) && $argc == 2){ //no other arguments -> empty file
		$filename = $options['stats'];
		return 1;
	}

	if(array_key_exists('stats', $options) && (array_key_exists('loc', $options) || 
		array_key_exists('comments', $options) || array_key_exists('labels', $options) 
			|| array_key_exists('jumps', $options))){

		$filename = $options['stats'];
		return 1;
	}

exit(10); //else error

}


/*
 * Reads from stdin
 */
function f_read_stdin(){
	$file = file_get_contents('php://stdin');
	if ($file == FALSE){
		exit(11);
	}
	return $file;
}

/*
 * Cuts the input string in lines and validates them
 */
function f_line_check($string){
	$array = explode(PHP_EOL, $string); //explodes string by new line
	$array_count = count($array);

	for ($i = 0; $i < $array_count; $i++){
		$array[$i] = f_remove_comment($array[$i]); //removes commentar
		$array[$i] = trim($array[$i]);     //removes empty characters
		$array[$i] = preg_replace('/\s+/', ' ', $array[$i]); //removes empty chars

	}

	$array = array_filter($array, 'strlen'); //removes empty lines
	$array = array_values($array); //renumbers array

	if(strcasecmp($array[0], '.IPPcode20') != 0){ //head check
		exit(21);
	}
	unset($array[0]); //.ippcode20 no longer needed
	$array = array_values($array);

	global $loc;
	$loc = count($array);

	$array_count = count($array);
	for($i = 0; $i < $array_count; $i++){
		f_parse($array[$i]);
	}

}

/*
 * Checks syntax of instruction and prepares xml representstion
 */
function f_parse($line){
	$line = explode(' ', $line);
	//var_dump($line);
	global $jumps;
	global $labels;
	global $instruction_counter;
	global $labels_array;
	$instruction_counter++;
	$line_arg_count = count($line);
	if(!($line_arg_count >= 1 && $line_arg_count <= 4)){ //incorrect argumet number
		exit(23); 
	}

	$line[0] = strtoupper($line[0]);
	switch ($line[0]) {
		case 'MOVE': //var symb
			if($line_arg_count != 3){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_instruction_end();
			break;
		case 'CREATEFRAME':
			if($line_arg_count != 1){
				exit(23);
			}
			f_xml_instruction_start($line[0]);
			f_xml_instruction_end();
			break;
		case 'PUSHFRAME':
			if($line_arg_count != 1){
				exit(23);
			}
			f_xml_instruction_start($line[0]);
			f_xml_instruction_end();
			break;
		case 'POPFRAME':
			if($line_arg_count != 1){
				exit(23);
			}
			f_xml_instruction_start($line[0]);
			f_xml_instruction_end();
			break;
		case 'DEFVAR': //var
			if($line_arg_count != 2){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_instruction_end();
			break;
		case 'CALL': //label
			if($line_arg_count != 2){
				exit(23);
			}
			if(f_labelname_check($line[1])==FALSE){
				exit(23);
			}
			$jumps++;
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '7');
			f_xml_instruction_end();
			break;
		case 'RETURN':
			if($line_arg_count != 1){
				exit(23);
			}
			$jumps++;
			f_xml_instruction_start($line[0]);
			f_xml_instruction_end();
			break;
		case 'PUSHS': //symb
			if($line_arg_count != 2){
				exit(23);
			}
			$num=(f_symbol_check($line[1]));
			if($num==FALSE){
				exit(23);
			}
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], $num);
			f_xml_instruction_end();
			break;
		case 'POPS': //var
			if($line_arg_count != 2){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_instruction_end();
			break;
		case 'ADD': //var symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'SUB': //var symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'MUL': //var symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'IDIV': //var symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'LT': //var symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'GT': //var symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'EQ': //var symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'AND': //var symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'OR': //var symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'NOT': //var symb
			if($line_arg_count != 3){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_instruction_end();
			break;
		case 'INT2CHAR': //var symb
			if($line_arg_count != 3){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_instruction_end();
			break;
		case 'STRI2INT': //var symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'READ': //var type
			if($line_arg_count != 3){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			if(f_type_check($line[2])==FALSE){
				exit(23);
			}
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], '6');
			f_xml_instruction_end();
			break;
		case 'WRITE': //symb
			if($line_arg_count != 2){
				exit(23);
			}
			$num=(f_symbol_check($line[1]));
			if($num==FALSE){
				exit(23);
			}
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], $num);
			f_xml_instruction_end();
			break;
		case 'CONCAT': //var symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'STRLEN': //var symb
			if($line_arg_count != 3){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_instruction_end();
			break;
		case 'GETCHAR': //var symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'SETCHAR': //var symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}

			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'TYPE': //var symb
			if($line_arg_count != 3){
				exit(23);
			}
			if(f_variable_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '1');
			f_xml_argument('2',$line[2], $num);
			f_xml_instruction_end();
			break;
		case 'LABEL': //label
			if($line_arg_count != 2){
				exit(23);
			}
			if(f_labelname_check($line[1])==FALSE){
				exit(23);
			}
			//$labels++;
			if(!(in_array($line[1], $labels_array))){
				array_push($labels_array, $line[1]);
				$labels++;
			}
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '7');
			f_xml_instruction_end();
			break;
		case 'JUMP': //label
			if($line_arg_count != 2){
				exit(23);
			}
			if(f_labelname_check($line[1])==FALSE){
				exit(23);
			}
			$jumps++;
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '7');
			f_xml_instruction_end();
			break;
		case 'JUMPIFEQ': //label symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_labelname_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}
			$jumps++;
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '7');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'JUMPIFNEQ': //label symb symb
			if($line_arg_count != 4){
				exit(23);
			}
			if(f_labelname_check($line[1])==FALSE){
				exit(23);
			}
			$num=(f_symbol_check($line[2]));
			if($num==FALSE){
				exit(23);
			}
			$num2=(f_symbol_check($line[3]));
			if($num2==FALSE){
				exit(23);
			}
			$jumps++;
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], '7');
			f_xml_argument('2',$line[2], $num);
			f_xml_argument('3',$line[3], $num2);
			f_xml_instruction_end();
			break;
		case 'EXIT': //symb
			if($line_arg_count != 2){
				exit(23);
			}
			$num=(f_symbol_check($line[1]));
			if($num==FALSE){
				exit(23);
			}
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], $num);
			f_xml_instruction_end();
			break;
		case 'DPRINT': //symb
			if($line_arg_count != 2){
				exit(23);
			}
			$num=(f_symbol_check($line[1]));
			if($num==FALSE){
				exit(23);
			}
			f_xml_instruction_start($line[0]);
			f_xml_argument('1',$line[1], $num);
			f_xml_instruction_end();
			break;
		case 'BREAK':
			if($line_arg_count != 1){
				exit(23);
			}
			f_xml_instruction_start($line[0]);
			f_xml_instruction_end();
			break;
		default:
			exit(22); //unknown operation code
			break;
	}

}

/*
 * Searchs for comment and removes it
 * @return line without comment
 */
function f_remove_comment($line){
	if(mb_strpos($line, '#') !== FALSE){
		//comment found
		$line = explode('#', $line); 
        $line = $line[0]; //comment removed

        global $comments;
        $comments ++;
        return $line;
	}
	else{
		//comment not found
		return $line;
	}
}

/*
 * Validates variable name
 * @return true/false
 */
function f_variable_check($variable){
	if(preg_match('/^(LF|TF|GF)@([A-Za-z]|_|-|\$|&|%|\*|!|\?)([A-Za-z0-9]|_|-|\$|&|%|\*|!|\?)*$/', $variable)){
		return TRUE;
	}
	return FALSE;
}

/*
 * Validates label name
 * @return true/false
 */
function f_labelname_check($label){
	if(preg_match('/^([A-Za-z]|_|-|\$|&|%|\*|!|\?)([A-Za-z0-9]|_|-|\$|&|%|\*|!|\?)*$/', $label)){
		return TRUE;
	}
	return FALSE;
}

/*
 * Validates symbol
 * @return 0 - uvalid, 1 - variable, 2 - bool, 3 - int, 4 - string, 5 - nil
 */
function f_symbol_check($symbol){

	if(preg_match('/^(LF|TF|GF)@.*$/', $symbol)){
		if(f_variable_check($symbol) == FALSE){
			return 0;
		}
		return 1; //its valid variable

	}
	elseif(!(preg_match('/^(bool|nil|int|string)@.*$/', $symbol))){ //invalide symbol
		return 0;
	}

	$symbol = explode('@',$symbol, 2);
	if($symbol[0] === 'bool'){
		if(preg_match('/^(true|false)$/', $symbol[1])){
			return 2; //valid bool
		}
		return 0;
	}
	elseif($symbol[0] === 'int'){
		if(preg_match('/^(\+|-)*[0-9]+$/', $symbol[1])){
			return 3; //valid int
		}
		return 0;

	}
	elseif($symbol[0] === 'string'){
		if(preg_match('/^(\\\\[0-9]{3}|[^\\\\])*$/', $symbol[1])){
			return 4; //valid string
		}
		return 0;
	}
	elseif($symbol[0] === 'nil'){
		if($symbol[1] === 'nil'){
			return 5; //valid nil
		}
		return 0;
	}
	return 0;
	
}

/*
 * Validates type
 * @return true/false
 */
function f_type_check($type){
	if($type === 'int' || $type === 'string' ||$type === 'bool'){
		return TRUE;
	}
	return FALSE;
}

/*
 * Starts xml representation of instruction
 */
function f_xml_instruction_start($opcode){
	global $xmlwriter;
	global $instruction_counter;
	xmlwriter_start_element($xmlwriter, 'instruction');
	xmlwriter_write_attribute($xmlwriter,'order', $instruction_counter);
	xmlwriter_write_attribute($xmlwriter,'opcode', $opcode);
}
/*
 * Ends xml representation of instruction
 */
function f_xml_instruction_end(){
	global $xmlwriter;
	xmlwriter_end_element($xmlwriter);
}
/*
 * xml representation of arguments
 * $arg_num: argument order
 * $variable: variable, label or symbol name
 * $code: 1 - var, 2 - bool, 3 - int, 4 - string, 5 - nil, 6 - type, 7 - label
 */
function f_xml_argument($arg_num, $variable, $code){
	global $xmlwriter;
	$symbol = explode('@',$variable, 2);
	xmlwriter_start_element($xmlwriter, "arg$arg_num");
	if($code == '1'){
		xmlwriter_write_attribute($xmlwriter,'type', 'var');
		xmlwriter_text($xmlwriter, $variable);}
	elseif($code == '2'){
		xmlwriter_write_attribute($xmlwriter,'type', 'bool');
		xmlwriter_text($xmlwriter, $symbol[1]);}
	elseif($code == '3'){
		xmlwriter_write_attribute($xmlwriter,'type', 'int');
		xmlwriter_text($xmlwriter, $symbol[1]);}
	elseif($code == '4'){
		xmlwriter_write_attribute($xmlwriter,'type', 'string');
		xmlwriter_text($xmlwriter, $symbol[1]);}
	elseif($code == '5'){
		xmlwriter_write_attribute($xmlwriter,'type', 'nil');
		xmlwriter_text($xmlwriter, 'nil');}
	elseif($code == '6'){
		xmlwriter_write_attribute($xmlwriter,'type', 'type');
		xmlwriter_text($xmlwriter, $variable);}
	elseif($code == '7'){
		xmlwriter_write_attribute($xmlwriter,'type', 'label');
		xmlwriter_text($xmlwriter, $variable);}
	//xmlwriter_text($xmlwriter, $variable);
	xmlwriter_end_element($xmlwriter);

}

/*
 * Starts xml representation
 */
function f_xml_output_start(){
	global $xmlwriter;
	$xmlwriter = xmlwriter_open_memory();
	xmlwriter_start_document($xmlwriter, '1.0', 'UTF-8');
	xmlwriter_set_indent($xmlwriter, '4');
	xmlwriter_start_element($xmlwriter, 'program');
	xmlwriter_write_attribute($xmlwriter,'language', 'IPPcode20');
}

/*
 * Ends xml representation and prints to stdout
 */
function f_xml_output_end(){
	global $xmlwriter;
	xmlwriter_end_element($xmlwriter);
	$xml = xmlwriter_output_memory($xmlwriter);
	echo $xml;

}
/*
 * Prints statistics to a file $filename
 */
function f_stats_print($argc, $argv, $filename, $comments, $loc, $jumps, $labels){
	$statistics = ""; //empty file
	file_put_contents($filename, $statistics);

	for($i = 1; $i<$argc; $i++){ 
		$statistics = "";
		if($argv[$i] == "--loc"){
			$statistics = "$loc" . "\n";
		}
		elseif($argv[$i] == "--comments"){
			$statistics = "$comments" . "\n";
		}
		elseif($argv[$i] == "--labels"){
			$statistics = "$labels" . "\n";
		}
		elseif($argv[$i] == "--jumps"){
			$statistics = "$jumps" . "\n";
		}
		file_put_contents($filename, $statistics, FILE_APPEND);
	}
}

?>
