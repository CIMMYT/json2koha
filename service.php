<?php

require 'File/MARC.php';
error_reporting(E_ERROR | E_PARSE);


set_time_limit(0);
ini_set('default_socket_timeout', 900);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////		 Load of GLOBAL PARAMETERS  				  ////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$configs = include('config.php');

$KOHA_URL        		= $configs['KOHA_URL'];
$KOHA_USER       		= $configs['KOHA_USER'];
$KOHA_PASSWORD   		= $configs['KOHA_PASSWORD'];

$logs_dir        		= $configs['logs_dir'];
$json_dir        		= $configs['json_dir'];
$added_records   		= $configs['added_records'];

$database_username  	= $configs['database_username'];
$database_password  	= $configs['database_password'];
$database_hostname  	= $configs['database_hostname'];
$database_name  		= $configs['database_name'];


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$cnt      = 1;
$fileList = glob($json_dir);


foreach ($fileList as $url) {
    $baseDatos = $url;
    $url       = @file_get_contents($url);
    
    
    if ($url !== FALSE) {
        
        $records = json_decode($url);
        foreach ($records as $record) {
            
            if (!isset($record->leader) || !isset($record->_008) || !isset($record->titulo) || !isset($record->type) || !isset($record->fecha) || !isset($record->autor)) {
                echo "*********Some mandatory value is missing" . PHP_EOL;
            } else if (isset($record->doi) && !empty($record->doi) && doi_exists($database_hostname, $database_username, $database_password, $database_name, $record->doi)) {
                echo "\t\t Unable to insert in koha: DOI already exists" . PHP_EOL;
            } else if (record_exists($database_hostname, $database_username, $database_password, $database_name, $record->titulo, $record->sub_titulo, $record->type)) {
                echo "\t\t Unable to insert in koha: Title already exists: " . $record->titulo . $record->sub_titulo . PHP_EOL;
            } else {
				echo "\t\t Creating Marc21 record... " . $cnt . PHP_EOL;
                $marc = new File_MARC_Record();
                
                if (!empty($record->leader))
                    $marc->setLeader($record->leader);
                
                $marc->appendField(new File_MARC_Control_Field('003', 'MX-TxCIM'));
                
                $marc->appendField(new File_MARC_Control_Field('005', date('YmdHis', time()) . '.0'));
                
                if (!empty($record->_008))
                    $marc->appendField(new File_MARC_Control_Field('008', $record->_008));
                
                if (!empty($record->issn))
                    $marc->appendField(new File_MARC_Data_Field('022', array(
                        new File_MARC_Subfield('a', $record->issn)
                    ), null, null));
                
                if (!empty($record->doi))
                    $marc->appendField(new File_MARC_Data_Field('024', array(
                        new File_MARC_Subfield('a', $record->doi)
                    ), '8', '#'));
                
                $marc->appendField(new File_MARC_Data_Field('040', array(
                    new File_MARC_Subfield('a', 'MX-TxCIM')
                ), null, null));
                
                if (!empty($record->language))
                    $marc->appendField(new File_MARC_Data_Field('041', array(
                        new File_MARC_Subfield('a', $record->language)
                    ), null, null));
                
                if (!empty($record->autor))
                    $marc->appendField(new File_MARC_Data_Field('100', array(
                        new File_MARC_Subfield('a', $record->autor)
                    ), '1', '#'));
                
                
                
                if (!empty($record->titulo)) {
                    $arr_245   = array();
                    $arr_245[] = new File_MARC_Subfield('a', $record->titulo);
                    if (!empty($record->sub_titulo))
                        $arr_245[] = new File_MARC_Subfield('b', $record->sub_titulo);
                    
                    $marc->appendField(new File_MARC_Data_Field('245', $arr_245, '1', '#'));
                }
                
                $arr_260_subs = array();
                if (!empty($record->lugar))
                    $arr_260_subs[] = new File_MARC_Subfield('a', $record->lugar . ' : ');
                if (!empty($record->publisher))
                    $arr_260_subs[] = new File_MARC_Subfield('b', $record->publisher . ', ');
                if (!empty($record->year))
                    $arr_260_subs[] = new File_MARC_Subfield('c', $record->year . '.');
                
                if (!empty($arr_260_subs))
                    $marc->appendField(new File_MARC_Data_Field('260', $arr_260_subs, null, null));
                
                if (!empty($record->_500_a))
                    $marc->appendField(new File_MARC_Data_Field('500', array(
                        new File_MARC_Subfield('a', $record->_500_a)
                    ), null, null));
                
                if (!empty($record->resumen))
                    $marc->appendField(new File_MARC_Data_Field('520', array(
                        new File_MARC_Subfield('a', $record->resumen)
                    ), null, null));
                
                if (!empty($record->_546_a))
                    $marc->appendField(new File_MARC_Data_Field('546', array(
                        new File_MARC_Subfield('a', $record->_546_a)
                    ), null, null));
                
                if (isset($record->categories))
                    if (is_object($record->categories)) {
                        if (!empty($record->categories)) {
                            $categories = get_object_vars($record->categories);
                            foreach ($categories as $category) {
                                $marc->appendField(new File_MARC_Data_Field('650', array(
                                    new File_MARC_Subfield('a', $category)
                                ), null, null));
                            }
                        }
                    } else {
                        if (!empty($record->categories))
                            $marc->appendField(new File_MARC_Data_Field('650', array(
                                new File_MARC_Subfield('a', $record->categories)
                            ), null, null));
                    }
                
                if (isset($record->areas))
                    if (is_object($record->areas)) {
                        if (!empty($record->areas)) {
                            $areas = get_object_vars($record->areas);
                            foreach ($areas as $area) {
                                $marc->appendField(new File_MARC_Data_Field('650', array(
                                    new File_MARC_Subfield('a', $area)
                                ), null, null));
                            }
                        }
                    } else {
                        if (!empty($record->areas))
                            $marc->appendField(new File_MARC_Data_Field('650', array(
                                new File_MARC_Subfield('a', $record->areas)
                            ), null, null));
                    }
                
                if (is_object($record->coautores)) {
                    if (!empty($record->coautores)) {
                        $coautores = get_object_vars($record->coautores);
                        foreach ($coautores as $coautor) {
                            $marc->appendField(new File_MARC_Data_Field('700', array(
                                new File_MARC_Subfield('a', $coautor)
                            ), null, null));
                        }
                    }
                } else {
                    if (!empty($record->coautores))
                        $marc->appendField(new File_MARC_Data_Field('700', array(
                            new File_MARC_Subfield('a', $record->coautores)
                        ), null, null));
                }
                
                $volume         = isset($record->volume) ? 'v. ' . $record->volume : "";
                $issue          = isset($record->issue) ? ' no. ' . $record->issue : "";
                $pages          = isset($record->pages) ? ' p. ' . $record->pages : "";
                $article_number = isset($record->article_number) ? ' art. ' . $record->article_number : "";
                $f_773_g        = array();
                if (!empty($volume))
                    $f_773_g[] = $volume;
                if (!empty($issue))
                    $f_773_g[] = $issue;
                if (!empty($pages) && empty($article_number)) //if article_number exists do not add pages
                    $f_773_g[] = $pages;
                if (!empty($article_number))
                    $f_773_g[] = $article_number;
                
                $f_773_g = implode(', ', $f_773_g);
                
                $year      = isset($record->year) ? $record->year : "";
                $publisher = isset($record->publisher) ? $record->publisher : "";
                
                $f_773_d = array();
                if (!empty($publisher))
                    $f_773_d[] = $publisher;
                if (!empty($year))
                    $f_773_d[] = $year;
                
                $f_773_d = implode(', ', $f_773_d);
                
                $arr_773_subs = array();
                if (!empty($record->revista))
                    $arr_773_subs[] = new File_MARC_Subfield('t', $record->revista);
                if (!empty($f_773_g))
                    $arr_773_subs[] = new File_MARC_Subfield('g', $f_773_g);
                if (!empty($f_773_d))
                    $arr_773_subs[] = new File_MARC_Subfield('d', $f_773_d);
                if (!empty($record->issn))
                    $arr_773_subs[] = new File_MARC_Subfield('x', $record->issn);
                if (!empty($record->isbn))
                    $arr_773_subs[] = new File_MARC_Subfield('z', $record->isbn);
                
                if (!empty($arr_773_subs))
                    $marc->appendField(new File_MARC_Data_Field('773', $arr_773_subs, '0', '#'));
                
                if (!empty($record->type))
                    $marc->appendField(new File_MARC_Data_Field('942', array(
                        new File_MARC_Subfield('c', $record->type),
                        new File_MARC_Subfield('n', "1")
                    ), null, null));
                $arr_952_subs   = array();
                $arr_952_subs[] = new File_MARC_subfield('0', '0');
                $arr_952_subs[] = new File_MARC_subfield('7', '0');
                $arr_952_subs[] = new File_MARC_subfield('2', 'ddc');
                $arr_952_subs[] = new File_MARC_subfield('8', 'CSC');
                $arr_952_subs[] = new File_MARC_subfield('a', 'CM');
                $arr_952_subs[] = new File_MARC_subfield('b', 'CM');
                
                if (!empty($record->type))
                    $arr_952_subs[] = new File_MARC_Subfield('y', $record->type);
                
                if (!empty($arr_952_subs))
                    $marc->appendField(new File_MARC_Data_Field('952', $arr_952_subs, null, null));
                
                
                
                $xml = $marc->toXML();
                
                $url = $KOHA_URL . "/cgi-bin/koha/svc/authentication?userid=" . $KOHA_USER . "&password=" . $KOHA_PASSWORD;
                $ch  = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_COOKIEJAR, '-');
                $result = curl_exec($ch);
                
                $headers = array(
                    "Content-type: text/xml",
                    "Content-length: " . strlen($xml),
                    "Connection: close"
                );
                
                $url = $KOHA_URL . "/cgi-bin/koha/svc/new_bib?items=1";
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec($ch);
                
                $response_xml = @simplexml_load_string($result);
                if ( $response_xml and $response_xml->auth_status != 'failed' ) {
                    $url_record = $KOHA_URL . "/cgi-bin/koha/catalogue/detail.pl?biblionumber=" . $response_xml->biblionumber;
                    $added_records .= '<tr><td><a href="' . $url_record . '">' . $url_record . "</a></td></tr>";
                    
                    $marc->appendField(new File_MARC_Control_Field('001', $response_xml->biblionumber));
                    
                    $xml     = $marc->toXML();
                    $headers = array(
                        "Content-type: text/xml",
                        "Content-length: " . strlen($xml),
                        "Connection: close"
                    );
                    $url     = $KOHA_URL . "/cgi-bin/koha/svc/bib/" . $response_xml->biblionumber;
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    $result = curl_exec($ch);
                    
                    curl_close($ch);
                    $response_xml = @simplexml_load_string($result);
                    
                    
                    $url_record      = $KOHA_URL . "/cgi-bin/koha/catalogue/detail.pl?biblionumber=" . $response_xml->biblionumber;
                    $record_date     = date("d/m/Y");
                    $register_record = $record->titulo . '::' . $record_date . '::' . $url_record . PHP_EOL;
                    
					//logs
                    file_put_contents($logs_dir . '/log.csv', '"' . date("d/m/Y") . '","' . $record->titulo . '","' . $url_record . '","' . $baseDatos . '"' . "\n", FILE_APPEND | LOCK_EX);
                    
                    
                    echo $url_record . PHP_EOL;
                } else {
                    echo "Error: Could not create record in koha, check MARC21 format or database connection." . PHP_EOL;
                }
                
                
                $cnt++;
            }
        }
    }
}

$added_records .= '</tbody></table>';




function doi_exists($database_hostname, $database_username, $database_password, $database_name, $doi)
{
    $dbhandle = mysqli_connect($database_hostname, $database_username, $database_password, $database_name);
	if (mysqli_connect_errno()) {
		printf("Connection failed: %s\n", mysqli_connect_error());
		exit();
	}
    
    $doi = str_replace('https://', '', $doi);
    $doi = str_replace('http://', '', $doi);
    $doi = str_replace('dx.doi.org/', '', $doi);
    $doi = str_replace('doi.org/', '', $doi);
    $doi = str_replace('dx.doi.org', '', $doi);
    $doi = str_replace('doi.org', '', $doi);
    
    echo "Looking for DOI: " . $doi . PHP_EOL;
	$temp_query = 'SELECT biblionumber FROM biblio_metadata WHERE ExtractValue( metadata, \'//datafield[@tag="024"]/subfield[@code="a"]\' ) like \'%' . $doi . '%\'';
	
    $result = mysqli_query($dbhandle, $temp_query);
    $num_rows = mysqli_num_rows($result);
    mysqli_free_result($result);
	mysqli_close($dbhandle);
	
    if ($num_rows > 0)
        return true;
    else
        return false;
    
}

function record_exists($database_hostname, $database_username, $database_password, $database_name, $title, $sub_title, $type)
{
    $dbhandle = mysqli_connect($database_hostname, $database_username, $database_password, $database_name);
	if (mysqli_connect_errno()) {
		printf("Connection failed: %s\n", mysqli_connect_error());
		exit();
	}
	
    $title     = mysqli_real_escape_string($dbhandle, $title);
    $sub_title = mysqli_real_escape_string($dbhandle, $sub_title);
    
    $title = str_replace("&", "&amp;", $title);
    $title = str_replace("<", "&lt;", $title);
    $title = str_replace(">", "&gt;", $title);
    
    
    $sub_title = str_replace("&", "&amp;", $sub_title);
    $sub_title = str_replace("<", "&lt;", $sub_title);
    $sub_title = str_replace(">", "&gt;", $sub_title);
    
    $sub_title = !empty($sub_title) ? ' AND ExtractValue( metadata, \'//datafield[@tag="245"]/subfield[@code="b"]\' ) = \'' . $sub_title . '\'' : '';
	$temp_query = 'SELECT biblionumber FROM biblio_metadata WHERE ExtractValue( metadata, \'//datafield[@tag="245"]/subfield[@code="a"]\' ) = \'' . $title . '\'' . $sub_title;

	$result = mysqli_query($dbhandle, $temp_query);
    $num_rows = mysqli_num_rows($result);
    mysqli_free_result($result);
	mysqli_close($dbhandle);
    	
	
    if ($num_rows > 0)
        return true;
    else
        return false;
}




?>

