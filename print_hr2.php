<?php
include('word/dbclass.php');
$dbh=New db();
$nur=$_GET['nur'];
$stmt = $dbh->prepare("SELECT * FROM documentos d 
INNER JOIN users u ON u.id=d.id_user 
INNER JOIN oficinas o ON o.id=u.id_oficina
INNER JOIN entidades e ON e.id=o.id_entidad 
INNER JOIN procesos p ON d.id_proceso=p.id
WHERE d.nur='$nur'
AND d.original='1'");        
$stmt->execute();


$para = $dbh->prepare("SELECT s.nombre_receptor,s.accion,s.proveido
FROM seguimiento s WHERE nur ='$nur' AND id_seguimiento=0 AND oficial=1");
$para->execute();
$nombre_receptor = '';
$accion = 0;
$proveido = '';
while ($pa = $para->fetch(PDO::FETCH_LAZY)) {
    $nombre_receptor = $pa->nombre_receptor;
    $accion = $pa->accion;
    $proveido = $pa->proveido;
}

// copias
$copia = $dbh->prepare("SELECT  u.mosca FROM seguimiento s, users u
WHERE nur= '$nur'  AND s.id_seguimiento =0 AND s.oficial=0 AND u.id=s.derivado_a");
$copia->execute();

$copias = "";
while ($co = $copia->fetch(PDO::FETCH_LAZY)) {
    $copias .= $co->mosca.", ";
}

while ($rs = $stmt->fetch(PDO::FETCH_LAZY)) { 
    require('libs/fpdf17/fpdf.php');
    require('libs/fpdf17/code39.php');
	$pdf = new PDF_Code39('P','mm','Letter');
    $pdf->SetMargins(10, 10,5);
    $pdf->AddPage();    
    $pdf->SetFont('Arial', 'B', 18);    
    $image_file = 'media/logos/'.$rs->logo;
		
    $pdf->Image($image_file, 12, 10, 55, 30, 'png', '', '', FALSE, 300, '', FALSE, FALSE, 1);
    $pdf->SetFont('Arial', '', 20);
    $pdf->SetX(10);
    $pdf->Cell(60, 30, '', 1,FALSE,'C');                               
    $pdf->SetX(70);
    //hoja de seguimiento
    $pdf->Cell(75, 30, 'HOJA DE RUTA', 1,FALSE,'C');                                                                 
    $pdf->SetX(145); 
	
    //NUA
    $pdf->SetFont('Arial', 'B', 10);    
    $pdf->Cell(65, 5, $rs->sigla, 1,FALSE,'C');
    $pdf->SetXY(145, 15);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(65, 5, $rs->nur, 'TR',FALSE,'C');
    
    $pdf->SetXY(70, 20);  
    $pdf->SetFont('Arial', '', 7);
    $pdf->Cell(75, 20, 'Ministerio de Desarrollo Productivo y Economia Plural', 0,FALSE,'C'); 
    $pdf->SetFont('Arial', 'B', 14);    
    //$pdf->Cell(57, 5, $rs->nur, 'TR',FALSE,'C');
    $pdf->SetXY(145, 15);    
    //codigo de barra    
    if(strpos($rs->nur, "/"))    
        $pdf->Code39(147,20,$rs->nur,0.65,12);    
        //$pdf->Code39(152,21,$rs->nur,0.71,8);    
    else
        $pdf->Code39(155,20,$rs->nur,0.65,12);
        //$pdf->Code39(155,21,$rs->nur,0.71,8);
    //fin codigo barra
    $pdf->SetXY(145, 20);
    $pdf->Cell(65, 15, '', 'BR',FALSE,'C');    
    
    //cite
    $pdf->SetXY(145, 35);    
    $pdf->Cell(65, 5, $rs->cite_original, 1,FALSE,'C');
    $pdf->Ln();
    //columna 1
    $pdf->SetFont('helvetica', '', 9);    
    $pdf->Cell(30, 10, 'PROCEDENCIA:', 1,FALSE,'L');
    
        
    if(trim($rs->institucion_remitente)!='')
    {
        if(strlen($rs->institucion_remitente)>100)
        {
            $pdf->MultiCell(115, 10, $rs->institucion_remitente, 1,'L'); 
        }
        else
        {
            $pdf->Cell(115, 10, $rs->institucion_remitente, 1,'L'); 
        }
    }
    else
    {
        if(strlen($rs->entidad)>80)
        {
            $pdf->MultiCell(115, 5, $rs->entidad, 1,'L'); 
        }
        else
        {
            $pdf->Cell(115, 10, $rs->entidad, 1,'L'); 
        }         
    }    
    //fecha
    $pdf->SetXY(155, 40);   
    $pdf->Cell(13, 5, 'FECHA:', 1,FALSE,'R'); 
    $meses=array(1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre');
    //$mes=(INT)date('m', $rs->fecha_creacion);
    //$dia=date('d', $rs->fecha_creacion);
	$dia=date($rs[16]);
	$xdia=substr($rs[16],8,2);
	$xmes=substr($rs[16],5,2);
	$xmes = (int)($xmes);
	$xanio=substr($rs[16],0,4);
	$xyfecha = $xdia. ' de '. $meses[$xmes] . ' de '. $xanio;
    
	//$anio=date('Y',  $rs[16]);
		
	//$fecha=$dia.' de '.$meses[$mes].' de '.$anio;
		
    //$pdf->Cell(42, 5, $fecha, 1,FALSE,'C');  
			$pdf->Cell(42, 5, $xyfecha, 1,FALSE,'C');
    //hora
    $pdf->SetXY(155, 45);   
    $pdf->Cell(13, 5, 'HORA:', 1,FALSE,'R');    
    //$pdf->Cell(42, 5, date('h:i:s A',$rs->fecha_creacion), 1,FALSE,'C');  
			$pdf->Cell(42, 5, substr($rs[16],11,8), 1,FALSE,'C'); 
    //REMITENTE
    $pdf->SetXY(10, 50);
    $pdf->Cell(30, 10, 'REMITENTE:', 1,FALSE,'L');    
    $pdf->Cell(105, 6, $rs->nombre_remitente, 0,FALSE,'L');

    $pdf->SetFont('helvetica', 'B', 9);   
    $pdf->SetXY(40, 55);
    $pdf->Cell(105, 3, $rs->cargo_remitente, 0,FALSE,'L');
    $pdf->SetFont('helvetica', '', 9);    
    //proceso
    //fecha
    $pdf->SetXY(155, 50);   
    $pdf->SetFontSize(7);
    $pdf->Cell(13, 10, 'PROCESO', 1,FALSE,'R');     
    $pdf->MultiCell(42, 10, $rs->proceso, 1,'C');        
    $pdf->SetXY(10, 60);
    $pdf->SetFontSize(9);
    $pdf->Cell(30, 10, 'REFERENCIA:', 1,FALSE,'L');    
    $pdf->SetFont('helvetica', '', 9);             
    if(strlen($rs->referencia)>100)
    {
        $pdf->SetFont('helvetica', '', 7);
       $pdf->MultiCell(170, 5, $rs->referencia, 1,'L'); 
    }
    else
    {
       $pdf->Cell(170, 10, $rs->referencia, 1,'L'); 
       $pdf->Ln(10);
    }              
        
    $pdf->Cell(30, 5, 'ADJUNTO:', 1,FALSE,'L');    
    $pdf->Cell(145, 5, $rs->adjuntos, 'BLR',FALSE,'L');
    $pdf->Cell(15, 5, 'HOJAS', 'BLR',FALSE,'L');
    $pdf->Cell(10, 5, $rs->hojas, 'BLR',FALSE,'C');
    $pdf->Ln();
    //primera pagina
    
        // primer recuadro
       $pdf->Ln(4);
    $pdf->SetFontSize(10);
    $pdf->SetFillColor(240,245,255);
    $pdf->Cell(20, 7, 'Para:', 1,FALSE,'L',true);    
    $pdf->SetFontSize(8);
    $pdf->SetFillColor(0);
    $pdf->Cell(60, 7, $nombre_receptor, 1,FALSE,'L');    
    $pdf->SetFontSize(10);
    $pdf->SetFillColor(240,245,255);
    $pdf->Cell(10, 7, 'CC:', 1,FALSE,'L',true);
    $pdf->SetFillColor(0);
    $pdf->SetFontSize(8);
    $pdf->Cell(110, 7, $copias, 1,FALSE,'L');
    $pdf->ln();
    $pdf->SetFontSize(4);
    $pdf->Cell(26, 5, 'ACCION NECESARIA Y RESPUESTA', 1,FALSE,'L');
    $pdf->SetFontSize(8);
    if ($accion==1){
        $pdf->Cell(4, 5, 'X', 1,FALSE,'L');        
    } else{
        $pdf->Cell(4, 5, '', 1,FALSE,'L');    
    }
    $pdf->SetFontSize(4);
    $pdf->Cell(24, 5, 'PREPARAR RESPUESTA', 1,FALSE,'L');
    $pdf->SetFontSize(8);
    if ($accion==2){
        $pdf->Cell(4, 5, 'X', 1,FALSE,'L');        
    } else{
        $pdf->Cell(4, 5, '', 1,FALSE,'L');    
    }
    $pdf->SetFontSize(4);
    $pdf->Cell(24, 5, 'PREPARAR INFORME', 1,FALSE,'L');
    $pdf->SetFontSize(8);
    if ($accion==3){
        $pdf->Cell(4, 5, 'X', 1,FALSE,'C');        
    } else{
        $pdf->Cell(4, 5, '', 1,FALSE,'C');    
    }
    $pdf->SetFontSize(4);
    $pdf->Cell(25, 5, 'PARA SU CONSIDERACION', 1,FALSE,'L');
    $pdf->SetFontSize(8);
    if ($accion==4){
        $pdf->Cell(4, 5, 'X', 1,FALSE,'L');        
    } else{
        $pdf->Cell(4, 5, '', 1,FALSE,'L');    
    }
    $pdf->SetFontSize(4);
    $pdf->Cell(25, 5, 'PARA SU CONOCIMIENTO', 1,FALSE,'L');
    $pdf->SetFontSize(8);
    if ($accion==5){
        $pdf->Cell(4, 5, 'X', 1,FALSE,'L');        
    } else{
        $pdf->Cell(4, 5, '', 1,FALSE,'L');    
    }
    $pdf->SetFontSize(4);
    $pdf->Cell(15, 5, 'PARA FIRMA', 1,FALSE,'L');
    $pdf->SetFontSize(8);
    if ($accion==6){
        $pdf->Cell(4, 5, 'X', 1,FALSE,'L');        
    } else{
        $pdf->Cell(4, 5, '', 1,FALSE,'L');    
    }
    $pdf->SetFontSize(4);
    $pdf->Cell(15, 5, 'DESPACHAR', 1,FALSE,'L');
    $pdf->SetFontSize(8);
    if ($accion==7){
        $pdf->Cell(4, 5, 'X', 1,FALSE,'L');        
    } else{
        $pdf->Cell(4, 5, '', 1,FALSE,'L');    
    }
    $pdf->SetFontSize(4);
    $pdf->Cell(14, 5, 'ARCHIVAR', 1,FALSE,'L');
    $pdf->SetFontSize(8);
    if ($accion==8){
        $pdf->Cell(4, 5, 'X', 1,FALSE,'L');        
    } else{
        $pdf->Cell(4, 5, '', 1,FALSE,'L');    
    }
    $pdf->Ln();
    //proveido
    if(strlen($proveido)>85)
    {
        $pdf->SetXY(10,91);
        $pdf->MultiCell(144, 20,$proveido, 1,'L');
    }
    else
    {
       $pdf->Cell(144, 40, $proveido, 1,FALSE,'L');
    }

        //$pdf->Cell(144, 40, $proveido, 1,FALSE,'L');
    $pdf->SetTextColor(243,249,255);
    $pdf->SetFontSize(20);
    $pdf->SetXY(154,91);
    $pdf->MultiCell(56, 40, 'Sello Recibido', 1,'C');
    
    //$pdf->Cell(56, 40, 'Sello Recibido', 1,FALSE,'C');
    $pdf->SetTextColor(0);
    //$pdf->Ln(40);
    $pdf->SetFillColor(240,245,255);
    
    $pdf->SetFontSize(10);
    $pdf->Cell(20, 5, 'Adjunto:', 1,FALSE,'L',true);
    $pdf->Cell(124, 5, '', 1,FALSE,'L');
    $pdf->SetFillColor(240,245,255);
    $pdf->Cell(20, 5, 'Hora:', 1,FALSE,'L',true);
    $pdf->Cell(36, 5, '', 1,FALSE,'L');
    $pdf->Ln();
    
    for($i=2;$i<4;$i++)
    {
        $pdf->Ln(4);
    $pdf->SetFontSize(10);
    $pdf->SetFillColor(240,245,255);
    $pdf->Cell(20, 7, 'Para:', 1,FALSE,'L',true);    
    $pdf->SetFillColor(0);
    $pdf->Cell(60, 7, '', 1,FALSE,'L');    
    $pdf->SetFillColor(240,245,255);
    $pdf->Cell(10, 7, 'CC:', 1,FALSE,'L',true);
    $pdf->SetFillColor(0);
    $pdf->Cell(110, 7, '', 1,FALSE,'C');
    $pdf->ln();
    $pdf->SetFontSize(4);
    $pdf->Cell(26, 5, 'ACCION NECESARIA Y RESPUESTA', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');    
    $pdf->Cell(24, 5, 'PREPARAR RESPUESTA', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Cell(24, 5, 'PREPARAR INFORME', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Cell(25, 5, 'PARA SU CONSIDERACION', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Cell(25, 5, 'PARA SU CONOCIMIENTO', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Cell(15, 5, 'PARA FIRMA', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Cell(15, 5, 'DESPACHAR', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Cell(14, 5, 'ARCHIVAR', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Ln();
    //proveido
    $pdf->Cell(144, 40, '', 1,FALSE,'L');
    $pdf->SetTextColor(243,249,255);
     $pdf->SetFontSize(20);
    $pdf->Cell(56, 40, 'Sello Recibido', 1,FALSE,'C');
    $pdf->SetTextColor(0);
    $pdf->Ln(40);
    $pdf->SetFillColor(240,245,255);
    
    $pdf->SetFontSize(10);
    $pdf->Cell(20, 5, 'Adjunto:', 1,FALSE,'L',true);
    $pdf->Cell(124, 5, '', 1,FALSE,'L');
    $pdf->SetFillColor(240,245,255);
    $pdf->Cell(20, 5, 'Hora:', 1,FALSE,'L',true);
    $pdf->Cell(36, 5, '', 1,FALSE,'L');
    $pdf->Ln();
    }
    $pdf->Cell(20, 5,'Hoja de Ruta: '. $rs->nur, 0,FALSE,'L');    
    $pdf->ln();
    //segunda APgina
    for($i=1;$i<5;$i++)
    {
    $pdf->Ln(4);
    $pdf->SetFontSize(10);
    $pdf->SetFillColor(240,245,255);
    $pdf->Cell(20, 7, 'Para:', 1,FALSE,'L',true);    
    $pdf->SetFillColor(0);
    $pdf->Cell(60, 7, '', 1,FALSE,'L');    
    $pdf->SetFillColor(240,245,255);
    $pdf->Cell(10, 7, 'CC:', 1,FALSE,'L',true);
    $pdf->SetFillColor(0);
    $pdf->Cell(110, 7, '', 1,FALSE,'C');
    $pdf->ln();
    $pdf->SetFontSize(4);
    $pdf->Cell(26, 5, 'ACCION NECESARIA Y RESPUESTA', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');    
    $pdf->Cell(24, 5, 'PREPARAR RESPUESTA', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Cell(24, 5, 'PREPARAR INFORME', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Cell(25, 5, 'PARA SU CONSIDERACION', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Cell(25, 5, 'PARA SU CONOCIMIENTO', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Cell(15, 5, 'PARA FIRMA', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Cell(15, 5, 'DESPACHAR', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Cell(14, 5, 'ARCHIVAR', 1,FALSE,'L');
    $pdf->Cell(4, 5, '', 1,FALSE,'L');
    $pdf->Ln();
    //proveido
    $pdf->Cell(144, 40, '', 1,FALSE,'L');
    $pdf->SetTextColor(243,249,255);
     $pdf->SetFontSize(20);
    $pdf->Cell(56, 40, 'Sello Recibido', 1,FALSE,'C');
    $pdf->SetTextColor(0);
    $pdf->Ln(40);
    $pdf->SetFillColor(240,245,255);
    
    $pdf->SetFontSize(10);
    $pdf->Cell(20, 5, 'Adjunto:', 1,FALSE,'L',true);
    $pdf->Cell(124, 5, '', 1,FALSE,'L');
    $pdf->SetFillColor(240,245,255);
    $pdf->Cell(20, 5, 'Hora:', 1,FALSE,'L',true);
    $pdf->Cell(36, 5, '', 1,FALSE,'L');
    $pdf->Ln();
    }
$pdf->Output('hoja_ruta.pdf','D');
}
