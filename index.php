<?php
set_time_limit(0);
ini_set('allow_url_fopen', 'on');
ini_set('memory_limit', '-1');

//Set the following values in php.ini:
//  post-max-size=0
//  upload-max-filesize=2000M

require __DIR__.'/vendor/autoload.php';
use Spipu\Html2Pdf\Html2Pdf;
use Spipu\Html2Pdf\Exception\Html2PdfException;
use Spipu\Html2Pdf\Exception\ExceptionFormatter;

$dirtooriginalzipfile = 'upload';
$dirtoallzipfiles = 'upload/firstunzip';
$dirtoalldata = 'upload/firstunzip/allzipcontent';
$dirtoallpdf = 'upload/allpdf';
$dirtofinalzip = 'upload/ready';
$dirtotemppdffiles = 'upload/allpdftemp';

$rename = array();
$rename['search'] = array('ä', 'ö', 'ü', 'ß', ' ');
$rename['replace'] = array('ae', 'oe', 'ue', 'ss', '_');

if(!file_exists($dirtoallpdf)){ mkdir($dirtoallpdf); }
if(!file_exists($dirtofinalzip)){ mkdir($dirtofinalzip); }
if(!file_exists($dirtotemppdffiles)){ mkdir($dirtotemppdffiles); }
if(!file_exists($dirtoallzipfiles)){ mkdir($dirtoallzipfiles); }
if(!file_exists($dirtoalldata)){ mkdir($dirtoalldata); }

function error($error = ''){
    if(!empty($error)){
        die('Ein Fehler ist aufgetreten: '.$error);
    }
    else{
        die('Ein Fehler ist aufgetreten. Bitte versuche es später erneut');
    }
}

function unzip($pathToZip, $pathtodest){
    $zip = new ZipArchive;
    if ($zip->open($pathToZip) === TRUE && $zip->extractTo($pathtodest) === TRUE) {
        $zip->close();
        return true;
    }
    else {
        error('Beim öffnen der zip-Datei ist ein Fehler aufgetreten.');
    }
}

function deldir($dir, $deletedir = true){
    if(file_exists($dir)){
        $directory = dir($dir);
        while ($t=$directory->read()){
            if (($t!="..") && ($t!=".")){
                if (!is_dir($dir."/".$t)){
                    unlink($dir."/".$t);
                }
                if (is_dir($dir."/".$t)){
                    deldir($dir."/".($t));
                }
            }
        }
        $directory->close();
        if ($deletedir) {
            rmdir($dir);
        }
    }
}


$alldata = scandir($dirtoalldata);
$allzips = scandir($dirtooriginalzipfile);
$allzipfiles = scandir($dirtoallzipfiles);
$allreadyzipfiles = scandir($dirtofinalzip);
$allpdfs = scandir($dirtoallpdf);


$get_reset = filter_input(INPUT_GET, "reset");
$get_download_all = filter_input(INPUT_GET, "downloadall");
$get_selected_zip = filter_input(INPUT_GET, "selectedzip");
$get_delete_zip = filter_input(INPUT_GET, "deletezip");
$get_generate_category = filter_input(INPUT_GET, "generatecategory");
$post_upload_submit = filter_input(INPUT_POST, "zipfileupload");
$post_upload_zipfile = isset($_FILES["zipfile"]) ? $_FILES["zipfile"] : null;

if ($post_upload_submit !== null && $post_upload_zipfile !== null && !empty($post_upload_zipfile["tmp_name"])) {
    if ($post_upload_zipfile["error"] == UPLOAD_ERR_OK) {
        $tmp_name = $post_upload_zipfile["tmp_name"];
        $name = basename($post_upload_zipfile["name"]);
        if(substr($name, -4, 4) == '.zip'){
            move_uploaded_file($tmp_name, $dirtooriginalzipfile."/MyStuff2PDF_Upload_".date("d.m.Y_H.i.s")."_-_".$name);
        }
    }
    
    header("Location: /");
    die();
}
if ($get_reset === "true") {
    deldir($dirtoallzipfiles, false);
    deldir($dirtoalldata, false);
    deldir($dirtoallpdf, false);
    deldir($dirtofinalzip, false);
    deldir($dirtotemppdffiles, false);
    
    header("Location: /");
    die();
}
if ($get_download_all === "true") {
    $finalzipname = $dirtofinalzip.'/MyStuff2PDF_'.date("d.m.Y_H.i.s").'.zip';
    $finalzip = new ZipArchive;
    if($finalzip->open($finalzipname, ZipArchive::CREATE)){
        foreach ($allpdfs as $singledata) {
            if($singledata !== '.' && $singledata !== '..'){
                if(substr($singledata, -4, 4) == '.pdf'){
                    $finalzip->addFile($dirtoallpdf.'/'.$singledata, $singledata);
                }
            }
        }
        $finalzip->close();
        unset($finalzip);
    }
    
    header("Location: /");
    die();
}
if ($get_selected_zip !== null) {
    $file = base64_decode($get_selected_zip);
    if (in_array($file.'.zip', $allzips)) {
        $neworiginalzipfilename = str_replace($rename['search'], $rename['replace'], $file.'.zip');
        rename($dirtooriginalzipfile.'/'.$file.'.zip', $dirtooriginalzipfile.'/'.$neworiginalzipfilename);
        unzip($dirtooriginalzipfile.'/'.$neworiginalzipfilename, $dirtoallzipfiles);
    }
    header("Location: /");
    die();
}
if ($get_delete_zip !== null) {
    $file = base64_decode($get_delete_zip);
    if (in_array($file.'.zip', $allzips)) {
        unlink($dirtooriginalzipfile.'/'.$file.'.zip');
    }
    header("Location: /");
    die();
}
if ($get_generate_category !== null) {
    $file = base64_decode($get_generate_category);
    if (in_array($file.'.zip', $allzipfiles)) {
        deldir($dirtoalldata, false);
        deldir($dirtotemppdffiles, false);
        
        $neworiginalzipfilename = str_replace($rename['search'], $rename['replace'], $file.'.zip');
        rename($dirtoallzipfiles.'/'.$file.'.zip', $dirtoallzipfiles.'/'.$neworiginalzipfilename);
        unzip($dirtoallzipfiles.'/'.$neworiginalzipfilename, $dirtoalldata);
        $alldata = scandir($dirtoalldata);
       
        foreach ($alldata as $singledata) {
            if($singledata !== '.' && $singledata !== '..'){
                if(substr($singledata, -4, 4) == '.csv'){
                    $newcsvfilename = str_replace($rename['search'], $rename['replace'], $singledata);
                    rename($dirtoalldata.'/'.$singledata, $dirtoalldata.'/'.$newcsvfilename);
                    
                    $singleitemdataoutput = array();
                    $singleitemoutput = array();
                    $finaloutput = '';
                    
                    $rawcsvoutput = array_map('str_getcsv', file($dirtoalldata.'/'.$newcsvfilename));
                    foreach($rawcsvoutput as $key=>$item){
                        if($key !== 0){
                            foreach($item as $nr=>$itemvalue){
                                $singleitemdataoutput[$rawcsvoutput[0][$nr]] = $itemvalue;
                            }
                            $singleitemoutput[] = $singleitemdataoutput;
                        }
                    }
                    
                    $temphandle = fopen($dirtotemppdffiles.'/temppdf_'.substr($singledata, 0, -4).'.pdf.txt', 'a');
                    foreach($singleitemoutput as $item){
                        $finaloutput .= '
                        <page>
                            <page_footer>
                                <table cellspacing="0" style="width: 100%; text-align: right;">
                                    <tr>
                                        <td style="width: 50%; text-align: left;">Erstellt '.date("d.m.Y").' um '.date("H:i:s").' Uhr</td>
                                        <td style="width: 50%;">Seite [[page_cu]] von [[page_nb]]</td>
                                    </tr>
                                </table>
                            </page_footer>
                            <tbody>
                                <table cellspacing="0" border="0.5" style="width: 100%; text-align: left;">';
                                    $finaloutput .= '<tr>';
                                        $finaloutput .= '<td style="width: 20%; padding: 5px;">Kategorie</td>';
                                        $finaloutput .= '<td style="width: 80%; padding: 5px;">'.substr($singledata, 0, -4).'</td>';
                                    $finaloutput .= '</tr>';
                                    foreach($item as $key=>$value){
                                        if($key !== 'item images'){
                                            $finaloutput .= '<tr>';
                                                if($key == 'item id'){ $key = 'Item ID'; }
                                                elseif($key == 'item updated'){ $key = 'letzte Aktualisierung'; }
                                                elseif($key == 'item created'){ $key = 'Erstellungsdatum'; }
                                                elseif($key == 'item location'){ $key = 'Ort'; }
                                                elseif($key == 'item colors'){ $key = 'Farbe'; }
                                                elseif($key == 'item attachments'){ $key = 'Anhänge'; }
                                                elseif($key == 'item barcode'){ $key = 'Barcode'; }
                                                $finaloutput .= '<td style="width: 20%; padding: 5px;">'.$key.'</td>';
                                                if($key=='Name'){$value = '<b>'.$value.'</b>';}
                                                $finaloutput .= '<td style="width: 80%; padding: 5px;">'.$value.'</td>';
                                            $finaloutput .= '</tr>';
                                        }
                                        elseif($key == 'item images'){
                                            $finaloutput .= '<tr>';
                                                $finaloutput .= '<td style="width: 20%; padding: 5px;">Fotos</td>';
                                                $finaloutput .= '<td style="width: 80%; padding: 0px 5px 0px 5px">';
                                                    if($value !== ''){
                                                        $images = explode('|', $value);
                                                        $i = 1;
                                                        $finaloutput .= '<table cellspacing="0" border="0" style="width: 100%; text-align: left;"><tr>';
                                                        foreach($images as $image){
                                                            $finaloutput .= '<td style="width: 25%;"><img src="'.$dirtoalldata.'/'.$image.'" width="135" style="margin: 2px;"/></td>';
                                                            if($i%4==0){
                                                                $finaloutput .= '</tr><tr>';
                                                            }
                                                            $i++;
                                                        }
                                                        $finaloutput .= '</tr></table>';
                                                    }
                                                $finaloutput .= '</td>';
                                            $finaloutput .= '</tr>';
                                        }
                                    }
                        $finaloutput .= '
                                </table>
                            </tbody>
                        </page>';
                        fwrite ($temphandle, $finaloutput);
                        $finaloutput = '';
                    }
                    fclose($temphandle);
                    unset($temphandle);
                    try {
                        $html2pdf = new HTML2PDF('d','A4','de',true,'UTF-8',array(10, 10, 10, 10));
                        $html2pdf->writeHTML(file_get_contents($dirtotemppdffiles.'/temppdf_'.substr($singledata, 0, -4).'.pdf.txt'));
                        $html2pdf->output($dirtoallpdf.'/MyStuff2PDF_'.str_replace($rename['search'], $rename['replace'], substr($singledata, 0, -4)).'.pdf', 'F');
                        unset($html2pdf);
                        unset($finaloutput);
                    } catch (Html2PdfException $e) {
                        unset($e);
                    }
                    
                }
            }
        }
        
        deldir($dirtoalldata, false);
        deldir($dirtotemppdffiles, false);
    }
    header("Location: /");
    die();
}




if (count($allzipfiles) <= 3) {
    //Noch nicht entpackt
    ?>
<form action="" method="post" enctype="multipart/form-data">
    <input type="file" name="zipfile" /><br/>
    <input type="submit" name="zipfileupload" value="Upload" />
</form>
<br/><br/><br/>
    <?php
    echo "Wähle eine Datei aus<br/>";
    ?>
    <table border="1">
        <thead>
            <tr>
                <th>Datei</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($allzips as $singledata) {
                if($singledata !== '.' && $singledata !== '..'){
                    if(substr($singledata, -4, 4) == '.zip'){
                        $filename = substr($singledata, 0, -4);
                        
                        echo "<tr>";
                            echo "<td>" . $filename . "</td>";

                            echo "<td><a href='/?selectedzip=" . base64_encode($filename) . "'>Auswahl</a> | <a href='/?deletezip=" . base64_encode($filename) . "'>Löschen</a></td>";
                        echo "</tr>";
                    }
                }
            }
            ?>
        </tbody>
    </table>
    <?php
}
else{
    //Schon entpackt
    ?>
    <a href="/?reset=true">Reset</a>
    <br/><br/>
    <a href="/?downloadall=true">Alles in eine Datei packen</a>
    <br/>
    <table border="1">
        <thead>
            <tr>
                <th>Name</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($allreadyzipfiles as $singledata) {
                if($singledata !== '.' && $singledata !== '..'){
                    if(substr($singledata, -4, 4) == '.zip'){
                        $filename = substr($singledata, 0, -4);

                        echo "<tr>";
                            echo "<td>" . $filename . "</td>";
                            echo "<td><a href='/".$dirtofinalzip."/".$singledata."'>Datei herunterladen</a></td>";
                        echo "</tr>";
                    }
                }
            }
            ?>
        </tbody>
    </table>
    <br/><br/>
    <table border="1">
        <thead>
            <tr>
                <th>Kategorie</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($allzipfiles as $singledata) {
                if($singledata !== '.' && $singledata !== '..'){
                    if(substr($singledata, -4, 4) == '.msd'){
                        unlink($dirtoalldata.'/'.$singledata);
                    }
                    elseif(substr($singledata, -4, 4) == '.zip'){
                        $categoryName = substr($singledata, 0, -4);

                        echo "<tr>";
                            echo "<td>" . $categoryName . "</td>";

                            if (file_exists($dirtoallpdf.'/MyStuff2PDF_'.$categoryName.'.pdf')) {
                                echo "<td>Schon fertig <a href='/".$dirtoallpdf."/MyStuff2PDF_".$categoryName.".pdf'>Datei herunterladen</a></td>";
                            }
                            else{
                                echo "<td><a href='/?generatecategory=" . base64_encode($categoryName) . "'>Generate PDF</a></td>";
                            }
                        echo "</tr>";
                    }
                }
            }
            ?>
        </tbody>
    </table>
    <?php
}