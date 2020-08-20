<?php
session_start();


function queryBuilder($dateRange) {	    

	$q = "SELECT r.id, r.amount, r.receipt_date, r.proj_id, r.image, p.name AS projName
  		FROM receipts AS r 
	  	INNER JOIN projects AS p ON r.proj_id = p.id
	  	WHERE ".$dateRange."
	  	ORDER BY id DESC
  	";	
  	return $q;
}

$errors = array();
$output = '';
$count = 0; // count how many receipts uploaded


try 
{
	 include __DIR__ . '/../../DbConnection.php';	
		
	if ($_SERVER['REQUEST_METHOD'] === 'POST') 	
	{
		//check if the form submited is our own form
	    if (!isset($_POST['formtoken1']) || $_POST['formtoken1'] !== $_SESSION['formtoken1']) {
	        //$formtoken should always be set, if it is not set, create error
	        exit('The form submited is not valid. Please try again or contact support for additional assistance');
	    }
	    if (!empty($_POST['med'] )) { 
	    	//!empty means bots must have populated form submited
	        exit('The form submited is not valid. Med');
	    }

	    // Check which month the user wants to view
	    if (isset($_POST['april']) ) {
	    	
	    	// between 01/04/2020 - 31/04/2020
	    	$dateRange = "receipt_date >= '2020-04-01' AND receipt_date <= '2020-04-31' ";
	    	$$April = queryBuilder($dateRange ); // call
			$db_results = $pdo->query($April);		

	    } elseif (isset($_POST['may']) ) {
	    	
	    	$dateRange = "receipt_date >= '2020-05-01' AND receipt_date <= '2020-05-31'";
	    	$May = queryBuilder($dateRange ); // call
			$db_results = $pdo->query($May);	

	    } elseif (isset($_POST['june']) ) {
	    	
			$May = "SELECT r.id, r.amount, r.receipt_date, r.proj_id, r.image, p.name AS projName
		  	FROM receipts AS r 
		  	INNER JOIN projects AS p ON r.proj_id = p.id
		  	WHERE receipt_date >= '2020-06-01' AND receipt_date <= '2020-06-31'
		  	ORDER BY id DESC
		  	";
			$db_results = $pdo->query($May);

	    }  

	    if (isset($db_results)) {

			$totAmount = 0;	
			$tableRows = '';
			// <img class="resize" src="./receipt_gallery/'.$value['image'].'" />
			foreach ($db_results as $key => $value) {				
				$tableRows .= '
				<tr> 
					<td>'. $value['id']. '</td> 
					<td>€'.number_format($value['amount']/100, 2, '.', '') . '</td> 
					<td>'. $value['receipt_date']. '</td> 
                    <td>'. $value['projName']. '</td> 
                    <td>'. $value['image']. '</td> 
                    <td>                       
                      <a href="'.$value['image'].'">
                      	<img class="resize" src="'.$value['image'].'" />
                      </a> 
                    </td> 
				</tr>';
				$totAmount = $totAmount + $value['amount'];
			}
	    }


	} // $_POST


} catch (PDOException $e) {
	$message = 'Unable to connect to the database server: ' . $e->getMessage().' in ' . 
		$e->getFile() . ':' . $e->getLine();
	echo "<h3> $message </h3>";
}



$_SESSION['formtoken1'] = md5(uniqid(rand(), true));
$formToken1 = $_SESSION['formtoken1'];

?>



<!DOCTYPE html>
<html>
<head>
	<title></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"> 
	<link rel="stylesheet" href="js/magnific/magnific-popup.css">
	<link rel="stylesheet" href="css/main2.css">
	<link rel="stylesheet" href="//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css">
</head>


<body>
<div class="container" id="errors">
	<div class="col-12">
		<div><a href="https://universeyachting.eu/hr/"><strong>&#9776;  Home </strong></a> </div>
		<?php 
	 		foreach ($errors as $key => $value) {
	 			echo '<div class="alert alert-danger">'.$value. '</div>';	 		
	 		}
	 	?>
	</div>
</div>


<div class="container" id="form">	    
	<form enctype="multipart/form-data" action="" method="post" role="form" accept-charset="utf-8"> 
		<input type="hidden" name="formtoken1" value="<?php echo $_SESSION['formtoken1']; ?>" />   
        <p class="hp" style="display: none;"> <input type="text" name="med" id="med" value=""> </p>				
		<input type="submit" name="april" value="April &rarr;" class="btn btn-success">
		<input type="submit" name="may" value="May &rarr;" class="btn btn-success">
		<input type="submit" name="june" value="June &rarr;" class="btn btn-success">
		<input type="submit" name="july" value="July &rarr;" class="btn btn-success">
		<input type="submit" name="august" value="august &rarr;" class="btn btn-success">
	</form> 	
</div>


<div class="containter" id="data_from_database">	
	<div class="col-12" style="overflow-x:auto; font-size: smaller;"> 

		<?php if (isset($db_results)) {

			if ($totAmount) {
				echo "<h2>Συνολικά έξοδα διατροφής αυτόν τον μήνα: <strong>€". number_format($totAmount/100, 2, '.', '') ."</strong></h2>";
			} ?>

			<table class="table" id="myTable">
				<thead>
					<tr>
						<th>id</th>
						<th>Amount</th>
						<th>Receipt date</th>
		                <th>proj Id</th>
		                <th>image name</th>
		                <th>image</th>
					</tr>
				</thead>
				<tbody> 		  
				<?php 					
					echo isset($tableRows) ? $tableRows : '';
				?>
				</tbody>
			</table>
				
		<?php } ?>
	</div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script type="text/javascript" src="//cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
<script src="js/magnific/jquery.magnific-popup.min.js"></script>

<script type="text/javascript"> 
    
	$(document).ready( function () {
		
		// $('#myTable').DataTable();
		/* 
		$('#myTable').DataTable( {
			// https://datatables.net/examples/basic_init/table_sorting.html
			// you can alter the ordering characteristics of the table at initialisation time
			// Columns are ordered using 0 as first column on left. We want order by ID column, which is 0
	        "order": [[ 0, "desc" ]]
	    });
	    */
		
		// Lightbox
		$('#data_from_database a').magnificPopup({
			type: 'image'
		});


	});
</script>

</body>
</html> 


